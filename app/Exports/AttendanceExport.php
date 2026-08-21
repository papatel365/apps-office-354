<?php

namespace App\Exports;

use Carbon\Carbon;

/**
 * Attendance Report Export - Payroll Period Format
 *
 * Format: 29th of previous month to 28th of selected month
 *
 * Structure:
 *   - Row 1: RECAP ABSEN (merged, bold, center, yellow background)
 *   - Row 2: Period (e.g., "Agustus 2026")
 *   - Header Row: Nama | 29-Jul | 30-Jul | ... | 28-Agu | Total
 *   - Data Rows: One row per employee with attendance status per date
 *
 * Status:
 *   - H = Hadir
 *   - A = Alfa / Tidak Absen
 */
class AttendanceExport extends BaseExport
{
    /**
     * @var array Report data from AttendanceReportService::getAttendanceMatrix()
     */
    protected $data;

    /**
     * @var array Generator info
     */
    protected $generatorInfo;

    /**
     * @var string Period string (label only, e.g., "Agustus 2026")
     */
    protected $period;

    /**
     * @var string Title
     */
    protected $title = 'RECAP ABSEN';

    /**
     * @var array Period dates
     */
    protected $periodDates = [];

    /**
     * @var array Employee attendance matrix
     */
    protected $employees = [];

    /**
     * Create new export instance
     */
    public function __construct(array $data, array $generatorInfo, string $period)
    {
        $this->data = $data;
        $this->generatorInfo = $generatorInfo;
        $this->period = $period;

        // Extract data
        $this->employees = $data['employees'] ?? [];
        $this->periodDates = $data['periodDates'] ?? [];

        $this->companyName = $generatorInfo['company_name'] ?? 'Office 354';
    }

    /**
     * Generate the export
     */
    public function export()
    {
        return $this->generateHtml();
    }

