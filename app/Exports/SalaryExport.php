<?php

namespace App\Exports;

/**
 * Salary Report Export
 */
class SalaryExport extends BaseExport
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
     * @var string Period
     */
    protected $period;

    /**
     * @var string Title
     */
    protected $title = 'Gaji';

    /**
     * Create new export instance
     */
    public function __construct(array $data, array $generatorInfo, string $period)
    {
        $this->data = $data;
        $this->generatorInfo = $generatorInfo;
        $this->period = $period;
        $this->companyName = $generatorInfo['company_name'] ?? 'Office 354';

        // Define headers
        $this->headers = ['No', 'ID Karyawan', 'Nama Karyawan', 'Departemen', 'Jabatan', 'Gaji Pokok', 'Tunjangan', 'Potongan', 'Total Gaji', 'Status'];

        // Prepare rows
        $this->prepareRows();
    }

    /**
     * Prepare data rows
     */
    protected function prepareRows()
    {
        $no = 1;
        foreach ($this->data['salaries'] ?? [] as $salary) {
            $statusLabel = ($salary['payment_status'] ?? '') === 'paid' ? 'Lunas' : 'Menunggu';

            $this->rows[] = [
                $no++,
                $salary['employee_id'] ?? '-',
                $salary['employee_name'] ?? '-',
                $salary['department'] ?? '-',
                $salary['position'] ?? '-',
                self::formatCurrency($salary['basic_salary'] ?? 0),
                self::formatCurrency($salary['allowances'] ?? 0),
                self::formatCurrency($salary['deductions'] ?? 0),
                self::formatCurrency($salary['total_salary'] ?? 0),
                $statusLabel,
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
