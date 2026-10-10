<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DamageReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'gown_return_id',
        'reservation_id',
        'gown_id',
        'damage_type',
        'description',
        'repair_cost',
        'discovered_at',
        'estimated_repair_cost',
        'final_repair_cost',
        'severity',
        'photos',
    ];

    protected $casts = [
        'repair_cost' => 'decimal:2',
        'discovered_at' => 'date',
        'estimated_repair_cost' => 'decimal:2',
        'final_repair_cost' => 'decimal:2',
    ];

    public function gownReturn()
    {
        return $this->belongsTo(GownReturn::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function gown()
    {
        return $this->belongsTo(Gown::class);
    }
}
