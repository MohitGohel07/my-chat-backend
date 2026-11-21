<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clipper:make-admin {email : The email address of the user} {--password= : Optional new password to set}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant administrator privileges to a user for the Auto Clipper module';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $name = $this->ask('User not found. Enter a name to create a new admin user', 'Admin');
            $password = $this->option('password') ?: $this->secret('Enter a password for the new admin');

            if (empty($password)) {
                $this->error('Password cannot be empty.');
                return Command::FAILURE;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
            ]);

            $this->info("New admin user created successfully: {$email}");
            return Command::SUCCESS;
        }

        $user->is_admin = true;

        if ($password = $this->option('password')) {
            $user->password = Hash::make($password);
            $this->info("Password updated for {$email}.");
        }

        $user->save();

        $this->info("Administrator privileges granted to: {$email}");
        return Command::SUCCESS;
    }
}

