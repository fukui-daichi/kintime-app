<?php

namespace App\Http\Requests\ApprovalRequest;

use App\Helpers\TimeFormatter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CreateApprovalRequest extends FormRequest
{
    public function rules(): array
    {
        // デバッグ用ログ
        // Log::info('Request Type:', ['type' => $this->input('request_type')]);
        // Log::info('All Input:', $this->all());

        $rules = [
            'attendance_id' => 'required|exists:attendances,id',
            'request_type' => 'required|in:time_correction,break_time_modification',
            'reason' => 'required|string|max:500',
        ];

        // 申請種別に応じたバリデーションルールを設定
        switch ($this->input('request_type')) {
            case 'time_correction':
                $rules['after_clock_in'] = [
                    'nullable',
                    'date_format:H:i',
                ];
                $rules['after_clock_out'] = [
                    'nullable',
                    'date_format:H:i',
                    Rule::when(
                        $this->filled('after_clock_in'),
                        ['after:after_clock_in']
                    ),
                ];
                // 時刻修正の場合、少なくともどちらかの時刻が必要
                $rules['any_time'] = ['required', 'in:true'];
                break;

            case 'break_time_modification':
                $rules['after_break_time'] = [
                    'required',
                    'date_format:H:i',
                    'before:05:00',
                ];
                break;
        }

        return $rules;
    }

    /**
     * バリデーション前の準備処理
     */
    protected function prepareForValidation()
    {
        if ($this->input('request_type') === 'time_correction') {
            $this->merge([
                'any_time' => (!empty($this->after_clock_in) || !empty($this->after_clock_out)) ? 'true' : 'false'
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'attendance_id.required' => '勤怠データが選択されていません',
            'attendance_id.exists' => '選択された勤怠データは存在しません',
            'request_type.required' => '申請種別を選択してください',
            'request_type.in' => '無効な申請種別です',
            'after_clock_in.date_format' => '出勤時刻は HH:mm 形式で入力してください',
            'after_clock_out.date_format' => '退勤時刻は HH:mm 形式で入力してください',
            'after_clock_out.after' => '退勤時刻は出勤時刻より後である必要があります',
            'after_break_time.required' => '休憩時間を入力してください',
            'after_break_time.date_format' => '休憩時間は HH:mm 形式で入力してください',
            'after_break_time.before' => '休憩時間は5時間以内で入力してください',
            'any_time.required' => '出勤時刻または退勤時刻のいずれかを入力してください',
            'any_time.in' => '出勤時刻または退勤時刻のいずれかを入力してください',
            'reason.required' => '申請理由を入力してください',
            'reason.max' => '申請理由は500文字以内で入力してください',
        ];
    }

    public function validatedData(): array
    {
        $validated = $this->validated();

        // any_timeフィールドを削除
        unset($validated['any_time']);

        // 休憩時間が入力されている場合、分単位に変換
        if (isset($validated['after_break_time'])) {
            $validated['after_break_time'] = TimeFormatter::timeToMinutes($validated['after_break_time']);
        }

        // 申請データに必要な情報を追加
        return array_merge($validated, [
            'user_id' => Auth::id(),
            'approver_id' => $this->getDefaultApproverId(),
            'status' => 'pending',
        ]);
    }

    private function getDefaultApproverId(): int
    {
        return \App\Models\User::where('user_type', 'admin')
            ->first()
            ->id;
    }
}
