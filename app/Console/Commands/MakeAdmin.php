<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MakeAdmin extends Command
{
    protected $signature = 'treinprikker:admin {email} {--password= : Password (generated when omitted)} {--name=Beheerder}';

    protected $description = 'Create or promote an admin user for /admin (HTTP basic auth)';

    public function handle(): int
    {
        $password = $this->option('password') ?: Str::password(20, symbols: false);

        $user = User::updateOrCreate(
            ['email' => $this->argument('email')],
            ['name' => $this->option('name'), 'password' => Hash::make($password), 'is_admin' => true],
        );

        $this->info("Admin {$user->email} is klaar.");
        if (! $this->option('password')) {
            $this->line("Wachtwoord: {$password}");
        }

        return self::SUCCESS;
    }
}
