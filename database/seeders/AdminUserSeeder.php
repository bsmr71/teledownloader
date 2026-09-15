<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'bismar71@gmail.com'],
            [
                'name' => 'Bismar Admin',
                'password' => Hash::make('zabuaz71'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
    }
}
