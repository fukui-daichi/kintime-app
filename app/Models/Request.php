<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Request extends Model
{
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
}
