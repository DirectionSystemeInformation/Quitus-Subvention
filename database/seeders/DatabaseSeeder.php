<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'dshn@sports.gov.bf'],
            [
                'name' => 'Agent DSHN',
                'password' => bcrypt('password'),
                'role' => 'dshn',
                'status' => 'active',
            ]
        );
    }
}
