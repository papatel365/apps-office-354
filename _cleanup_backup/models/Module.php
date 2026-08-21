<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory, SoftDeletes;

    // Categories
    const CATEGORY_SALES = 'sales';
    const CATEGORY_HRD = 'hrd';
    const CATEGORY_FINANCE = 'finance';
    const CATEGORY_ISP = 'isp';
    const CATEGORY_FNBR = 'fnbr';
    const CATEGORY_RETAIL = 'retail';
    const CATEGORY_MANUFACTURING = 'manufacturing';
    const CATEGORY_HEALTHCARE = 'healthcare';
    const CATEGORY_EDUCATION = 'education';
    const CATEGORY_PROPERTY = 'property';
    const CATEGORY_AI = 'ai';
    const CATEGORY_REPORTING = 'reporting';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'version',
        'category',
        'price',
        'is_active',
        'is_featured',
        'is_promo',
        'promo_price',
        'icon',
        'documentation_url',
        'support_url',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_promo' => 'boolean',
        'price' => 'decimal:2',
        'promo_price' => 'decimal:2',
        'settings' => 'array',
    ];

    /**
     * Get the companies that have this module.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'subscription_modules')
            ->withPivot(['expires_at', 'is_active', 'licensed_at'])
            ->withTimestamps();
    }

    /**
     * Get subscription modules.
     */
    public function subscriptionModules()
    {
        return $this->hasMany(SubscriptionModule::class);
    }

    /**
     * Scope for active modules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured modules.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for promo modules.
     */
    public function scopePromo($query)
    {
        return $query->where('is_promo', true);
    }

    /**
     * Scope ordered by category and name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('category')->orderBy('name');
    }

    /**
     * Get current price (considering promo).
     */
    public function getCurrentPriceAttribute(): float
    {
        return $this->is_promo && $this->promo_price ? (float) $this->promo_price : (float) $this->price;
    }

    /**
     * Check if module has a promo.
     */
    public function getHasPromoAttribute(): bool
    {
        return $this->is_promo && $this->promo_price && $this->promo_price < $this->price;
    }
}
