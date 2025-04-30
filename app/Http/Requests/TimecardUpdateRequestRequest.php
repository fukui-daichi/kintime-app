<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimecardUpdateRequestRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを行うことを許可
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 申請作成のバリデーションルール
     */
    public function rules(): array
    {
        return [
            'timecard_id' => 'required|exists:timecards,id',
            'date' => 'required|date',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
            'break_start' => 'required|date_format:H:i',
            'break_end' => 'required|date_format:H:i',
            'reason' => 'required|string|max:500'
        ];
    }

    /**
     * バリデーション後のデータ整形
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        return [
            'timecard_id' => $validated['timecard_id'],
            'original_time' => [
                'clock_in' => $this->input('clock_in'),
                'clock_out' => $this->input('clock_out'),
                'break_start' => $this->input('break_start'),
                'break_end' => $this->input('break_end')
            ],
            'corrected_time' => [
                'clock_in' => $this->input('clock_in'),
                'clock_out' => $this->input('clock_out'),
                'break_start' => $this->input('break_start'),
                'break_end' => $this->input('break_end')
            ],
            'correction_type' => 'all',
            'reason' => $validated['reason']
        ];
    }
}
