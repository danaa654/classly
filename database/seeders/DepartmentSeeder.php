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
                'abbreviation' => 'CCS',
            ],
            [
                'code' => 'CRIM',
                'name' => 'College of Criminal Justice',
                'abbreviation' => 'CRIM',
            ],
            [
                'code' => 'CTE',
                'name' => 'College of Teacher Education',
                'abbreviation' => 'CTE',
            ],
            [
                'code' => 'SHTM',
                'name' => 'School of Hospitality and Tourism Management',
                'abbreviation' => 'SHTM',
            ],
        ]);
    }
}