<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::factory()->create(['name' => '管理部']);
        Department::factory()->create(['name' => '人事部']);
        Department::factory()->create(['name' => '開発部']);
    }
}
