<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PullpreviewSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create(['name' => 'Transmorpher Amigor', 'email' => 'transmorpher.amigor@example.com', 'password' => 'password']);
    }
}
