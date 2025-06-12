<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'amount',
        'type',
        'status',
        'reference',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    const TYPE_TRANSFER = 'transfer';
    const TYPE_TRANSFER_FEE = 'transfer-fee';
    const TYPE_RECHARGE = 'recharge';
    const TYPE_WITHDRAWAL = 'withdrawal';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // Relationships
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Scopes for cursor pagination
    public function scopeFromCursor($query, $cursor)
    {
        return $query->where('created_at', '<', $cursor)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    // Helper methods
    public static function generateReference()
    {
        return 'TRX-' . strtoupper(uniqid()) . '-' . time();
    }

    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }

    public function markAsFailed()
    {
        $this->status = self::STATUS_FAILED;
        $this->save();
    }
} 