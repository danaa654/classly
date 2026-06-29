<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Registrar']);
        Role::create(['name' => 'Dean']);
        Role::create(['name' => 'Assistant Dean']);
        Role::create(['name' => 'OIC']);
    }
}