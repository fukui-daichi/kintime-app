<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'user_type',
        'hire_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'hire_date' => 'date',
        'password' => 'hashed',
    ];

    // 勤怠データとのリレーション
    public function timecards()
    {
        return $this->hasMany(Timecard::class);
    }

    // フルネーム取得用のアクセサ
    public function getFullNameAttribute()
    {
        return "{$this->last_name} {$this->first_name}";
    }

    /**
     * 申請者としての申請リレーション
     * ユーザーが申請者として作成した申請一覧を取得
     */
    public function requestsAsApplicant(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'user_id');
    }

    /**
     * 承認者としての申請リレーション
     * ユーザーが承認者として受け取った申請一覧を取得
     */
    public function requestsAsApprover(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'approver_id');
    }

    /**
     * 管理者かどうかを判定
     */
    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }
}
