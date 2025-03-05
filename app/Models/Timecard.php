<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timecard extends Model
{
    use HasFactory;

    /**
     * 勤怠状態の定数定義
     */
    public const STATUS_WORKING = 'working';           // 勤務中
    public const STATUS_LEFT = 'left';                 // 退勤済み
    public const STATUS_PENDING_APPROVAL = 'pending_approval'; // 承認待ち
    public const STATUS_APPROVED = 'approved';         // 承認済み
    public const STATUS_PAID_VACATION = 'paid_vacation'; // 有給休暇 (追加)

    /**
     * 有給休暇種別の定数定義
     */
    public const VACATION_TYPE_FULL = 'full';  // 全日休暇
    public const VACATION_TYPE_AM = 'am';      // 午前半休
    public const VACATION_TYPE_PM = 'pm';      // 午後半休

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
        'vacation_type', // 追加
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
    public function requests(): HasMany
    {
        return $this->hasMany(Request::class, 'timecard_id');
    }

    /**
     * 承認待ちの申請が存在するか確認
     *
     * @return bool
     */
    public function hasPendingRequest(): bool
    {
        return $this->requests()
            ->where('status', Request::STATUS_PENDING)
            ->exists();
    }

    /**
     * 最新の申請を取得
     *
     * @return \App\Models\Request|null
     */
    public function getLatestRequest()
    {
        return $this->requests()
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
    public function isPendingRequest(): bool
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

    /**
     * 有給休暇かどうかを判定
     *
     * @return bool
     */
    public function isPaidVacation(): bool
    {
        return $this->status === self::STATUS_PAID_VACATION;
    }

    /**
     * 有給休暇の種別を取得（全日、午前半休、午後半休）
     *
     * @return string|null 有給休暇の種別、有給休暇でない場合はnull
     */
    public function getVacationType(): ?string
    {
        return $this->isPaidVacation() ? $this->vacation_type : null;
    }

    /**
     * 有給休暇の種別が全日かどうかを判定
     *
     * @return bool
     */
    public function isFullDayVacation(): bool
    {
        return $this->isPaidVacation() && $this->vacation_type === self::VACATION_TYPE_FULL;
    }

    /**
     * 有給休暇の種別が午前半休かどうかを判定
     *
     * @return bool
     */
    public function isAMVacation(): bool
    {
        return $this->isPaidVacation() && $this->vacation_type === self::VACATION_TYPE_AM;
    }

    /**
     * 有給休暇の種別が午後半休かどうかを判定
     *
     * @return bool
     */
    public function isPMVacation(): bool
    {
        return $this->isPaidVacation() && $this->vacation_type === self::VACATION_TYPE_PM;
    }
}
