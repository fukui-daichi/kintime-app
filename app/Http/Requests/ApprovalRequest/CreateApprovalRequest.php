<?php

namespace App\Http\Requests\ApprovalRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CreateApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'attendance_id' => 'required|exists:attendances,id',
            'request_type' => 'required|in:time_correction,break_time_modification',
            // 時刻修正の場合は、どちらかが必須
            'after_clock_in' => [
                'nullable',
                'required_without_all:after_clock_out',
                'date_format:H:i',
                'required_if:request_type,time_correction',
            ],
            'after_clock_out' => [
                'nullable',
                'required_without_all:after_clock_in',
                'date_format:H:i',
                'required_if:request_type,time_correction',
                // 出勤時刻が入力されている場合は、退勤時刻は出勤時刻より後でなければならない
                'after:after_clock_in',
            ],
            // 休憩時間修正の場合は時刻形式で入力
            'after_break_hours' => [
                'nullable',
                'required_if:request_type,break_time_modification',
                'date_format:H:i',
            ],
            'reason' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_id.required' => '勤怠データが選択されていません',
            'attendance_id.exists' => '選択された勤怠データは存在しません',
            'request_type.required' => '申請種別を選択してください',
            'request_type.in' => '無効な申請種別です',
            'after_clock_in.required_without_all' => '出勤時刻または退勤時刻のいずれかを入力してください',
            'after_clock_in.date_format' => '出勤時刻は HH:mm 形式で入力してください',
            'after_clock_out.required_without_all' => '出勤時刻または退勤時刻のいずれかを入力してください',
            'after_clock_out.date_format' => '退勤時刻は HH:mm 形式で入力してください',
            'after_clock_out.after' => '退勤時刻は出勤時刻より後である必要があります',
            'after_break_hours.required_if' => '修正後の休憩時間を入力してください',
            'after_break_hours.date_format' => '休憩時間は HH:mm 形式で入力してください',
            'reason.required' => '申請理由を入力してください',
            'reason.max' => '申請理由は500文字以内で入力してください',
        ];
    }

    public function validatedData(): array
    {
        $validated = $this->validated();

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
