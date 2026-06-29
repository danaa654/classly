<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Registrar']);
        Role::firstOrCreate(['name' => 'Dean']);
        Role::firstOrCreate(['name' => 'Assistant Dean']);
        Role::firstOrCreate(['name' => 'OIC']);
    }
}