<?php

namespace App\Exports;

use App\Http\Resources\EmployeeReportResource;

/**
 * Employee Report Export
 *
 * Uses EmployeeReportResource to ensure all data is flat strings (no JSON objects)
 */
class EmployeeExport extends BaseExport
{
    /**
     * @var array Report data
     */
    protected $data;

    /**
     * @var array Generator info
     */
    protected $generatorInfo;

    /**
     * @var string Title
     */
    protected $title = 'Karyawan';

    /**
     * Create new export instance
     */
    public function __construct(array $data, array $generatorInfo)
    {
        $this->data = $data;
        $this->generatorInfo = $generatorInfo;
        $this->companyName = $generatorInfo['company_name'] ?? 'Office 354';

        // Define headers - matching EmployeeReportResource keys
        $this->headers = [
            'No',
            'ID Karyawan',
            'Nama Lengkap',
            'Email',
            'No. HP',
            'Departemen',
            'Divisi',
            'Jabatan',
            'Tipe Karyawan',
            'Status',
            'Tgl Bergabung',
        ];

        // Prepare rows
        $this->prepareRows();
    }

    /**
     * Prepare data rows
     * IMPORTANT: All values are strings, extracted from EmployeeReportResource
     */
    protected function prepareRows()
    {
        $no = 1;
        $employees = $this->data['employees'] ?? [];

        // Use EmployeeReportResource to ensure consistent flat data
        $formattedEmployees = EmployeeReportResource::collection($employees);

        foreach ($formattedEmployees as $emp) {
            // Format join date
            $joinDate = $emp['join_date_formatted'] ?? '-';

            // Status label
            $statusLabel = $emp['is_active'] ? 'Aktif' : 'Nonaktif';

            $this->rows[] = [
                $no++,
                $emp['employee_id'] ?? '-',
                $emp['full_name'] ?? '-',
                $emp['email'] ?? '-',
                $emp['phone'] ?? '-',
                $emp['department'] ?? '-',      // String, not object
                $emp['division'] ?? '-',       // String, not object
                $emp['position'] ?? '-',       // String, not object
                $emp['employee_type'] ?? '-',  // String, not object
                $statusLabel,
                $joinDate,
            ];
        }
    }

    /**
     * Generate the export
     */
    public function export()
    {
        return $this->generateHtml();
    }
}
