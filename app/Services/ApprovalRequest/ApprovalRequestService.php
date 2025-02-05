<?php

namespace App\Services\ApprovalRequest;

use App\Models\ApprovalRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 申請に関するビジネスロジックを管理するサービスクラス
 */
class ApprovalRequestService
{
    /**
     * @var ApprovalRequestFormatter
     */
    private $formatter;

    /**
     * コンストラクタ
     *
     * @param ApprovalRequestFormatter $formatter
     */
    public function __construct(ApprovalRequestFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * 指定したユーザーの申請一覧を取得
     *
     * @param int $userId ユーザーID
     * @return Collection フォーマット済みの申請一覧
     */
    public function getUserRequests(int $userId): Collection
    {
        $requests = ApprovalRequest::with(['attendance', 'approver'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return $requests->map(fn($request) => $this->formatter->format($request));
    }

    /**
     * ステータスでフィルタリングした申請一覧を取得
     *
     * @param string|null $status 取得したい申請のステータス（null or 'all'の場合は全件取得）
     * @param int $perPage 1ページあたりの表示件数
     * @return LengthAwarePaginator ページネーション済みの申請一覧
     */
    public function getFilteredRequests(?string $status = 'pending', int $perPage = 20): LengthAwarePaginator
    {
        $query = ApprovalRequest::with(['user', 'attendance', 'approver'])
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest();

        return $query->paginate($perPage);
    }

    /**
     * 承認待ちの申請一覧を取得（管理者用）
     *
     * @return Collection フォーマット済みの承認待ち申請一覧
     */
    public function getPendingRequests(): Collection
    {
        $requests = $this->getFilteredRequests('pending')->items();
        return collect($requests)->map(fn($request) => $this->formatter->format($request));
    }

    /**
     * 新規申請を作成
     *
     * @param array $data
     * @return ApprovalRequest
     * @throws \Exception
     */
    public function createRequest(array $data): ApprovalRequest
    {
        try {
            return DB::transaction(function () use ($data) {
                // リクエストデータから申請を作成
                $request = ApprovalRequest::create($data);

                // 関連する勤怠データのステータスを更新
                $request->attendance->update([
                    'status' => 'pending_approval'
                ]);

                return $request;
            });
        } catch (\Exception $e) {
            Log::error('申請作成中にエラーが発生しました: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 申請を承認
     *
     * @param ApprovalRequest $request
     * @return bool
     */
    public function approveRequest(ApprovalRequest $request): bool
    {
        try {
            return DB::transaction(function () use ($request) {
                // デバッグログ（申請データの確認）
                Log::debug('Approval Request Data:', [
                    'attendance_date' => $request->attendance->date,
                    'after_clock_in' => $request->after_clock_in,
                    'after_clock_out' => $request->after_clock_out,
                ]);

                // 申請を承認状態に更新
                $request->update([
                    'status' => 'approved'
                ]);

                // 勤怠データの更新
                $attendance = $request->attendance;
                $updateData = [];

                // 時刻修正の申請の場合
                if ($request->request_type === 'time_correction') {
                    if ($request->after_clock_in) {
                        try {
                            // まず時刻部分だけを取り出す
                            $timeOnly = Carbon::parse($request->after_clock_in)->format('H:i');
                            // 日付と時刻を結合
                            $updateData['clock_in'] = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $timeOnly);
                        } catch (\Exception $e) {
                            Log::error('出勤時刻の変換エラー:', [
                                'error' => $e->getMessage(),
                                'input_time' => $request->after_clock_in,
                                'extracted_time' => $timeOnly ?? null
                            ]);
                            throw $e;
                        }
                    }

                    if ($request->after_clock_out) {
                        try {
                            // まず時刻部分だけを取り出す
                            $timeOnly = Carbon::parse($request->after_clock_out)->format('H:i');
                            // 日付と時刻を結合
                            $updateData['clock_out'] = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $timeOnly);
                        } catch (\Exception $e) {
                            Log::error('退勤時刻の変換エラー:', [
                                'error' => $e->getMessage(),
                                'input_time' => $request->after_clock_out,
                                'extracted_time' => $timeOnly ?? null
                            ]);
                            throw $e;
                        }
                    }
                }

                // 休憩時間修正の申請の場合
                if ($request->request_type === 'break_time_modification' && $request->after_break_time) {
                    $updateData['break_time'] = $request->after_break_time;
                }

                // 実労働時間、残業時間、深夜時間を再計算
                if (!empty($updateData)) {
                    $clockIn = $updateData['clock_in'] ?? $attendance->clock_in;
                    $clockOut = $updateData['clock_out'] ?? $attendance->clock_out;
                    $breakTime = $updateData['break_time'] ?? $attendance->break_time;

                    if ($clockIn && $clockOut) {
                        $workMinutes = Carbon::parse($clockOut)->diffInMinutes(Carbon::parse($clockIn));
                        $actualWorkMinutes = $workMinutes - $breakTime;

                        $updateData['actual_work_time'] = $actualWorkMinutes;
                        $updateData['overtime'] = max(0, $actualWorkMinutes - 480);
                        $updateData['night_work_time'] = $this->calculateNightWorkMinutes(
                            Carbon::parse($clockIn),
                            Carbon::parse($clockOut)
                        );
                    }
                }

                // ステータスを更新
                $updateData['status'] = 'left';

                // デバッグ用ログ出力
                Log::debug('Update Data:', [
                    'attendance_id' => $attendance->id,
                    'update_data' => $updateData,
                ]);

                // 勤怠データを更新
                $attendance->update($updateData);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('申請承認中にエラーが発生しました: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 深夜時間を計算（22時〜5時の間の勤務時間）
     */
    private function calculateNightWorkMinutes(Carbon $clockIn, Carbon $clockOut): int
    {
        $nightWorkMinutes = 0;
        $currentTime = $clockIn->copy();

        while ($currentTime < $clockOut) {
            $hour = (int)$currentTime->format('H');
            if ($hour >= 22 || $hour < 5) {
                $nightWorkMinutes++;
            }
            $currentTime->addMinute();
        }

        return $nightWorkMinutes;
    }

    /**
     * 申請を否認
     *
     * @param ApprovalRequest $request
     * @return bool
     */
    public function rejectRequest(ApprovalRequest $request): bool
    {
        try {
            return DB::transaction(function () use ($request) {
                // 申請を否認状態に更新
                $request->update([
                    'status' => 'rejected'
                ]);

                // 勤怠データのステータスを元に戻す
                $request->attendance->update(['status' => 'left']);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('申請否認中にエラーが発生しました: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 勤怠データが修正申請可能か確認
     *
     * @param Attendance $attendance 確認対象の勤怠データ
     * @return bool 修正申請可能な場合はtrue
     */
    public function canRequestModification(Attendance $attendance): bool
    {
        return !$attendance->hasPendingRequest() &&
               $attendance->status !== 'pending_approval';
    }

    /**
     * 申請フォーム表示用のデータを取得
     *
     * @param Attendance $attendance
     * @return array
     */
    public function getRequestFormData(Attendance $attendance): array
    {
        return $this->formatter->formatAttendanceForRequest($attendance);
    }
}
