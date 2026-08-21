<?php

namespace App\Exports;

/**
 * Base Export Class - Professional styling without branding
 */
abstract class BaseExport
{
    // Corporate Color Palette - Professional Blue
    const COLOR_PRIMARY = '1e40af';
    const COLOR_PRIMARY_DARK = '153e7a';
    const COLOR_SECONDARY = '666666';
    const COLOR_TEXT = '333333';
    const COLOR_WHITE = 'ffffff';
    const COLOR_ZEBRA_ODD = 'f9fafb';
    const COLOR_ZEBRA_EVEN = 'ffffff';
    const COLOR_BORDER = 'd1d5db';
    const COLOR_SUCCESS = '059669';
    const COLOR_WARNING = 'd97706';
    const COLOR_DANGER = 'dc2626';

    // Typography
    const FONT_FAMILY = 'Calibri';

    /** @var array Headers */
    protected $headers = [];

    /** @var array Data rows */
    protected $rows = [];

    /** @var string Report title */
    protected $title = 'Laporan';

    /** @var string Period */
    protected $period = '';

    /**
     * Format currency (Indonesian format)
     */
    public static function formatCurrency($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    /**
     * Format date (Indonesian format)
     */
    public static function formatDate($value): string
    {
        if (empty($value)) {
            return '-';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Generate HTML for Excel - Professional styling without branding
     */
    public function generateHtml(): string
    {
        $headerHtml = '';
        foreach ($this->headers as $header) {
            $headerHtml .= '<th style="background-color: #' . self::COLOR_PRIMARY . '; color: #' . self::COLOR_WHITE . '; font-weight: bold; text-align: center; padding: 8px 6px; border: 1px solid #' . self::COLOR_PRIMARY . '; text-transform: uppercase; font-size: 8pt;">' . htmlspecialchars($header) . '</th>' . "\n";
        }

        $rowsHtml = '';
        if (!empty($this->rows)) {
            $rowIndex = 0;
            foreach ($this->rows as $row) {
                $bgColor = ($rowIndex % 2 === 0) ? self::COLOR_ZEBRA_EVEN : self::COLOR_ZEBRA_ODD;
                $rowsHtml .= '        <tr>' . "\n";
                foreach ($row as $cell) {
                    $cellValue = htmlspecialchars((string)($cell ?? ''));
                    $rowsHtml .= '            <td style="background-color: #' . $bgColor . '; padding: 6px 8px; border: 1px solid #' . self::COLOR_BORDER . '; vertical-align: middle; font-size: 9pt;">' . $cellValue . '</td>' . "\n";
                }
                $rowsHtml .= '        </tr>' . "\n";
                $rowIndex++;
            }
        } else {
            $colspan = count($this->headers);
            $rowsHtml = '        <tr><td colspan="' . $colspan . '" style="text-align: center; padding: 30px 15px; font-style: italic; color: #' . self::COLOR_SECONDARY . '; background-color: #' . self::COLOR_ZEBRA_ODD . ';">Tidak ada data sesuai filter.</td></tr>' . "\n";
        }

        $html = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta charset="UTF-8">
    <meta name="Generator" content="Office 354">
    <title>Laporan ' . htmlspecialchars($this->title) . '</title>
    <style>
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 9pt;
            color: #' . self::COLOR_TEXT . ';
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #' . self::COLOR_PRIMARY . ';
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .period {
            font-size: 11pt;
            color: #' . self::COLOR_SECONDARY . ';
            margin-top: 5px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background-color: #' . self::COLOR_PRIMARY . ';
            color: #' . self::COLOR_WHITE . ';
            font-weight: bold;
            text-align: center;
            padding: 8px 6px;
            border: 1px solid #' . self::COLOR_PRIMARY . ';
            text-transform: uppercase;
            font-size: 8pt;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #' . self::COLOR_BORDER . ';
            vertical-align: middle;
            font-size: 9pt;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; padding-right: 10px; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .status-success { color: #' . self::COLOR_SUCCESS . '; font-weight: bold; }
        .status-warning { color: #' . self::COLOR_WARNING . '; font-weight: bold; }
        .status-danger { color: #' . self::COLOR_DANGER . '; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Laporan ' . htmlspecialchars($this->title) . '</div>
        <div class="period">Periode: ' . htmlspecialchars($this->period) . '</div>
    </div>

    <table>
        <thead>
            <tr>
                ' . $headerHtml . '
            </tr>
        </thead>
        <tbody>
            ' . $rowsHtml . '
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
        $prefix = strtolower(str_replace(' ', '-', $this->title));
        return 'laporan-' . $prefix . '-' . date('Y-m-d') . '.xls';
    }
}
