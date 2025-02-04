<?php

namespace App\Http\Requests\ApprovalRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CreateApprovalRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを行う権限があるかを判定
     * ログインユーザーのみ申請可能
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attendance_id' => 'required|exists:attendances,id',
            'request_type' => 'required|in:time_correction,break_time_modification',
            // 時刻修正の場合のみ必須
            'after_clock_in' => 'required_if:request_type,time_correction|nullable|date_format:H:i',
            'after_clock_out' => [
                'required_if:request_type,time_correction',
                'nullable',
                'date_format:H:i',
                'after:after_clock_in'
            ],
            // 休憩時間修正の場合のみ必須
            'after_break_hours' => 'required_if:request_type,break_time_modification|nullable|numeric|between:0,5',
            // 共通
            'reason' => 'required|string|max:500',
        ];
    }

    /**
     * バリデーションメッセージの日本語化
     */
    public function messages(): array
    {
        return [
            'attendance_id.required' => '勤怠データが選択されていません',
            'attendance_id.exists' => '選択された勤怠データは存在しません',
            'request_type.required' => '申請種別を選択してください',
            'request_type.in' => '無効な申請種別です',
            'after_clock_in.required_if' => '修正後の出勤時刻を入力してください',
            'after_clock_in.date_format' => '出勤時刻は HH:mm 形式で入力してください',
            'after_clock_out.required_if' => '修正後の退勤時刻を入力してください',
            'after_clock_out.date_format' => '退勤時刻は HH:mm 形式で入力してください',
            'after_clock_out.after' => '退勤時刻は出勤時刻より後である必要があります',
            'after_break_hours.required_if' => '修正後の休憩時間を入力してください',
            'after_break_hours.numeric' => '休憩時間は数値で入力してください',
            'after_break_hours.between' => '休憩時間は0〜5時間の間で入力してください',
            'reason.required' => '申請理由を入力してください',
            'reason.max' => '申請理由は500文字以内で入力してください',
        ];
    }

    /**
     * バリデーション後のデータ加工
     * 承認者IDなどの追加情報を付与
     */
    public function validatedData(): array
    {
        $validated = $this->validated();

        // 申請データの追加情報を付与
        return array_merge($validated, [
            'user_id' => Auth::id(),
            'approver_id' => $this->getDefaultApproverId(),
            'status' => 'pending',
        ]);
    }

    /**
     * デフォルトの承認者ID（管理者）を取得
     */
    private function getDefaultApproverId(): int
    {
        return \App\Models\User::where('user_type', 'admin')
            ->first()
            ->id;
    }
}
