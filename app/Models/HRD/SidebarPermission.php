<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee Sidebar Permission Model
 * Stores per-employee sidebar menu permissions.
 * Used for fine-grained control over which menu items each employee can see.
 * This is separate from the existing sidebar_permissions table which uses user_id.
 */
class SidebarPermission extends Model
{
    use BelongsToCompany;

    protected $table = 'employee_sidebar_permissions';

    protected $fillable = [
        'employee_id',
        'company_id',
        'menu_key',
        'can_view',
    ];

    protected $casts = [
        'can_view' => 'boolean',
    ];

    /**
     * Get the employee profile that owns this permission.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    /**
     * Scope to get permissions for a specific employee.
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to get only enabled permissions.
     */
    public function scopeEnabled($query)
    {
        return $query->where('can_view', true);
    }

    /**
     * Scope to get only disabled permissions.
     */
    public function scopeDisabled($query)
    {
        return $query->where('can_view', false);
    }

    /**
     * Check if a menu key is enabled for this permission.
     */
    public function isEnabled(): bool
    {
        return $this->can_view === true;
    }
}
