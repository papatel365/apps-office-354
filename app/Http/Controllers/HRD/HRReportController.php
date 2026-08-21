<?php

namespace App\Http\Controllers\HRD;

use App\Exports\AttendanceExport;
use App\Exports\EmployeeExport;
use App\Exports\LeaveExport;
use App\Exports\SalaryExport;
use App\Http\Controllers\Controller;
use App\Services\HR\HRReportService;
use App\Services\HR\AttendanceReportService;
use App\Services\HR\EmployeeReportService;
use App\Services\HR\LeaveReportService;
use App\Services\HR\SalaryReportService;
use App\Services\HR\ReportFilterService;
use App\Services\HR\ReportFilterInfoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

/**
 * HR Report Controller
 *
 * Handles all HR report pages and exports.
 */
class HRReportController extends Controller
{
    use ReportPageTrait;

    protected $reportService;
    protected $attendanceService;
    protected $employeeService;
    protected $leaveService;
    protected $salaryService;
    protected $filterService;
    protected $filterInfoService;

    public function __construct(
        HRReportService $reportService,
        AttendanceReportService $attendanceService,
        EmployeeReportService $employeeService,
        LeaveReportService $leaveService,
        SalaryReportService $salaryService,
        ReportFilterService $filterService,
        ReportFilterInfoService $filterInfoService
    ) {
        $this->reportService = $reportService;
        $this->attendanceService = $attendanceService;
        $this->employeeService = $employeeService;
        $this->leaveService = $leaveService;
        $this->salaryService = $salaryService;
        $this->filterService = $filterService;
        $this->filterInfoService = $filterInfoService;
    }

    /**
     * Dashboard - Main Reports Page
     */
    public function index(Request $request): View
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        // Get summary data for dashboard
        $attendanceSummary = $this->attendanceService->getReportData([
            'month' => $month,
            'year' => $year,
        ]);

        $employeeSummary = $this->employeeService->getReportData([]);
        $leaveSummary = $this->leaveService->getReportData(['year' => $year]);
        $salarySummary = $this->salaryService->getSummaryData([
            'month' => $month,
            'year' => $year,
        ]);

