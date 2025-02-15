<?php

namespace App\Http\Requests\Request;

use App\Constants\RequestConstants;
use App\Helpers\TimeFormatter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    /**
     * バリデーションルールを取得
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'timecard_id' => 'required|exists:timecards,id',
            'request_type' => [
                'required',
                Rule::in([
                    RequestConstants::REQUEST_TYPE_TIMECARD,
                    RequestConstants::REQUEST_TYPE_PAID_VACATION
                ])
            ],
            'reason' => 'required|string|max:500',
        ];

        if ($this->input('request_type') === RequestConstants::REQUEST_TYPE_TIMECARD) {
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

        return $rules;
    }

    /**
     * バリデーション前の準備処理
     */
    protected function prepareForValidation()
    {
        if ($this->input('request_type') === RequestConstants::REQUEST_TYPE_TIMECARD) {
            $this->merge([
                'any_time' => (!empty($this->after_clock_in) || !empty($this->after_clock_out)) ? 'true' : 'false'
            ]);
        }
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
            'any_time.required' => '出勤時刻または退勤時刻のいずれかを入力してください',
            'any_time.in' => '出勤時刻または退勤時刻のいずれかを入力してください',
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
            // timecardのデータを取得
            $timecard = \App\Models\Timecard::findOrFail($validated['timecard_id']);

            // target_dateを設定
            $validated['target_date'] = $timecard->date;

            // 休憩時間を時刻形式から分単位に変換
            if (isset($validated['after_break_time'])) {
                $validated['after_break_time'] = \App\Helpers\TimeFormatter::timeToMinutes($validated['after_break_time']);
            }

            // before_*の情報を追加
            $validated['before_clock_in'] = $timecard->clock_in;
            $validated['before_clock_out'] = $timecard->clock_out;
            $validated['before_break_time'] = $timecard->break_time;

            // ユーザー情報を追加
            $user = $this->user();  // 現在のユーザーを取得
            $validated['user_id'] = $user->getKey();  // idプロパティの代わりにgetKey()を使用

            // 管理者を取得して承認者IDを設定
            $admin = \App\Models\User::where('user_type', 'admin')->first();
            $validated['approver_id'] = $admin->getKey();

            $validated['status'] = 'pending';

            // nullの値を除外
            return array_filter($validated, function ($value) {
                return $value !== null;
            });

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
     * @param array $validated
     * @param \App\Models\Timecard $timecard
     * @return array
     */
    private function prepareTimecardRequestData(array $validated, \App\Models\Timecard $timecard): array
    {
        // 入力がない場合は元の値を使用
        $validated['before_clock_in'] = $timecard->clock_in;
        $validated['before_clock_out'] = $timecard->clock_out;
        $validated['before_break_time'] = $timecard->break_time;

        $validated['after_clock_in'] = $validated['after_clock_in']
            ?? substr($timecard->clock_in, 0, 5);
        $validated['after_clock_out'] = $validated['after_clock_out']
            ?? substr($timecard->clock_out, 0, 5);

        return $validated;
    }

    /**
     * デフォルトの承認者ID（管理者）を取得
     *
     * @return int
     */
    private function getDefaultApproverId(): int
    {
        return \App\Models\User::where('user_type', 'admin')
            ->first()
            ->id;
    }
}
