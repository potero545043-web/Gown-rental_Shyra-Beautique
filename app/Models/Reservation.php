<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_code',
        'customer_id',
        'created_by',
        'pickup_date',
        'return_date',
        'rental_total',
        'security_deposit_total',
        'late_fee_per_day',
        'discount',
        'grand_total',
        'amount_paid',
        'balance',
        'status',
        'customer_notes',
        'admin_notes',
        'event_date',
        'agreement_version',
        'agreement_accepted_ip',
        'payment_reference_number',
        'measurements',
        'government_id_photo_path',
        'physical_id_photo_path',
        'id_safe_slot',
        'agreement_accepted_at',
        'collateral_status',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'cancellation_refund_amount',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'return_date' => 'date',
        'rental_total' => 'decimal:2',
        'security_deposit_total' => 'decimal:2',
        'late_fee_per_day' => 'decimal:2',
        'discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'agreement_accepted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancellation_refund_amount' => 'decimal:2',
        'event_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items()
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function securityDeposit()
    {
        return $this->hasOne(SecurityDeposit::class);
    }

    public function gownRelease()
    {
        return $this->hasOne(GownRelease::class);
    }

    public function gownReturn()
    {
        return $this->hasOne(GownReturn::class);
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
