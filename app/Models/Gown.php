<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gown extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'gown_code',
        'name',
        'size',
        'color',
        'style',
        'rental_price',
        'security_deposit',
        'purchase_price',
        'description',
        'measurements',
        'image',
        'condition',
        'status',
        'date_purchased',
        'archived_at',
        'archived_status',
    ];

    protected $casts = [
        'rental_price' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'date_purchased' => 'date',
        'archived_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reservationItems()
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function accessories()
    {
        return $this->belongsToMany(
            Accessory::class,
            'gown_accessories'
        )->withPivot('quantity');
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function cleaningRecords()
    {
        return $this->hasMany(CleaningRecord::class);
    }

    public function damageReports()
    {
        return $this->hasMany(DamageReport::class);
    }
}
