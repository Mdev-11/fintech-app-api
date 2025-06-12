<?php
// config/wallet.php


return [
    'currency' => 'XOF', // West African CFA Franc
    'phone_number' => '771230000', // Platform phone number for wallet transactions
    'email' => 'platform@jokko.com',
    'name' => 'Jokko Wallet', // Name of the wallet
    'transfer_fee_percent' => 1.0, // 1%
    'name' => 'JokkoPlatform',
    'password' => 'Jokko2025',

    //The following line was causing the issue: Route facade has not been set
    // 'password' => Hash::make('Jokko2025'),
];
