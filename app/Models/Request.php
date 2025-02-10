<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Request extends Model
{
    use HasFactory;

    /**
     * 申請種別の定数
     */
    public const TYPE_TIMECARD = 'timecard';
    public const TYPE_PAID_VACATION = 'paid_vacation';

    /**
     * 申請状態の定数
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * 有給休暇種別の定数
     */
    public const VACATION_TYPE_FULL = 'full';
    public const VACATION_TYPE_AM = 'am';
    public const VACATION_TYPE_PM = 'pm';

    protected $fillable = [
        'user_id',
        'approver_id',
        'request_type',
        'target_date',
        'status',
        'reason',
        'comment',
        'before_clock_in',
        'before_clock_out',
        'after_clock_in',
        'after_clock_out',
        'before_break_time',
        'after_break_time',
        'vacation_type',
        'approved_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'before_clock_in' => 'datetime',
        'before_clock_out' => 'datetime',
        'after_clock_in' => 'datetime',
        'after_clock_out' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // リレーション定義
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function timecard(): BelongsTo
    {
        return $this->belongsTo(Timecard::class, 'target_date', 'date');
    }

    // statusやrequest_typeに関するヘルパーメソッドを追加
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isTimecardRequest(): bool
    {
        return $this->request_type === self::TYPE_TIMECARD;
    }

    public function isPaidVacationRequest(): bool
    {
        return $this->request_type === self::TYPE_PAID_VACATION;
    }
}
