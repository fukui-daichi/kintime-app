<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimecardUpdateRequest extends Model
{
    use HasFactory;

    protected $table = 'timecard_update_requests';

    protected $fillable = [
        'user_id',
        'timecard_id',
        'original_time',
        'corrected_time',
        'correction_type',
        'status',
        'reason',
        'approver_id'
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_CLOCK_IN = 'clock_in';
    public const TYPE_CLOCK_OUT = 'clock_out';
    public const TYPE_BREAK_START = 'break_start';
    public const TYPE_BREAK_END = 'break_end';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timecard()
    {
        return $this->belongsTo(Timecard::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
