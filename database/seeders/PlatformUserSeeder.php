<?php

namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformUserSeeder extends Seeder
{
    public function run()
    {
        User::firstOrCreate(
            ['email' => config('wallet.email', 'platform@jokko.com')],
            [
                'name' => config('wallet.name', 'JokkoPlatform'),
                'password' => Hash::make(config('wallet.password', 'Jokko2025')),
                'phone_number' => config('wallet.phone_number', '771230000'),
            ]
        );
        
    }
}