        return view('crm.hrd.reports.index', [
            'filterOptions' => $this->reportService->getFilterOptions(),
            'attendanceSummary' => $attendanceSummary['summary'],
            'employeeSummary' => $employeeSummary['summary'],
            'leaveSummary' => $leaveSummary['summary'],
            'salarySummary' => $salarySummary,
            'todayAttendance' => $this->attendanceService->getTodaySummary(),
            'currentMonth' => $month,
            'currentYear' => $year,
            'monthName' => $this->reportService->getMonthOptions()[$month],
            'generatorInfo' => $this->reportService->getGeneratorInfo(),
        ]);
    }

    /**
     * Attendance Report
     */
    public function attendance(Request $request): View
    {
        $filters = $this->getDefaultFilters($request);
        $filterConfig = $this->getAttendanceFilterConfig();
        $filterInfo = $this->getFilterInfo($filters, $filterConfig);

        $data = $this->attendanceService->getReportData($filters);

        return view('crm.hrd.reports.attendance', array_merge($data, [
            'filterOptions' => $this->reportService->getFilterOptions(),
            'generatorInfo' => $this->reportService->getGeneratorInfo(),
            'exportQuery' => $this->filterService->buildExportQuery($filters),
            'filterInfo' => $filterInfo['filterInfo'],
            'hasFilters' => $filterInfo['hasFilters'],
        ]));
    }

    /**
     * Employee Report
     */
    public function employees(Request $request): View
    {
        $filters = $this->getDefaultFilters($request, ['department_id', 'division_id', 'position_id', 'status', 'search']);
        $filterConfig = $this->getEmployeeFilterConfig();
        $filterInfo = $this->getFilterInfo($filters, $filterConfig);

        $data = $this->employeeService->getReportData($filters);

        return view('crm.hrd.reports.employees', array_merge($data, [
            'filterOptions' => $this->reportService->getFilterOptions(),
            'generatorInfo' => $this->reportService->getGeneratorInfo(),
            'exportQuery' => $this->filterService->buildExportQuery($filters),
            'filterInfo' => $filterInfo['filterInfo'],
            'hasFilters' => $filterInfo['hasFilters'],
        ]));
    }

    /**
     * Leave Report
     */
    public function leaves(Request $request): View
    {
        $filters = $this->getDefaultFilters($request, ['year', 'month', 'department_id', 'division_id', 'employee_id', 'leave_type_id', 'status', 'search']);
        // Remove default month for leaves (only year is required)
        if (empty($request->month)) {
            unset($filters['month']);
        }
        $filterConfig = $this->getLeaveFilterConfig();
        $filterInfo = $this->getFilterInfo($filters, $filterConfig);

        $data = $this->leaveService->getReportData($filters);

        return view('crm.hrd.reports.leaves', array_merge($data, [
            'filterOptions' => $this->reportService->getFilterOptions(),
            'generatorInfo' => $this->reportService->getGeneratorInfo(),
            'exportQuery' => $this->filterService->buildExportQuery($filters),
            'filterInfo' => $filterInfo['filterInfo'],
            'hasFilters' => $filterInfo['hasFilters'],
        ]));
    }

    /**
     * Salary Report
     */
    public function salary(Request $request): View
    {
        $filters = $this->getDefaultFilters($request);
        $filterConfig = $this->getSalaryFilterConfig();
        $filterInfo = $this->getFilterInfo($filters, $filterConfig);

        $data = $this->salaryService->getReportData($filters);

        return view('crm.hrd.reports.salary', array_merge($data, [
            'filterOptions' => $this->reportService->getFilterOptions(),
            'generatorInfo' => $this->reportService->getGeneratorInfo(),
            'exportQuery' => $this->filterService->buildExportQuery($filters),
            'filterInfo' => $filterInfo['filterInfo'],
            'hasFilters' => $filterInfo['hasFilters'],
        ]));
    }

    /**
     * Export to PDF
     */
    public function exportPdf(Request $request, string $type)
    {
        $filters = $request->only(['month', 'year', 'department_id', 'division_id', 'employee_id', 'status', 'search', 'position_id', 'leave_type_id']);

        $data = $this->getReportData($type, $filters);

        // Use the generic report.blade.php which handles all report types via $type variable
        $view = 'crm.hrd.reports.exports.pdf.report';

        $title = $this->getReportTitle($type);
        $period = $this->getPeriodString($type, $filters);
        $companyName = $this->reportService->getGeneratorInfo()['company_name'] ?? 'Office 354';
        $filterInfoText = $this->getFilterInfoText($filters, $this->getFilterConfigForType($type));

        // IMPORTANT: For employees, use formatted data to avoid JSON objects
        if ($type === 'employees') {
            $data['employees'] = $this->employeeService->formatForExport($data['employees'] ?? []);
        }

        $pdf = Pdf::loadView($view, array_merge($data, [
            'type' => $type,
            'title' => $title,
            'period' => $period,
            'companyName' => $companyName,
            'generatorInfo' => $this->reportService->getGeneratorInfo(),
            'showWatermark' => true,
            'filterInfo' => $filterInfoText,
        ]));

        $pdf->setPaper('A4', 'landscape');

        $filename = $this->getExportFilename($type, $filters, 'pdf');

        return $pdf->download($filename);
    }

    /**
     * Get period string for report
     * For attendance: returns "Bulan Tahun" format (e.g., "Agustus 2026")
     */
    protected function getPeriodString(string $type, array $filters): string
    {
        $year = $filters['year'] ?? now()->year;
        $month = $filters['month'] ?? null;

        if (!$month) {
            return 'Tahun ' . $year;
        }

        // For attendance, use "Bulan Tahun" format (e.g., "Agustus 2026")
        // The actual date range (29 prev month - 28 selected month) is handled in the service
        if ($type === 'attendance') {
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];

            return ($monthNames[(int)$month] ?? $month) . ' ' . $year;
        }

        // Default format for other reports
        $monthOptions = $this->reportService->getMonthOptions();
        return $monthOptions[(int)$month] . ' ' . $year;
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request, string $type)
    {
        $filters = $request->only(['month', 'year', 'department_id', 'division_id', 'employee_id', 'status', 'search', 'position_id', 'leave_type_id']);
        $generatorInfo = $this->reportService->getGeneratorInfo();
        $filename = $this->getExportFilename($type, $filters, 'xls');

        // Use custom export classes
        $export = match ($type) {
            'attendance' => new AttendanceExport(
                $this->attendanceService->getAttendanceMatrix($filters),
                $generatorInfo,
                $this->getPeriodString($type, $filters)
            ),
            'employees' => new EmployeeExport($this->employeeService->getReportData($filters), $generatorInfo),
            'leaves' => new LeaveExport($this->leaveService->getReportData($filters), $generatorInfo, $this->getPeriodString($type, $filters)),
            'salary' => new SalaryExport($this->salaryService->getReportData($filters), $generatorInfo, $this->getPeriodString($type, $filters)),
            default => null,
        };

        if ($export) {
            $content = $export->export();
            return Response::make($content, 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        // Fallback to CSV
        return $this->exportGenericExcel($type, $this->getReportData($type, $filters), $filters);
    }

    /**
     * Generic Excel export fallback
     */
    protected function exportGenericExcel(string $type, array $data, array $filters)
    {
        $exportData = $this->formatForExport($type, $data);
        $csvContent = $this->generateCsv($exportData['headers'], $exportData['rows']);

        $filename = $this->getExportFilename($type, $filters, 'xlsx');

        return Response::make($csvContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export to Word
     */
    public function exportWord(Request $request, string $type)
    {
        $filters = $request->only(['month', 'year', 'department_id', 'division_id', 'employee_id', 'status', 'search', 'position_id', 'leave_type_id']);

        $data = $this->getReportData($type, $filters);
        $filterInfoText = $this->getFilterInfoText($filters, $this->getFilterConfigForType($type));

        // Note: Formatting for employees will be done in formatForExport() below
        // to avoid double formatting issues

        $exportData = $this->formatForExport($type, $data);
        $generatorInfo = $this->reportService->getGeneratorInfo();
        $html = $this->generateWordHtml($type, $data, $exportData, $filterInfoText, $generatorInfo);

        $filename = $this->getExportFilename($type, $filters, 'docx');

        return Response::make($html, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get report data based on type
     */
    protected function getReportData(string $type, array $filters): array
    {
        return match ($type) {
            'attendance' => $this->attendanceService->getReportData($filters),
            'employees' => $this->employeeService->getReportData($filters),
            'leaves' => $this->leaveService->getReportData($filters),
            'salary' => $this->salaryService->getReportData($filters),
            default => [],
        };
    }

    /**
     * Format data for export - EXACTLY matching web view table
     * For attendance: uses matrix format (employee x date)
     */
    protected function formatForExport(string $type, array $data): array
    {
        return match ($type) {
            'attendance' => [
                // Headers: NIP, Nama, dates, Total
                'headers' => $this->buildAttendanceHeaders($data),
                'rows' => $this->buildAttendanceRows($data),
            ],
            'employees' => [
                'headers' => ['ID', 'Nama', 'Email', 'No. HP', 'Departemen', 'Divisi', 'Jabatan', 'Status', 'Tipe', 'Tgl Bergabung'],
                'rows' => $this->employeeService->formatForExport($data['employees'] ?? []),
            ],
            'leaves' => [
                'headers' => ['ID', 'Nama', 'Departemen', 'Jenis Cuti', 'Tanggal Mulai', 'Tanggal Selesai', 'Lama (Hari)', 'Status'],
                'rows' => $this->leaveService->formatForExport(collect($data['leaves'] ?? [])),
            ],
            'salary' => [
                'headers' => ['ID', 'Nama', 'Departemen', 'Jabatan', 'Gaji Pokok', 'Tunjangan', 'Potongan', 'Total Gaji', 'Status'],
                'rows' => $this->salaryService->formatForExport(collect($data['salaries'] ?? [])),
            ],
            default => ['headers' => [], 'rows' => []],
        };
    }

    /**
     * Generate CSV content
     */
    protected function generateCsv(array $headers, array $rows): string
    {
        $output = fopen('php://temp', 'r+');

        // Add UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Write headers
        fputcsv($output, $headers);

        // Write rows
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Generate Word HTML - Professional styling without branding
     */
    protected function generateWordHtml(string $type, array $data, array $exportData, string $filterInfo = '', array $generatorInfo = []): string
    {
        $title = $this->getReportTitle($type);

        // Get period
        $period = '';
        if ($type === 'attendance') {
            $monthOptions = $this->reportService->getMonthOptions();
            $month = $data['month'] ?? null;
            $year = $data['year'] ?? now()->year;
            if ($month) {
                $period = $monthOptions[(int)$month] . ' ' . $year;
            } else {
                $period = 'Tahun ' . $year;
            }
        } elseif ($type === 'leaves' || $type === 'salary') {
            $year = $data['year'] ?? now()->year;
            $period = 'Tahun ' . $year;
        }

        // Generate header cells
        $headerHtml = '';
        foreach ($exportData['headers'] as $header) {
            $headerHtml .= '<th style="background-color: #1e40af; color: white; font-weight: bold; text-align: center; padding: 8px 6px; text-transform: uppercase; font-size: 8pt;">' . htmlspecialchars($header) . '</th>';
        }

        // Generate data rows
        $rowsHtml = '';
        if (!empty($exportData['rows'])) {
            $rowIndex = 0;
            foreach ($exportData['rows'] as $row) {
                $bgColor = ($rowIndex % 2 === 0) ? '#ffffff' : '#f9fafb';
                $rowsHtml .= '<tr>';
                foreach ($row as $cell) {
                    $rowsHtml .= '<td style="background-color: ' . $bgColor . '; padding: 6px 8px; border: 1px solid #d1d5db; vertical-align: middle; font-size: 9pt;">' . htmlspecialchars((string)($cell ?? '')) . '</td>';
                }
                $rowsHtml .= '</tr>';
                $rowIndex++;
            }
        } else {
            $colspan = count($exportData['headers']);
            $rowsHtml = '<tr><td colspan="' . $colspan . '" style="text-align: center; padding: 30px 15px; font-style: italic; color: #666666;">Tidak ada data sesuai filter.</td></tr>';
        }

        // Filter info section
        $filterInfoHtml = '';
        if ($filterInfo) {
            $filterInfoHtml = '<p style="font-size: 9pt; color: #666666; margin-bottom: 15px;">' . htmlspecialchars($filterInfo) . '</p>';
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Laporan {$title}</title>
<style>
    body {
        font-family: Calibri, Arial, sans-serif;
        font-size: 9pt;
        color: #333333;
        margin: 20px 30px;
    }

    /* Header - Centered, No Branding */
    .header {
        text-align: center;
        margin-bottom: 15px;
    }

    .title {
        font-size: 18pt;
        font-weight: bold;
        color: #1e40af;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 0;
    }

    .period {
        font-size: 11pt;
        color: #666666;
        margin-top: 5px;
    }

    .divider {
        border: none;
        border-top: 1px solid #1e40af;
        margin: 15px 0;
    }

    /* Table */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
    }

    thead {
        display: table-header-group;
    }

    th {
        background-color: #1e40af;
        color: white;
        font-weight: bold;
        text-align: center;
        padding: 8px 6px;
        border: none;
        text-transform: uppercase;
        font-size: 8pt;
    }

    td {
        padding: 6px 8px;
        border: 1px solid #d1d5db;
        vertical-align: middle;
        font-size: 9pt;
    }

    tr:nth-child(even) td {
        background-color: #f9fafb;
    }

    tr:nth-child(odd) td {
        background-color: #ffffff;
    }

    .text-left { text-align: left; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .font-bold { font-weight: bold; }

    /* Filter & Footer - Bottom Right */
    .report-footer {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #d1d5db;
        font-size: 8pt;
        color: #666666;
        text-align: right;
    }
    .filter-info {
        margin-bottom: 10px;
    }
</style>
</head>
<body>
    <div class="header">
        <div class="title">Laporan {$title}</div>
        <div class="period">Periode: {$period}</div>
    </div>

    <hr class="divider">

    <table>
        <thead>
            <tr>
                {$headerHtml}
            </tr>
        </thead>
        <tbody>
            {$rowsHtml}
        </tbody>
    </table>

    <div class="report-footer">
        <div class="filter-info">
            <strong>Filter:</strong><br>
            {$filterInfo}
        </div>
        <div>
            <strong>Dicetak oleh:</strong> {$generatorInfo['generated_by']} |
            <strong>Tanggal:</strong> {$generatorInfo['generated_at']}
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }


    /**
     * Get report title
     */
    protected function getReportTitle(string $type): string
    {
        return match ($type) {
            'attendance' => 'Absensi',
            'employees' => 'Karyawan',
            'leaves' => 'Cuti',
            'salary' => 'Gaji',
            default => ucfirst($type),
        };
    }

    /**
     * Get export filename
     */
    protected function getExportFilename(string $type, array $filters, string $extension): string
    {
        $prefix = match ($type) {
            'attendance' => 'laporan-absensi',
            'employees' => 'laporan-karyawan',
            'leaves' => 'laporan-cuti',
            'salary' => 'laporan-gaji',
            default => 'laporan',
        };

        $date = now()->format('Y-m-d');

        return "{$prefix}-{$date}.{$extension}";
    }

    /**
     * Get filter config for report type
     */
    protected function getFilterConfigForType(string $type): array
    {
        return match ($type) {
            'attendance' => $this->getAttendanceFilterConfig(),
            'employees' => $this->getEmployeeFilterConfig(),
            'leaves' => $this->getLeaveFilterConfig(),
            'salary' => $this->getSalaryFilterConfig(),
            default => [],
        };
    }

    /**
     * Build attendance export headers for generic export (CSV fallback)
     */
    protected function buildAttendanceHeaders(array $data): array
    {
        $headers = ['Nama'];

        $periodDates = $data['periodDates'] ?? [];
        foreach ($periodDates as $dateInfo) {
            $headers[] = $dateInfo['dayName'] ?? '';
        }

        $headers[] = 'Total';

        return $headers;
    }

    /**
     * Build attendance export rows for generic export (CSV fallback)
     */
    protected function buildAttendanceRows(array $data): array
    {
        $employees = $data['employees'] ?? [];
        $periodDates = $data['periodDates'] ?? [];
        $rows = [];

        foreach ($employees as $emp) {
            $row = [
                $emp['name'] ?? '-',
            ];

            foreach ($periodDates as $dateInfo) {
                $dateString = $dateInfo['dateString'];
                $row[] = $emp['attendance'][$dateString] ?? 'A';
            }

            // Total hadir only
            $row[] = $emp['total_hadir'] ?? 0;

            $rows[] = $row;
        }

        return $rows;
    }
}
