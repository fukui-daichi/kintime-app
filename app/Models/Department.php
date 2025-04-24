<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    /**
     * この部署に所属するユーザー一覧
     */
    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
