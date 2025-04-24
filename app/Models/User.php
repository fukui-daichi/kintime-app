<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // 基本情報
        'employee_number',
        'last_name',
        'first_name',
        'department_id',
        'employment_type',
        'role',
        'is_active',
        'joined_at',
        'leaved_at',
        'email',
        'password'
    ];

    /**
     * フルネーム取得
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->last_name} {$this->first_name}";
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'department_id' => 'integer',
            'is_active' => 'boolean',
            'joined_at' => 'date',
            'leaved_at' => 'date',
        ];
    }

    /**
     * 所属部署
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * ユーザー種別を取得
     */
    public function getUserType(): string
    {
        return $this->role;
    }

    /**
     * 管理者判定
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * 上長（マネージャー）判定
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * 一般ユーザー判定
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * アカウント有効判定
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * 在籍中判定
     */
    public function isEmployed(): bool
    {
        if (!$this->joined_at) {
            return false;
        }
        $today = now()->toDateString();
        if ($this->leaved_at && $this->leaved_at < $today) {
            return false;
        }
        return $this->joined_at <= $today;
    }
}
