<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ChatUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('chat.seed_users')) {
            $this->command?->info('Chat user seeding is disabled. Set CHAT_SEED_USERS=true to enable it.');
            return;
        }

        $password = config('chat.seed_password');
        if (! is_string($password) || strlen($password) < 6) {
            throw new RuntimeException('CHAT_SEED_PASSWORD must be set and contain at least 6 characters.');
        }

        $users = [
            ['name' => 'Mohit', 'email' => config('chat.seed_mohit_email')],
            ['name' => 'Mohit 2', 'email' => config('chat.seed_mohit2_email')],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make($password)],
            );
        }

        $this->command?->info('Chat seed users are ready.');
    }
}
