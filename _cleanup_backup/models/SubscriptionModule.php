<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'module_id',
        'is_active',
        'licensed_at',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'licensed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the module.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Check if the subscription is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the subscription is active and not expired.
     */
    public function getIsValidAttribute(): bool
    {
        return $this->is_active && !$this->is_expired;
    }
}
