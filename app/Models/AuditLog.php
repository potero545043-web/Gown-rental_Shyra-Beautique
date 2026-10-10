<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        $description = $this->description;

        if ($this->action !== 'reservation.approved' || !$description || !$this->user) {
            return $description;
        }

        return preg_replace(
            '/Approved by .+\\.$/u',
            'Approved by ' . $this->user->name . '.',
            $description
        ) ?? $description;
    }
}
