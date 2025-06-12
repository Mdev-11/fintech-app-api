<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualCard extends Model
{
    use HasFactory;
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    protected $fillable = ['wallet_id', 'uuid', 'status'];




    protected $casts = [
    ];

    // Relationships
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
    // Helper methods
    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    public function getMaskedCardNumber()
    {
        return substr($this->card_number, 0, 4) . ' **** **** ' . substr($this->card_number, -4);
    }

    public function isExpired()
    {
        $expiryDate = \Carbon\Carbon::createFromDate($this->expiry_year, $this->expiry_month, 1)->endOfMonth();
        return $expiryDate->isPast();
    }
    
} 