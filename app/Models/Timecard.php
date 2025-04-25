<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timecard extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
        'status',
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
