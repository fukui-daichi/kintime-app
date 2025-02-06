<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',          // 申請者ID
        'approver_id',      // 承認者ID
        'timecard_id',    // 対象の勤怠ID
        'request_type',     // 申請種別
        'before_clock_in',  // 修正前出勤時間
        'before_clock_out', // 修正前退勤時間
        'before_break_time', // 修正前休憩時間
        'after_clock_in',   // 修正後出勤時間
        'after_clock_out',  // 修正後退勤時間
        'after_break_time', // 修正後休憩時間
        'status',           // 承認状態
        'reason',           // 申請理由
        'comment',          // 承認者コメント
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'before_clock_in' => 'datetime',
        'before_clock_out' => 'datetime',
        'after_clock_in' => 'datetime',
        'after_clock_out' => 'datetime',
        'before_break_time' => 'integer',
        'after_break_time' => 'integer',
    ];

    /**
     * 申請者のリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 承認者のリレーション
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * 勤怠記録のリレーション
     */
    public function timecard(): BelongsTo
    {
        return $this->belongsTo(Timecard::class);
    }
}
