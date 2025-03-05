<?php

namespace App\Http\Requests\Request;

use App\Constants\RequestConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CreateRequest extends FormRequest
{
    /**
     * バリデーション前の処理
     */
    protected function prepareForValidation()
    {
        // リクエストタイプの判定と追加フラグの設定
        $requestType = $this->input('request_type');
        $this->merge([
            'is_timecard_request' => $requestType === RequestConstants::REQUEST_TYPE_TIMECARD,
            'is_paid_vacation_request' => $requestType === RequestConstants::REQUEST_TYPE_PAID_VACATION
        ]);
    }

    /**
     * バリデーションルールを取得
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'request_type' => [
                'required',
                Rule::in([
                    RequestConstants::REQUEST_TYPE_TIMECARD,
                    RequestConstants::REQUEST_TYPE_PAID_VACATION
                ])
            ],
            'reason' => 'required|string|max:500',
            'target_date' => 'required|date',
        ];

        // 勤怠修正申請の場合
        if ($this->input('is_timecard_request')) {
            $rules['timecard_id'] = 'required|exists:timecards,id';
            $rules['after_clock_in'] = [
                'required_without:after_clock_out',
                'nullable',
                'date_format:H:i',
            ];
            $rules['after_clock_out'] = [
                'required_without:after_clock_in',
                'nullable',
                'date_format:H:i',
                Rule::when(
                    $this->filled('after_clock_in'),
                    ['after:after_clock_in']
                ),
            ];
            $rules['after_break_time'] = [
                'nullable',
                'date_format:H:i',
            ];
        }

        // 有給休暇申請の場合
        if ($this->input('is_paid_vacation_request')) {
            $rules['vacation_type'] = [
                'required',
                Rule::in([
                    RequestConstants::VACATION_TYPE_FULL,
                    RequestConstants::VACATION_TYPE_AM,
                    RequestConstants::VACATION_TYPE_PM
                ])
            ];

            // 未来日付のチェック
            $rules['target_date'] = [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $targetDate = Carbon::parse($value);
                    if ($targetDate->lt(Carbon::today())) {
                        $fail('有給休暇申請は本日以降の日付のみ可能です。');
                    }
                }
            ];
        }

        return $rules;
    }

    /**
     * バリデーションメッセージをカスタマイズ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'timecard_id.required' => '勤怠データが選択されていません',
            'timecard_id.exists' => '選択された勤怠データは存在しません',
            'request_type.required' => '申請種別を選択してください',
            'request_type.in' => '無効な申請種別です',
            'after_clock_in.date_format' => '出勤時刻は HH:mm 形式で入力してください',
            'after_clock_out.date_format' => '退勤時刻は HH:mm 形式で入力してください',
            'after_clock_out.after' => '退勤時刻は出勤時刻より後である必要があります',
            'vacation_type.required' => '休暇種別を選択してください',
            'vacation_type.in' => '無効な休暇種別です',
            'target_date.required' => '対象日が指定されていません',
            'target_date.date' => '対象日は有効な日付形式で指定してください',
            'reason.required' => '申請理由を入力してください',
            'reason.max' => '申請理由は500文字以内で入力してください',
        ];
    }

    /**
     * バリデーション済みデータを取得して整形
     *
     * @return array
     */
    public function validatedData(): array
    {
        $validated = $this->validated();

        try {
            // 共通データの設定
            $user = $this->user();
            $validated['user_id'] = $user->getKey();

            // 管理者を取得して承認者IDを設定
            $admin = \App\Models\User::where('user_type', 'admin')->first();
            $validated['approver_id'] = $admin->getKey();
            $validated['status'] = 'pending';

            // 申請種別に応じた処理
            if ($validated['request_type'] === RequestConstants::REQUEST_TYPE_TIMECARD) {
                return $this->prepareTimecardRequestData($validated);
            } else {
                return $this->preparePaidVacationRequestData($validated);
            }
        } catch (\Exception $e) {
            Log::error('リクエストデータの整形エラー', [
                'error' => $e->getMessage(),
                'validated_data' => $validated,
                'user' => $this->user()
            ]);
            throw $e;
        }
    }

    /**
     * 勤怠修正申請データの準備
     *
     * @param array $validated バリデーション済みデータ
     * @return array 整形済みデータ
     */
    private function prepareTimecardRequestData(array $validated): array
    {
        // timecardのデータを取得
        $timecard = \App\Models\Timecard::findOrFail($validated['timecard_id']);

        // before_*の情報を追加
        $validated['before_clock_in'] = $timecard->clock_in;
        $validated['before_clock_out'] = $timecard->clock_out;
        $validated['before_break_time'] = $timecard->break_time;

        // 休憩時間を時刻形式から分単位に変換
        if (isset($validated['after_break_time'])) {
            $validated['after_break_time'] = \App\Helpers\TimeFormatter::timeToMinutes($validated['after_break_time']);
        }

        // 不要なフィールドを削除
        unset($validated['is_timecard_request']);
        unset($validated['is_paid_vacation_request']);

        return array_filter($validated, function ($value) {
            return $value !== null;
        });
    }

    /**
     * 有給休暇申請データの準備
     *
     * @param array $validated バリデーション済みデータ
     * @return array 整形済みデータ
     */
    private function preparePaidVacationRequestData(array $validated): array
    {
        // 不要なフィールドを削除
        unset($validated['is_timecard_request']);
        unset($validated['is_paid_vacation_request']);
        unset($validated['timecard_id']);

        return array_filter($validated, function ($value) {
            return $value !== null;
        });
    }
}
