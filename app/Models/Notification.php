<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    const TYPE_TRANSACTION = 'transaction';
    const TYPE_AUTHENTICATION = 'authentication';
    const TYPE_SYSTEM = 'system';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    // Helper methods
    public function markAsRead()
    {
        $this->read_at = now();
        $this->save();
    }

    public function isRead()
    {
        return $this->read_at !== null;
    }

    public static function createSystemNotification($user, $title, $message, $data = [])
    {
        return self::create([
            'user_id' => $user->id,
            'type' => self::TYPE_SYSTEM,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
    /**
     * Create a transaction notification.
     *
     * @param User $user
     * @param Transaction $transaction
     * @param User|null $sender
     * @param User|null $receiver
     * @return Notification
     */
    public static function createTransactionNotification($user, $transaction, $message = null)
    {
        // If a message is provided, use it; otherwise, construct a default message
        if (!$message) {
            $message = "Transaction of {$transaction->amount} has been {$transaction->status}";
        }

        // If the user is not the sender or receiver, return null
        if ($user->id !== $transaction->sender_id && $user->id !== $transaction->receiver_id) {
            return null;
        }

        // Create the notification
        return self::create([
            'user_id' => $user->id,
            'type' => self::TYPE_TRANSACTION,
            'title' => 'New Transaction',
            'message' => $message,
            // Include the transaction ID in the data for reference
            // 'identifier' => $transaction->reference,
            'data' => ['transaction_id' => $transaction->id],
        ]);
    }

    public static function createAuthenticationNotification($user, $message)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => self::TYPE_AUTHENTICATION,
            'title' => 'Authentication Update',
            'message' => $message,
        ]);
    }
} 