<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'logo_url',
        'description',
        'is_active',
        'is_default',
        'is_sandbox',
        'supported_channels',
        'fees',
        'limits',
        'credentials',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_sandbox' => 'boolean',
        'supported_channels' => 'array',
        'fees' => 'array',
        'limits' => 'array',
        'credentials' => 'array',
    ];
}
