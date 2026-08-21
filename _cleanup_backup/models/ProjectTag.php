<?php

namespace App\Models;

use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTag extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'name',
        'color',
    ];

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getHtmlAttribute(): string
    {
        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background-color: ' . $this->color . '20; color: ' . $this->color . ';">' . e($this->name) . '</span>';
    }
}
