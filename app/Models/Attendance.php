<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 一括代入可能な属性
     *
     * @var array<string, mixed>
     */
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
     * The attributes that should be cast.
     * 属性の型キャスト定義
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',                    // Y-m-d 形式の文字列をCarbonインスタンスに変換
        'clock_in' => 'datetime',            // Y-m-d H:i:s 形式をCarbonインスタンスに変換
        'clock_out' => 'datetime',           // Y-m-d H:i:s 形式をCarbonインスタンスに変換
        'break_time' => 'integer',           // 文字列を整数に変換
        'actual_work_time' => 'integer',     // 文字列を整数に変換
        'overtime' => 'integer',             // 文字列を整数に変換
        'night_work_time' => 'integer',      // 文字列を整数に変換
        'status' => 'string',                // statusはENUMだが、文字列として扱う
    ];

    /**
     * 勤怠状態の定数定義
     */
    public const STATUS_WORKING = 'working';           // 勤務中
    public const STATUS_LEFT = 'left';                 // 退勤済み
    public const STATUS_PENDING_APPROVAL = 'pending_approval'; // 承認待ち
    public const STATUS_APPROVED = 'approved';         // 承認済み

    /**
     * Userモデルとのリレーション
     * 勤怠データに紐づくユーザー情報を取得
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
     *
     * @return bool
     */
    public function hasPendingRequest(): bool
    {
        return $this->approvalRequests()
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * 最新の申請を取得
     *
     * @return \App\Models\ApprovalRequest|null
     */
    public function getLatestRequest()
    {
        return $this->approvalRequests()
            ->latest()
            ->first();
    }

    /**
     * 勤務中かどうかを判定
     *
     * @return bool
     */
    public function isWorking(): bool
    {
        return $this->status === self::STATUS_WORKING;
    }

    /**
     * 退勤済みかどうかを判定
     *
     * @return bool
     */
    public function hasLeft(): bool
    {
        return $this->status === self::STATUS_LEFT;
    }

    /**
     * 承認待ち状態かどうかを判定
     *
     * @return bool
     */
    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * 承認済みかどうかを判定
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
