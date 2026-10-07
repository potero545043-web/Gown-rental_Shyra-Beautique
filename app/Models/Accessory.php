<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accessory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'quantity',
        'replacement_cost',
        'status',
    ];

    protected $casts = [
        'replacement_cost' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function gowns()
    {
        return $this->belongsToMany(
            Gown::class,
            'gown_accessories'
        )->withPivot('quantity');
    }

    public function gownAccessories()
    {
        return $this->hasMany(GownAccessory::class);
    }
}
