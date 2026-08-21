<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Employee Report Resource
 *
 * Provides consistent, flat data for all employee exports (PDF, Excel, Word, Print)
 * This ensures NO JSON objects appear in any export format.
 *
 * Can handle both Model objects and arrays (for already-formatted data)
 */
class EmployeeReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle both object (Model) and array input
        $employee = $this->resource;

        if (is_array($employee)) {
            // Already formatted array - just return it
            return $employee;
        }

        // Model object - extract properties
        return [
            // Basic Info
            'employee_id' => $employee->employee_number ?? '-',
            'full_name' => $employee->full_name ?? '-',
            'email' => $employee->user?->email ?? '-',
            'phone' => $employee->phone ?? '-',

            // Relations - ALWAYS extract name only
            'department' => $employee->department?->name ?? '-',
            'division' => $employee->division?->name ?? '-',
            'position' => $employee->position?->name ?? '-',
            'employee_type' => $employee->employeeType?->name ?? '-',
            'placement' => $employee->placement?->name ?? '-',

            // Status - Aktif/Resign
            'is_active' => $employee->is_active ?? false,
            'status_label' => ($employee->is_active ?? false) ? 'Aktif' : 'Resign',

            // Employment Type
            'employment_type' => $employee->employment_type ?? '-',
            'employment_type_label' => $this->getEmploymentTypeLabel($employee->employment_type ?? ''),

            // Dates
            'join_date' => $employee->join_date,
            'join_date_formatted' => $employee->join_date ? \Carbon\Carbon::parse($employee->join_date)->format('d/m/Y') : '-',
            'birth_date' => $employee->birth_date,
            'birth_date_formatted' => $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date)->format('d/m/Y') : '-',
            'age' => $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date)->age : null,

            // Resign Info
            'resign_date' => $employee->resign_date ?? null,
            'resign_date_formatted' => $employee->resign_date ? \Carbon\Carbon::parse($employee->resign_date)->format('d/m/Y') : null,
            'resign_reason' => $employee->resign_reason ?? null,
        ];
    }

    /**
     * Get employment type label
     */
    protected function getEmploymentTypeLabel(string $type): string
    {
        return match ($type) {
            'permanent' => 'Karyawan Tetap',
            'contract' => 'Kontrak',
            'probation' => 'Masa Percobaan',
            'internship' => 'Magang',
            'freelance' => 'Freelance',
            'part_time' => 'Part-time',
            'outsource' => 'Outsource',
            default => $type ? ucfirst($type) : '-',
        };
    }

    /**
     * Get a collection of resources with transformed data
     */
    public static function collection($resource): array
    {
        // If already an array, return as is
        if (is_array($resource)) {
            return $resource;
        }

        return $resource->map(function ($employee) {
            // If already an array, return it directly
            if (is_array($employee)) {
                return $employee;
            }
            return (new static($employee))->resolve();
        })->toArray();
    }
}
