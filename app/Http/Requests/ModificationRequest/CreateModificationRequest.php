<?php

namespace App\Http\Requests\ModificationRequest;

use App\Helpers\TimeFormatter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CreateModificationRequest extends FormRequest
{
    public function rules(): array
    {
        // デバッグ用ログ
        // Log::info('Request Type:', ['type' => $this->input('request_type')]);
        // Log::info('All Input:', $this->all());

        $rules = [
            'timecard_id' => 'required|exists:timecards,id',
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
            'timecard_id.required' => '勤怠データが選択されていません',
            'timecard_id.exists' => '選択された勤怠データは存在しません',
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

        // 対象の勤怠データを取得
        $timecard = \App\Models\Timecard::find($validated['timecard_id']);

        // 時刻修正の場合
        if ($validated['request_type'] === 'time_correction') {
            // 入力がない場合は元の値を使用
            $validated['after_clock_in'] = $validated['after_clock_in']
                ?? substr($timecard->clock_in, 0, 5);
            $validated['after_clock_out'] = $validated['after_clock_out']
                ?? substr($timecard->clock_out, 0, 5);
        }

        // 休憩時間修正の場合
        if ($validated['request_type'] === 'break_time_modification') {
            // 入力がない場合は元の値を使用（分単位で保存）
            $validated['after_break_time'] = $validated['after_break_time']
                ? TimeFormatter::timeToMinutes($validated['after_break_time'])
                : $timecard->break_time;
        }

        // 申請データに必要な情報を追加
        return array_merge($validated, [
            'user_id' => Auth::id(),
            'approver_id' => $this->getDefaultApproverId(),
            'status' => 'pending',
            // 修正前の値を保存
            'before_clock_in' => $timecard->clock_in,
            'before_clock_out' => $timecard->clock_out,
            'before_break_time' => $timecard->break_time,
        ]);
    }

    private function getDefaultApproverId(): int
    {
        return \App\Models\User::where('user_type', 'admin')
            ->first()
            ->id;
    }
}
