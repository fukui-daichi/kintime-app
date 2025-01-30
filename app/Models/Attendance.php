<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'break_time',
        'actual_work_time',
        'overtime',
        'night_work_time',
        'status',
        'note'
    ];

    // Userモデルとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
