<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Transaction;
use App\Models\VirtualCard;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@jokko.com",
                'password' => Hash::make('password'),
                'phone_number' => "7712345{$i}",
                'id_document_type' => 'national_id',
                'id_document_number' => "ID{$i}",
                'id_document_path' => "documents/test-id-{$i}.jpg",
                'facial_recognition_id' => "FACE-{$i}",
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);
        }
       
        //TODO: We need to extract transfer fees if transaction type is transfer
        // Create transactions between users
        $transactionTypes = [Transaction::TYPE_TRANSFER, Transaction::TYPE_RECHARGE];
        for ($i = 0; $i < 50; $i++) {
            $type = $transactionTypes[array_rand($transactionTypes)];
            $sender = $users[array_rand($users)];
            $receiver = $type === Transaction::TYPE_TRANSFER 
                ? $users[array_rand($users)] 
                : null;
            // Ensure sender and receiver are not the same for transfers
            if ($type === Transaction::TYPE_TRANSFER && $sender->id === $receiver->id) {
                continue;
            }

            $transaction = Transaction::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver ? $receiver->id : null,
                'amount' => rand(10, 500),
                'type' => $type,
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => Transaction::generateReference(),
                'description' => $type === Transaction::TYPE_TRANSFER 
                    ? 'Test transfer between users' 
                    : 'Test wallet recharge',
                'created_at' => now()->subHours(rand(1, 720)), // Random time in last 30 days
            ]);

            // Create notifications for transactions
            if ($type === Transaction::TYPE_TRANSFER) {
                $message = "You have successfully transferred {$transaction->amount} to {$receiver->name}";
                Notification::createTransactionNotification($sender, $transaction, $message);
                $message = "You have received {$transaction->amount} from {$sender->name}";
                Notification::createTransactionNotification($receiver, $transaction, $message);
            } else {
                $message = "Your wallet has been recharged with {$transaction->amount}";
                Notification::createTransactionNotification($sender, $transaction, $message);
                Notification::createTransactionNotification($receiver, $transaction, $message);
            }
        }

        // Create some system notifications
        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => Notification::TYPE_SYSTEM,
                    'title' => 'System Update',
                    'message' => 'This is a test system notification.',
                    'created_at' => now()->subHours(rand(1, 720)),
                ]);
            }
        }
    }
}
