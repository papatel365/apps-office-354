<?php

namespace App\Exports;

/**
 * Leave Report Export
 */
class LeaveExport extends BaseExport
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
    protected $title = 'Cuti';

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
        $this->headers = ['No', 'ID Karyawan', 'Nama Karyawan', 'Jenis Cuti', 'Tanggal Mulai', 'Tanggal Selesai', 'Lama (Hari)', 'Keterangan', 'Status'];

        // Prepare rows
        $this->prepareRows();
    }

    /**
     * Prepare data rows
     */
    protected function prepareRows()
    {
        $statusLabels = [
            'approved' => 'Disetujui',
            'pending' => 'Menunggu',
            'rejected' => 'Ditolak',
        ];

        $no = 1;
        foreach ($this->data['leaves'] ?? [] as $leave) {
            $status = $statusLabels[$leave['status']] ?? ucfirst($leave['status'] ?? '');
            $startDate = !empty($leave['start_date']) ? self::formatDate($leave['start_date']) : '-';
            $endDate = !empty($leave['end_date']) ? self::formatDate($leave['end_date']) : '-';

            $this->rows[] = [
                $no++,
                $leave['employee_id'] ?? '-',
                $leave['employee_name'] ?? '-',
                $leave['leave_type'] ?? '-',
                $startDate,
                $endDate,
                $leave['total_days'] ?? 0,
                $leave['reason'] ?? '-',
                $status,
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
