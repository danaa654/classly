<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::insert([
            [
                'code' => 'CCS',
                'name' => 'College of Computer Studies',
                'short_name' => 'CCS',
            ],
            [
                'code' => 'CRIM',
                'name' => 'College of Criminal Justice',
                'short_name' => 'CRIM',
            ],
            [
                'code' => 'CTE',
                'name' => 'College of Teacher Education',
                'short_name' => 'CTE',
            ],
            [
                'code' => 'SHTM',
                'name' => 'School of Hospitality and Tourism Management',
                'short_name' => 'SHTM',
            ],
        ]);
    }
}