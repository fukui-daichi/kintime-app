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
        'original_clock_in',
        'original_clock_out',
        'original_break_start',
        'original_break_end',
        'corrected_clock_in',
        'corrected_clock_out',
        'corrected_break_start',
        'corrected_break_end',
        'status',
        'reason',
        'approver_id'
    ];

    protected $casts = [
        'original_clock_in' => 'datetime',
        'original_clock_out' => 'datetime',
        'original_break_start' => 'datetime',
        'original_break_end' => 'datetime',
        'corrected_clock_in' => 'datetime',
        'corrected_clock_out' => 'datetime',
        'corrected_break_start' => 'datetime',
        'corrected_break_end' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

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