    /**
     * Generate HTML for Excel with payroll period format
     */
    public function generateHtml(): string
    {
        // Count total columns: Nama, dates, Total (no NIP)
        $totalColumns = 1 + count($this->periodDates) + 1;

        // Header row 1: Title
        $titleHtml = '<tr>
            <th colspan="' . $totalColumns . '" style="
                background-color: #fbbf24;
                color: #000000;
                font-weight: bold;
                font-size: 16pt;
                text-align: center;
                vertical-align: middle;
                padding: 12px;
                border: 1px solid #d97706;
            ">RECAP ABSEN</th>
        </tr>';

        // Header row 2: Period (just month name, e.g., "Agustus 2026")
        $periodText = $this->period ?? 'Periode tidak tersedia';
        $periodHtml = '<tr>
            <th colspan="' . $totalColumns . '" style="
                background-color: #fef3c7;
                color: #92400e;
                font-size: 11pt;
                text-align: center;
                vertical-align: middle;
                padding: 8px;
                border: 1px solid #d97706;
            ">Periode: ' . htmlspecialchars($periodText) . '</th>
        </tr>';

        // Header row 3: Column headers (Nama + dates + Total)
        $headerHtml = '<tr>
            <th style="
                background-color: #374151;
                color: #ffffff;
                font-weight: bold;
                font-size: 9pt;
                text-align: left;
                vertical-align: middle;
                padding: 6px 8px;
                border: 1px solid #4b5563;
                width: 180px;
            ">Nama</th>';

        foreach ($this->periodDates as $dateInfo) {
            $dayLabel = $dateInfo['dayName'] ?? '';
            $isWeekend = $dateInfo['isWeekend'] ?? false;

            // Highlight weekends
            $bgColor = $isWeekend ? '#6b7280' : '#374151';
            $textColor = '#ffffff';

            $headerHtml .= '<th style="
                background-color: ' . $bgColor . ';
                color: ' . $textColor . ';
                font-weight: bold;
                font-size: 7pt;
                text-align: center;
                vertical-align: middle;
                padding: 4px 2px;
                border: 1px solid #4b5563;
                width: 32px;
                min-width: 32px;
            ">' . htmlspecialchars($dayLabel) . '</th>';
        }

        // Total column header
        $headerHtml .= '<th style="
            background-color: #1e40af;
            color: #ffffff;
            font-weight: bold;
            font-size: 9pt;
            text-align: center;
            vertical-align: middle;
            padding: 6px 4px;
            border: 1px solid #1e3a8a;
            width: 50px;
        ">Total</th>
        </tr>';

        // Data rows
        $rowsHtml = '';
        if (!empty($this->employees)) {
            $rowIndex = 0;
            foreach ($this->employees as $emp) {
                $rowIndex++;
                $bgColor = ($rowIndex % 2 === 0) ? '#f9fafb' : '#ffffff';

                $name = htmlspecialchars($emp['name'] ?? '-');

                $rowsHtml .= '<tr>
                    <td style="
                        background-color: ' . $bgColor . ';
                        padding: 5px 8px;
                        border: 1px solid #d1d5db;
                        text-align: left;
                        font-size: 9pt;
                        font-family: Calibri, Arial, sans-serif;
                        vertical-align: middle;
                    ">' . $name . '</td>';

                // Attendance cells for each date
                foreach ($this->periodDates as $dateInfo) {
                    $dateString = $dateInfo['dateString'];
                    $isWeekend = $dateInfo['isWeekend'] ?? false;
                    $status = $emp['attendance'][$dateString] ?? 'A';

                    // Determine cell styling based on status
                    $cellBg = $bgColor;
                    $cellColor = '#333333';
                    $cellFontWeight = 'normal';

                    if ($isWeekend) {
                        // Weekend - gray background
                        $cellBg = '#e5e7eb';
                        $cellColor = '#6b7280';
                    } elseif ($status === 'H') {
                        // Hadir - green
                        $cellBg = '#dcfce7';
                        $cellColor = '#166534';
                        $cellFontWeight = 'bold';
                    } elseif ($status === 'A') {
                        // Alfa/Tidak Absen - red
                        $cellBg = '#fee2e2';
                        $cellColor = '#dc2626';
                    }

                    $rowsHtml .= '<td style="
                        background-color: ' . $cellBg . ';
                        color: ' . $cellColor . ';
                        font-weight: ' . $cellFontWeight . ';
                        padding: 4px 2px;
                        border: 1px solid #d1d5db;
                        text-align: center;
                        font-size: 8pt;
                        font-family: Calibri, Arial, sans-serif;
                        vertical-align: middle;
                    ">' . htmlspecialchars($status) . '</td>';
                }

                // Total cell - count of Hadir (H) only
                $totalHadir = $emp['total_hadir'] ?? 0;
                $rowsHtml .= '<td style="
                    background-color: #eff6ff;
                    color: #1e40af;
                    font-weight: bold;
                    padding: 5px 4px;
                    border: 1px solid #d1d5db;
                    text-align: center;
                    font-size: 9pt;
                    font-family: Calibri, Arial, sans-serif;
                    vertical-align: middle;
                ">' . $totalHadir . '</td>
                </tr>';
            }
        } else {
            $rowsHtml = '<tr><td colspan="' . $totalColumns . '" style="
                text-align: center;
                padding: 30px 15px;
                font-style: italic;
                color: #666666;
                background-color: #f9fafb;
            ">Tidak ada data karyawan.</td></tr>';
        }

        // Legend row - simplified to only H and A
        $legendHtml = '<tr>
            <td style="
                background-color: #f3f4f6;
                color: #374151;
                font-size: 8pt;
                padding: 6px 8px;
                border: 1px solid #d1d5db;
                font-weight: bold;
                width: 180px;
            ">Keterangan:</td>';

        // H = Hadir
        $legendHtml .= '<td style="
            background-color: #dcfce7;
            color: #166534;
            font-size: 8pt;
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            text-align: center;
            font-family: Calibri, Arial, sans-serif;
            font-weight: bold;
        ">H = Hadir</td>';

        // A = Alfa / Tidak Absen
        $legendHtml .= '<td style="
            background-color: #fee2e2;
            color: #dc2626;
            font-size: 8pt;
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            text-align: center;
            font-family: Calibri, Arial, sans-serif;
            font-weight: bold;
        ">A = Alfa / Tidak Absen</td>';

        // Fill remaining legend cells
        $legendCols = count($this->periodDates) + 1;
        for ($i = 0; $i < $legendCols - 2; $i++) {
            $legendHtml .= '<td style="background-color: #f3f4f6; border: 1px solid #d1d5db;"></td>';
        }
        $legendHtml .= '</tr>';

        // Footer with generator info
        $generatedBy = $this->generatorInfo['generated_by'] ?? 'System';
        $generatedAt = $this->generatorInfo['generated_at'] ?? date('d/m/Y H:i');
        $footerHtml = '<tr>
            <td colspan="' . $totalColumns . '" style="
                background-color: #f9fafb;
                color: #6b7280;
                font-size: 8pt;
                padding: 8px;
                border: 1px solid #d1d5db;
                text-align: right;
            ">Dicetak oleh: ' . htmlspecialchars($generatedBy) . ' | Tanggal: ' . htmlspecialchars($generatedAt) . '</td>
        </tr>';

        $html = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta charset="UTF-8">
    <meta name="Generator" content="Office 354">
    <title>RECAP ABSEN</title>
    <style>
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 9pt;
            color: #333333;
            margin: 10px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #d1d5db;
        }
        /* Print settings for Excel */
        @media print {
            body { margin: 0; }
            table { page-break-after: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            td { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <table>
        <thead>
            ' . $titleHtml . '
            ' . $periodHtml . '
            ' . $headerHtml . '
        </thead>
        <tbody>
            ' . $rowsHtml . '
            ' . $legendHtml . '
            ' . $footerHtml . '
        </tbody>
    </table>
</body>
</html>';

        return $html;
    }

    /**
     * Get content type
     */
    public function getContentType(): string
    {
        return 'application/vnd.ms-excel';
    }

    /**
     * Get filename
     */
    public function getFilename(): string
    {
        return 'recap-absen-' . date('Y-m-d') . '.xls';
    }
}
