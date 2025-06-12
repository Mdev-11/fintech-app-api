<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
//translate all text in this file to french except comments
class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'is_active',
        'settings',
    ];

    protected $casts = [
        // 'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

   
    public function allTransactions()
    {
        return Transaction::where(function ($query) {
            $query->where('sender_id', $this->id)
                ->orWhere('receiver_id', $this->id);
        });
    }
    public function scopeUserTransactions($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId);
        });
    }
    public function virtualCard()
    {
        return $this->hasOne(VirtualCard::class);
    }
    // Helper methods
    public function credit($amount)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        return DB::transaction(function () use ($amount) {
            $this->balance += $amount;
            $this->save();
            return $this->balance;
        });
    }

    public function debit($amount)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        if ($this->balance < $amount) {
            throw new \InvalidArgumentException('Insufficient balance');
        }

        return DB::transaction(function () use ($amount) {
            $this->balance -= $amount;
            $this->save();
            return $this->balance;
        });
    }

    public function hasEnoughBalance($amount)
    {
        return $this->balance >= $amount;
    }

    public function transfer(Wallet $recipient, $amount, $metadata = [])
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        if (!$this->hasEnoughBalance($amount)) {
            throw new \InvalidArgumentException('Insufficient balance');
        }

        return DB::transaction(function () use ($recipient, $amount, $metadata) {
            $this->debit($amount);
            $recipient->credit($amount);
            $description = $metadata['description'] ?? "Transfer to {$recipient->user->name}";
            $transaction = Transaction::create([
                'sender_id' => $this->user_id,
                'receiver_id' => $recipient->user_id,
                'amount' => $amount,
                'type' => $metadata['type'] ?? Transaction::TYPE_TRANSFER,
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => Transaction::generateReference(),
                'description' => $description,
            ]);
            $message = "You have successfully transferred {$amount} to {$recipient->user->name}";
            // Notify the sender
            Notification::createTransactionNotification($this->user, $transaction, $message);
            $message = "You have received {$amount} from {$this->user->name}";
            // Notify the recipient
            Notification::createTransactionNotification($recipient->user, $transaction, $message);

            return $transaction;
        });
    }

    public function recharge($amount, $paymentMethod, $paymentDetails)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        return DB::transaction(function () use ($amount, $paymentMethod, $paymentDetails) {
            $transaction = Transaction::create([
                'sender_id' => $this->user_id,
                'amount' => $amount,
                'type' => Transaction::TYPE_RECHARGE,
                'status' => Transaction::STATUS_PENDING,
                'reference' => Transaction::generateReference(),
                'description' => 'Wallet recharge',
                'metadata' => [
                    'payment_method' => $paymentMethod,
                    'payment_details' => $paymentDetails,
                ],
            ]);

            // TODO: Integrate with payment gateway
            // For now, we'll simulate a successful payment
            $this->credit($amount);
            $transaction->markAsCompleted();
            // Notify the user about the transaction
            $message = "You have successfully recharged your wallet with {$amount}";
            Notification::createTransactionNotification($this->user, $transaction, $message);

            return $transaction;
        });
    }

    public function withdraw($amount, $withdrawalMethod, $withdrawalDetails)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        if (!$this->hasEnoughBalance($amount)) {
            throw new \InvalidArgumentException('Insufficient balance');
        }

        return DB::transaction(function () use ($amount, $withdrawalMethod , $withdrawalDetails) {
            $transaction = Transaction::create([
                'sender_id' => $this->user_id,
                'amount' => $amount,
                'type' => Transaction::TYPE_WITHDRAWAL,
                'status' => Transaction::STATUS_PENDING,
                'reference' => Transaction::generateReference(),
                'description' => 'Wallet withdrawal',
                'metadata' => [
                    'withdrawal_method' => $withdrawalMethod ?? 'none',
                    'withdrawal_details' => $withdrawalDetails ?? [],
                ],
            ]);

            // TODO: Integrate with payment gateway for withdrawal
            // For now, we'll simulate a successful withdrawal
            $this->debit($amount);
            $transaction->markAsCompleted();
            // Notify the user about the transaction
            $message = "You have successfully withdrawn {$amount} from your wallet";
            Notification::createTransactionNotification($this->user, $transaction, $message);

            return $transaction;
        });
    }
    protected static function booted()
    {
        static::created(function (self $wallet) {
            // Create a wallet for new users
            $wallet->virtualCard()->create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'status' => VirtualCard::STATUS_ACTIVE,
            ]);
        });
    }
} 