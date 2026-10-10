<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GownPurchase extends Model
{
    protected $fillable = [
        'reservation_id',
        'gown_return_id',
        'gown_id',
        'processed_by',
        'amount',
        'purchased_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'purchased_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function gownReturn()
    {
        return $this->belongsTo(GownReturn::class);
    }

    public function gown()
    {
        return $this->belongsTo(Gown::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
