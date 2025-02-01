<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Userモデルとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 申請履歴とのリレーション
     * この勤怠に対する申請履歴を取得
     */
    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    /**
     * 承認待ちの申請が存在するか確認
     */
    public function hasPendingRequest(): bool
    {
        return $this->approvalRequests()
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * 最新の申請を取得
     */
    public function getLatestRequest()
    {
        return $this->approvalRequests()
            ->latest()
            ->first();
    }
}
