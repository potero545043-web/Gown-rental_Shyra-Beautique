<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GownReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'processed_by',
        'actual_return_date',
        'actual_return_time',
        'condition_after',
        'late_days',
        'notes',
    ];

    protected $casts = [
        'actual_return_date' => 'date',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function damageReports()
    {
        return $this->hasMany(DamageReport::class);
    }

    public function penalties()
    {
        return $this->hasMany(Penalty::class);
    }

    public function gownPurchases()
    {
        return $this->hasMany(GownPurchase::class);
    }
}
