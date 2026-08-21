<?php

namespace App\Services;

use App\Models\Module;

/**
 * Helper service untuk PAPATEL Company Premium Access
 *
 * PAPATEL secara khusus memiliki akses ke semua modul premium TANPA perlu langganan.
 * Ini adalah akses khusus yang tidak bisa dicopy/dipindahkan ke perusahaan lain.
 *
 * PAPATEL mendapatkan akses ke:
 * - Semua modul premium yang sudah ada
 * - Semua modul premium yang akan ditambahkan di masa depan
 *
 * Tidak perlu update code ketika ada modul baru - semua modul aktif otomatis accessible.
 */
class PAPATELAccessService
{
    /**
     * Nama perusahaan yang otomatis punya semua premium modules
     */
    public const PAPATEL_COMPANY_NAME = 'PAPATEL';

    /**
     * Slug perusahaan untuk pengecekan (case-insensitive)
     */
    public const PAPATEL_COMPANY_SLUG = 'papatel';

    /**
     * Semua kode modul premium yang diketahui
     * Modul baru akan otomatis accessible karena pengecekan menggunakan hasModuleAccess()
     */
    public const KNOWN_PREMIUM_MODULES = [
        'hrd_expert',
        'finance_expert',
        'isp_suite',
        'fnbr_suite',
        'retail_suite',
        'manufacturing_suite',
        'healthcare_suite',
        'education_suite',
        'property_suite',
        'ai_assistant',
        'advanced_reporting',
        'sales_management',
        'client_portal',
    ];

    /**
     * Check apakah company adalah PAPATEL (exact match)
     */
    public static function isPAPATEL(?string $companyName): bool
    {
        if (empty($companyName)) {
            return false;
        }

        return strtolower(trim($companyName)) === self::PAPATEL_COMPANY_SLUG;
    }

    /**
     * Check apakah company name mengandung PAPATEL
     */
    public static function containsPAPATEL(?string $companyName): bool
    {
        if (empty($companyName)) {
            return false;
        }

        return str_contains(strtolower($companyName), self::PAPATEL_COMPANY_SLUG);
    }

    /**
     * Check apakah PAPATEL memiliki akses ke modul tertentu
     *
     * Selalu return true untuk modul yang aktif.
     * Modul baru yang ditambahkan di database akan otomatis accessible.
     */
    public static function hasModuleAccess(string $moduleCode): bool
    {
        // Check dari database - modul baru akan otomatis accessible
        $module = Module::where('code', $moduleCode)
            ->where('is_active', true)
            ->first();

        if ($module) {
            return true;
        }

        // Fallback: check dari list yang dikenal
        // Ini memastikan modul baru yang belum ada di database tetap bisa diakses
        // setelah admin menambahkan di database, check di atas akan menangani
        return in_array($moduleCode, self::KNOWN_PREMIUM_MODULES);
    }

    /**
     * Dapatkan semua modul premium yang seharusnya dimiliki PAPATEL
     *
     * Mengembalikan dari KNOWN_PREMIUM_MODULES untuk memastikan
     * modul baru di masa depan otomatis termasuk.
     */
    public static function getPAPATELPremiumModules(): array
    {
        // Gabungkan dari database + known list untuk coverage maksimal
        $dbModules = Module::where('is_active', true)
            ->where('is_premium', true)
            ->pluck('code')
            ->toArray();

        // Union untuk hindari duplikasi
        return array_unique(array_merge(self::KNOWN_PREMIUM_MODULES, $dbModules));
    }

    /**
     * Dapatkan semua modul yang accessible untuk PAPATEL
     * Termasuk core + premium
     */
    public static function getAllAccessibleModules(): array
    {
        // Semua modul aktif (core + premium dari database)
        $dbModules = Module::where('is_active', true)
            ->pluck('code')
            ->toArray();

        // Gabungkan dengan known premium modules
        return array_unique(array_merge($dbModules, self::getPAPATELPremiumModules()));
    }
}
