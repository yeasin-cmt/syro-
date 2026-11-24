<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@syro.test'], [
            'name' => 'SYRO Admin',
            'password' => 'Syro@Admin2026',
            'phone' => '01700000000',
        ]);
        $admin->forceFill(['role' => User::ROLE_ADMIN, 'is_active' => true, 'email_verified_at' => now()])->save();

        $customer = User::updateOrCreate(['email' => 'customer@syro.test'], [
            'name' => 'Nusrat Jahan',
            'password' => 'Syro@Customer2026',
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
                'name' => $name, 'password' => 'Syro@Customer2026', 'phone' => $phone, 'city' => $city, 'address' => 'Demo address',
            ]);
        }
    }
}
