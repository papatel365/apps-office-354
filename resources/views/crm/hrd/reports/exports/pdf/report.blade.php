<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Laporan {{ $title }} - {{ $period ?? '' }}</title>
<style>
    /* ================================================================
       PAGE SETUP
       ================================================================ */
    @page {
        margin: 15mm 20mm 25mm 20mm;
        size: A4 landscape;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html, body {
        width: 100%;
        font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
        font-size: 9pt;
        color: #333333;
        line-height: 1.4;
    }

    /* ================================================================
       HEADER SECTION - CENTERED, NO BRANDING
       ================================================================ */
    .report-header {
        width: 100%;
        text-align: center;
        margin-bottom: 20px;
    }

    .report-title {
        font-size: 18pt;
        font-weight: bold;
        color: #1e40af;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }

    .report-period {
        font-size: 11pt;
        color: #666666;
        margin-top: 3px;
    }

    /* Elegant divider line */
    .header-divider {
        width: 100%;
        height: 1px;
        background-color: #1e40af;
        margin: 15px 0;
    }

    /* ================================================================
       TABLE STYLING
       ================================================================ */
    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
    }

    thead {
        display: table-header-group;
    }

    /* Table Header - Elegant Blue */
    .report-table thead th {
        background-color: #1e40af;
        color: #ffffff;
        font-weight: 600;
        font-size: 8pt;
        text-align: center;
        vertical-align: middle;
        padding: 8px 6px;
        border: none;
        border-bottom: 2px solid #153e7a;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    /* Table Body Cells */
    .report-table tbody td {
        border: 1px solid #d1d5db;
        padding: 6px 8px;
        vertical-align: middle;
        font-size: 9pt;
        color: #333333;
        background-color: #ffffff;
    }

    /* Zebra Striping */
    .report-table tbody tr:nth-child(even) td {
        background-color: #f9fafb;
    }

    .report-table tbody tr:nth-child(odd) td {
        background-color: #ffffff;
    }

    /* ================================================================
       TEXT ALIGNMENT
       ================================================================ */
    .text-left { text-align: left !important; }
    .text-right { text-align: right !important; padding-right: 10px !important; }
    .text-center { text-align: center !important; }
    .font-bold { font-weight: bold; }

    /* ================================================================
       EMPTY STATE
       ================================================================ */
    .empty-state {
        text-align: center;
        padding: 30px 15px;
        font-style: italic;
        color: #666666;
        background-color: #f9fafb !important;
    }

    /* ================================================================
       STATUS COLORS
       ================================================================ */
    .status-approved, .status-disetujui, .status-paid, .status-lunas, .status-aktif, .status-active {
        color: #059669;
        font-weight: bold;
    }
    .status-pending, .status-menunggu, .status-proses {
        color: #d97706;
        font-weight: bold;
    }
    .status-rejected, .status-ditolak, .status-gagal {
        color: #dc2626;
        font-weight: bold;
    }
    .status-inactive, .status-nonaktif {
        color: #6b7280;
    }

    /* ================================================================
       CURRENCY FORMATTING
       ================================================================ */
    .currency {
        font-family: 'DejaVu Sans Mono', monospace;
        text-align: right !important;
        padding-right: 10px !important;
    }

    /* ================================================================
       PAGE BREAK
       ================================================================ */
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    thead th {
        page-break-after: avoid;
    }

    /* ================================================================
       COLUMN WIDTHS - OPTIMIZED FOR CONTENT
       ================================================================ */
    .col-no { width: 5%; }
    .col-id { width: 8%; }
    .col-name { width: 35%; }
    .col-dept { width: 20%; }
    .col-position { width: 12%; }
    .col-date { width: 10%; }
    .col-number { width: 14%; }
    .col-currency { width: 12%; }
    .col-status { width: 8%; }
    .col-email { width: 15%; }
    .col-phone { width: 10%; }
    .col-desc { width: 15%; }
    .col-time { width: 8%; }
</style>
</head>
<body>

<!-- ================================================================
     REPORT HEADER - CENTERED, NO BRANDING
     ================================================================ -->
<div class="report-header">
    <div class="report-title">Laporan {{ $title }}</div>
    @if(isset($period) && $period)
    <div class="report-period">Periode: {{ $period }}</div>
    @endif
</div>

<div class="header-divider"></div>

<!-- ================================================================
     DATA TABLE
     ================================================================ -->
<table class="report-table">
    <thead>
        <tr>
            @if($type === 'attendance')
                <th class="col-no">No</th>
                <th class="col-name text-left">Karyawan</th>
                <th class="col-dept">Departemen</th>
                <th class="col-number">Total Hari</th>
                <th class="col-number">Hadir</th>
                <th class="col-number">Tidak Hadir</th>
            @elseif($type === 'employees')
                <th class="col-no">No</th>
                <th class="col-id">ID</th>
                <th class="col-name text-left">Nama Lengkap</th>
                <th class="col-email text-left">Email</th>
                <th class="col-phone">No. HP</th>
                <th class="col-dept">Departemen</th>
                <th class="col-position">Jabatan</th>
                <th class="col-status">Status</th>
                <th class="col-date">Tgl Bergabung</th>
            @elseif($type === 'leaves')
                <th class="col-no">No</th>
                <th class="col-id">ID</th>
                <th class="col-name text-left">Nama Karyawan</th>
                <th class="col-dept">Jenis Cuti</th>
                <th class="col-date">Tanggal Mulai</th>
                <th class="col-date">Tanggal Selesai</th>
                <th class="col-number">Lama</th>
                <th class="col-status">Status</th>
            @elseif($type === 'salary')
                <th class="col-no">No</th>
                <th class="col-id">ID</th>
                <th class="col-name text-left">Nama Karyawan</th>
                <th class="col-dept">Departemen</th>
                <th class="col-position">Jabatan</th>
                <th class="col-currency">Gaji Pokok</th>
                <th class="col-currency">Tunjangan</th>
                <th class="col-currency">Potongan</th>
                <th class="col-currency font-bold">Total Gaji</th>
                <th class="col-status">Status</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp

        @if($type === 'attendance')
            @forelse($byEmployee ?? [] as $emp)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td class="text-left">{{ $emp['employee_name'] }}</td>
                <td class="text-center">{{ $emp['department'] ?? '-' }}</td>
                <td class="text-center">{{ $emp['total_days'] ?? 0 }}</td>
                <td class="text-center">{{ $emp['present'] ?? 0 }}</td>
                <td class="text-center">{{ $emp['absent'] ?? 0 }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="empty-state">Tidak ada data sesuai filter.</td></tr>
            @endforelse

        @elseif($type === 'employees')
            @forelse($employees ?? [] as $emp)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td class="text-center">{{ $emp['employee_id'] ?? '-' }}</td>
                <td class="text-left">{{ $emp['full_name'] }}</td>
                <td class="text-left">{{ $emp['email'] ?? '-' }}</td>
                <td class="text-center">{{ $emp['phone'] ?? '-' }}</td>
                <td class="text-center">{{ $emp['department'] ?? '-' }}</td>
                <td class="text-center">{{ $emp['position'] ?? '-' }}</td>
                <td class="text-center">
                    @if(($emp['is_active'] ?? false))
                        <span class="status-aktif">Aktif</span>
                    @else
                        <span class="status-nonaktif">Nonaktif</span>
                    @endif
                </td>
                <td class="text-center">
                    @if(!empty($emp['join_date']))
                        {{ \Carbon\Carbon::parse($emp['join_date'])->format('d/m/Y') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="empty-state">Tidak ada data sesuai filter.</td></tr>
            @endforelse

        @elseif($type === 'leaves')
            @forelse($leaves ?? [] as $leave)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td class="text-center">{{ $leave['employee_id'] ?? '-' }}</td>
                <td class="text-left">{{ $leave['employee_name'] ?? '-' }}</td>
                <td class="text-center">{{ $leave['leave_type'] ?? '-' }}</td>
                <td class="text-center">
                    @if(!empty($leave['start_date']))
                        {{ \Carbon\Carbon::parse($leave['start_date'])->format('d/m/Y') }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    @if(!empty($leave['end_date']))
                        {{ \Carbon\Carbon::parse($leave['end_date'])->format('d/m/Y') }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">{{ $leave['total_days'] ?? 0 }} hari</td>
                <td class="text-center">
                    @if(($leave['status'] ?? '') === 'approved')
                        <span class="status-disetujui">Disetujui</span>
                    @elseif(($leave['status'] ?? '') === 'pending')
                        <span class="status-menunggu">Menunggu</span>
                    @elseif(($leave['status'] ?? '') === 'rejected')
                        <span class="status-ditolak">Ditolak</span>
                    @else
                        {{ ucfirst($leave['status'] ?? '-') }}
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="empty-state">Tidak ada data sesuai filter.</td></tr>
            @endforelse

        @elseif($type === 'salary')
            @forelse($salaries ?? [] as $salary)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td class="text-center">{{ $salary['employee_id'] ?? '-' }}</td>
                <td class="text-left">{{ $salary['employee_name'] ?? '-' }}</td>
                <td class="text-center">{{ $salary['department'] ?? '-' }}</td>
                <td class="text-center">{{ $salary['position'] ?? '-' }}</td>
                <td class="text-right currency">Rp {{ number_format($salary['basic_salary'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right currency">Rp {{ number_format($salary['allowances'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right currency">Rp {{ number_format($salary['deductions'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right currency font-bold">Rp {{ number_format($salary['total_salary'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-center">
                    @if(($salary['payment_status'] ?? '') === 'paid')
                        <span class="status-lunas">Lunas</span>
                    @else
                        <span class="status-menunggu">Menunggu</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="10" class="empty-state">Tidak ada data sesuai filter.</td></tr>
            @endforelse
        @endif
    </tbody>
</table>

<!-- ================================================================
     FILTER INFO - BOTTOM OF DOCUMENT
     ================================================================ -->
<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #d1d5db;">
    <div style="font-size: 8pt; color: #666666; text-align: right;">
        @if(!empty($filterInfo))
            <div style="margin-bottom: 10px;">
                <strong>Filter:</strong><br>
                {!! nl2br(e($filterInfo)) !!}
            </div>
        @endif
        <div>
            <strong>Dicetak oleh:</strong> {{ $generatorInfo['generated_by'] ?? '-' }} |
            <strong>Tanggal:</strong> {{ $generatorInfo['generated_at'] ?? '-' }}
        </div>
    </div>
</div>

</body>
</html>
