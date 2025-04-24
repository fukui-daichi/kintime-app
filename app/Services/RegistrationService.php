<?php

namespace App\Services;

use App\Models\Department;

class RegistrationService
{
    /**
     * 登録フォーム用の部署一覧を取得
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDepartments()
    {
        return Department::orderBy('name')->get();
    }

    /**
     * 登録フォーム用の雇用形態一覧を取得
     *
     * @return array
     */
    public function getEmploymentTypes()
    {
        return [
            'full_time' => '正社員',
            'contract' => '契約社員',
            'part_time' => 'パートタイム',
            'temporary' => '派遣社員'
        ];
    }
}
