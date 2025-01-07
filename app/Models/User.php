<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

    // フルネーム取得用のアクセサ
    public function getFullNameAttribute()
    {
        return "{$this->last_name} {$this->first_name}";
    }

    // 管理者かどうかのチェック
    public function isAdmin()
    {
        return $this->user_type === 'admin';
    }
}
