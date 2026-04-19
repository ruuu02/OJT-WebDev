<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admin_users')->insertOrIgnore([
            'name'       => env('ADMIN_NAME', 'Super Admin'),
            'email'      => env('ADMIN_EMAIL', 'admin@example.com'),
            'password'   => Hash::make(env('ADMIN_PASSWORD', 'changeme')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
