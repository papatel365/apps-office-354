# Professional Export Templates - SantaraCRM

## Overview
Comprehensive professional styling system for all PDF, Excel, and Word exports in SantaraCRM.

## Corporate Color Palette

| Color | Hex | Usage |
|-------|-----|-------|
| Primary Blue | `#1e40af` | Headers, titles, primary actions |
| Primary Light | `#3b82f6` | Accents, highlights |
| Secondary Gray | `#6b7280` | Subtitles, labels, footer |
| Text Dark | `#111827` | Body text |
| White | `#ffffff` | Table headers, backgrounds |
| Zebra Odd | `#f9fafb` | Alternating row background |
| Border | `#e5e7eb` | Table borders |
| Success Green | `#059669` | Approved, paid, active status |
| Warning Orange | `#d97706` | Pending status |
| Danger Red | `#dc2626` | Rejected, failed status |

## Typography

### PDF
- Font: DejaVu Sans, Helvetica, Arial
- Title: 22pt, Bold, Uppercase, Letter-spacing 0.5px
- Subtitle: 11pt, Normal
- Table Header: 9pt, Bold, Uppercase
- Table Body: 9pt, Normal
- Footer: 8pt

### Excel
- Font: Calibri
- Title: 18pt, Bold, Blue
- Header: 10pt, Bold, White
- Body: 10pt, Normal
- Frozen header row enabled

### Word
- Font: Calibri, Arial
- Similar hierarchy to PDF

## File Structure

```
app/
├── Exports/
│   ├── BaseExport.php           # Base class with common styling
│   ├── AttendanceExport.php     # Attendance report Excel export
│   ├── EmployeeExport.php       # Employee report Excel export
│   ├── LeaveExport.php          # Leave report Excel export
│   ├── OvertimeExport.php       # Overtime report Excel export
│   └── SalaryExport.php         # Salary report Excel export

resources/views/
├── crm/hrd/reports/exports/
│   └── pdf/
│       └── report.blade.php      # Professional PDF template

app/Http/Controllers/HRD/
└── HRReportController.php        # Updated to use new exports
```

## Features Implemented

### PDF Export
- ✅ Elegant header with company name, report title, and period
- ✅ Decorative header lines (gradient and subtle)
- ✅ Corporate blue table header
- ✅ Zebra stripe table rows
- ✅ Professional empty state with icon
- ✅ Summary section (when available)
- ✅ Info section with print details
- ✅ Footer with page numbers (Halaman X dari Y)
- ✅ Optional watermark (SantaraCRM, 20% opacity)
- ✅ Consistent typography hierarchy

### Excel Export
- ✅ Corporate blue header row
- ✅ Frozen header pane
- ✅ Auto-sized columns
- ✅ Zebra stripe rows
- ✅ Professional borders (thin, subtle)
- ✅ Indonesian currency formatting (Rp 10.000.000)
- ✅ Date formatting (dd/mm/yyyy)
- ✅ Status color coding
- ✅ Empty state styling
- ✅ Summary sections
- ✅ Print settings (A4 landscape, fit to page)

### Word Export
- ✅ Professional header with company branding
- ✅ Corporate blue table headers
- ✅ Zebra stripe rows
- ✅ Consistent typography
- ✅ Empty state styling
- ✅ Info section
- ✅ Footer with print date

## Usage

### Export Routes
```
GET /hrd/reports/export/pdf/{type}
GET /hrd/reports/export/excel/{type}
GET /hrd/reports/export/word/{type}
```

### Available Report Types
- `attendance` - Laporan Absensi
- `employees` - Laporan Karyawan
- `leaves` - Laporan Cuti
- `overtime` - Laporan Lembur
- `salary` - Laporan Gaji

## Example Output

### PDF Preview
```
┌─────────────────────────────────────────────────────────────┐
│ SANTARACRM                                                  │
│ LAPORAN ABSENSI                                             │
│ Juli 2026                                                   │
│ ═══════════════════════════════════════════════════════════ │
│ ─────────────────────────────────────────────────────────  │
│                                                             │
│ ┌────┬──────┬──────────────┬───────────┬─────┬─────┐     │
│ │ No │ ID   │ Nama         │ Departemen │ ... │ ... │     │
│ ├────┼──────┼──────────────┼───────────┼─────┼─────┤     │
│ │ 1  │ EMP1 │ John Doe     │ IT         │ 22  │ 0   │     │
│ │ 2  │ EMP2 │ Jane Smith   │ Marketing  │ 20  │ 2   │     │
│ └────┴──────┴──────────────┴───────────┴─────┴─────┘     │
│                                                             │
│ Periode: Juli 2026                                          │
│ Tanggal Cetak: 23 Juli 2026, 15:30                         │
│ Dicetak oleh: Admin                                         │
│                                                             │
│ ─────────────────────────────────────────────────────────  │
│ SantaraCRM                          Halaman 1 dari 1         │
│ Dicetak pada 23 Jul 2026, 15:30                            │
└─────────────────────────────────────────────────────────────┘
```

### Excel Preview
```
┌─────────────────────────────────────────────────────────────┐
│ A │ B    │ C                │ D           │ E │ F │ G │ H │
├───┼──────┼──────────────────┼─────────────┼───┼───┼───┼───┤
│   │ SANTARACRM                                     │       │
│   │ LAPORAN ABSENSI                                │       │
│   │ Juli 2026                                     │       │
├───┼──────┼──────────────────┼─────────────┼───┼───┼───┼───┤
│ 1 │ EMP1 │ John Doe          │ IT          │22 │20 │ 0 │...│ ← Blue header
├───┼──────┼──────────────────┼─────────────┼───┼───┼───┼───┤
│ 2 │ EMP2 │ Jane Smith       │ Marketing   │20 │18 │ 2 │...│ ← White bg
├───┼──────┼──────────────────┼─────────────┼───┼───┼───┼───┤
│ 3 │ EMP3 │ Bob Wilson       │ Sales      │21 │19 │ 1 │...│ ← Gray bg
└───┴──────┴──────────────────┴─────────────┴───┴───┴───┴───┘
   ↑
   Frozen
```

## Best Practices

1. **Consistency**: All exports use the same color palette and typography
2. **Readability**: Proper spacing, alignment, and contrast
3. **Professionalism**: Clean, modern design like ERP systems
4. **Localization**: Indonesian formatting for dates, currency, and text
5. **Accessibility**: High contrast, clear hierarchy

## Future Enhancements

- [ ] Add logo support in headers
- [ ] Add footer with page numbers for Word
- [ ] Add charts/graphs for summary data
- [ ] Add custom branding per company
- [ ] Add export scheduling
- [ ] Add email delivery option
