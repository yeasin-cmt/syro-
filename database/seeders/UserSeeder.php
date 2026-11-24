<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Passwords are never hard-coded: set SEED_ADMIN_PASSWORD in .env, or a random one is generated and printed.
        $adminPassword = env('SEED_ADMIN_PASSWORD') ?: 'Sy-'.Str::random(14);
        $customerPassword = 'Sy-'.Str::random(16);

        $admin = User::updateOrCreate(['email' => 'admin@syro.test'], [
            'name' => 'SYRO Admin',
            'password' => $adminPassword,
            'phone' => '01700000000',
        ]);
        $admin->forceFill(['role' => User::ROLE_ADMIN, 'is_active' => true, 'email_verified_at' => now()])->save();
        $this->command?->warn("Admin login: admin@syro.test / {$adminPassword}  (change it after signing in)");

        $customer = User::updateOrCreate(['email' => 'customer@syro.test'], [
            'name' => 'Nusrat Jahan',
            'password' => $customerPassword,
            'phone' => '01712345678',
            'address' => 'House 21, Road 4, Dhanmondi',
            'city' => 'Dhaka',
        ]);
        $customer->forceFill(['email_verified_at' => now()])->save();

        foreach ([
            ['Tanvir Ahmed', 'tanvir@syro.test', '01812345678', 'Chattogram'],
            ['Farhana Akter', 'farhana@syro.test', '01912345678', 'Dhaka'],
            ['Rafiul Islam', 'rafiul@syro.test', '01612345678', 'Sylhet'],
        ] as [$name, $email, $phone, $city]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name, 'password' => $customerPassword, 'phone' => $phone, 'city' => $city, 'address' => 'Demo address',
            ]);
        }
    }
}
