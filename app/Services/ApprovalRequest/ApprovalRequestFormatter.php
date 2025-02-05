<?php

namespace App\Services\ApprovalRequest;

use App\Helpers\TimeFormatter;
use App\Models\ApprovalRequest;
use App\Models\Attendance;
use Carbon\Carbon;

class ApprovalRequestFormatter
{
    /**
     * 申請種別の日本語表示用配列
     */
    private const REQUEST_TYPES = [
        'time_correction' => '時刻修正',
        'break_time_modification' => '休憩時間修正',
    ];

    /**
     * 申請状態の日本語表示用配列
     */
    private const REQUEST_STATUSES = [
        'pending' => '承認待ち',
        'approved' => '承認済み',
        'rejected' => '否認',
    ];

    /**
     * 申請データをフォーマット
     */
    public function format(ApprovalRequest $request): array
    {
        // 日付文字列をCarbonインスタンスに変換
        $createdAt = new Carbon($request->created_at);
        $attendanceDate = new Carbon($request->attendance->date);

        return [
            'id' => $request->id,
            'created_at' => $createdAt->format('Y/m/d H:i'),
            'attendance_date' => $attendanceDate->format('Y/m/d'),
            'request_type' => self::REQUEST_TYPES[$request->request_type] ?? $request->request_type,
            'status' => [
                'label' => self::REQUEST_STATUSES[$request->status] ?? $request->status,
                'class' => $this->getStatusClass($request->status),
            ],
            'approver_name' => $request->approver->full_name,
            'comment' => $request->comment,
            'before' => $this->formatTimeData($request, 'before'),
            'after' => $this->formatTimeData($request, 'after'),
        ];
    }

    /**
     * 時間データをフォーマット
     */
    private function formatTimeData(ApprovalRequest $request, string $type): array
    {
        $prefix = "{$type}_";
        return [
            'clock_in' => $request->{$prefix.'clock_in'}
                ? Carbon::parse($request->{$prefix.'clock_in'})->format('H:i')
                : null,
            'clock_out' => $request->{$prefix.'clock_out'}
                ? Carbon::parse($request->{$prefix.'clock_out'})->format('H:i')
                : null,
            'break_time' => $request->{$prefix.'break_time'}
                ? sprintf('%02d:%02d',
                    floor($request->{$prefix.'break_time'} / 60),
                    $request->{$prefix.'break_time'} % 60)
                : null,
        ];
    }

    /**
     * 申請状態に応じたCSSクラスを取得
     */
    private function getStatusClass(string $status): string
    {
        return match($status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * 申請フォーム表示用に勤怠データを整形
     *
     * @param Attendance $attendance
     * @return array
     */
    public function formatAttendanceForRequest(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'date' => $attendance->date->format('Y年m月d日'),
            'clock_in' => $attendance->clock_in
                ? Carbon::parse($attendance->clock_in)->format('H:i')
                : '未打刻',
            'clock_out' => $attendance->clock_out
                ? Carbon::parse($attendance->clock_out)->format('H:i')
                : '未打刻',
            'break_time' => $attendance->break_time
                ? sprintf('%02d:%02d',
                    floor($attendance->break_time / 60),
                    $attendance->break_time % 60)
                : '未設定',
            'actual_work_time' => $attendance->actual_work_time
                ? TimeFormatter::minutesToTime($attendance->actual_work_time)
                : '未計算',
            'raw_attendance' => $attendance,
        ];
    }
}
