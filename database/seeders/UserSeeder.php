<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat akun Admin
        User::create([
            'name' => 'Admin AoraNema',
            'email' => 'admin@aoranema.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Buat akun Penonton
        User::create([
            'name' => 'Penonton Setia',
            'email' => 'user@aoranema.com',
            'password' => Hash::make('password123'),
        ]);
    }
}
