<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Indonesia Timezone Helper
 *
 * Maps Indonesian provinces/cities to their respective timezones.
 * Indonesia spans 3 timezones:
 * - WIB (Waktu Indonesia Barat) = UTC+7 = Asia/Jakarta
 * - WITA (Waktu Indonesia Tengah) = UTC+8 = Asia/Makassar
 * - WIT (Waktu Indonesia Timur) = UTC+9 = Asia/Jayapura
 */
class IndonesiaTimezoneHelper
{
    /**
     * Timezone definitions
     */
    public const TIMEZONES = [
        'Asia/Jakarta' => [
            'name' => 'WIB',
            'offset' => 'UTC+7',
            'label' => 'Waktu Indonesia Barat',
        ],
        'Asia/Makassar' => [
            'name' => 'WITA',
            'offset' => 'UTC+8',
            'label' => 'Waktu Indonesia Tengah',
        ],
        'Asia/Jayapura' => [
            'name' => 'WIT',
            'offset' => 'UTC+9',
            'label' => 'Waktu Indonesia Timur',
        ],
    ];

    /**
     * Provinces in WIB (Waktu Indonesia Barat) - UTC+7
     * Covers Sumatra, Java, West & Central Kalimantan
     */
    public const WIB_PROVINCES = [
        // Sumatra
        'aceh',
        'nanggroe aceh darussalam',
        'sumatera utara',
        'sumatera barat',
        'riau',
        'kepulauan riau',
        'jambi',
        'bengkulu',
        'sumatera selatan',
        'lampung',
        'bangka belitung',
        'bangka belitung islands',
        'kepulauan bangka belitung',

        // Java
        'dki jakarta',
        'jakarta',
        'jawa barat',
        'jawa tengah',
        'jawa timur',
        'banten',
        'di yogyakarta',
        'dki yogyakarta',
        'yogyakarta',

        // Kalimantan
        'kalimantan barat',
        'kalimantan tengah',
        'kalimantan selatan', // Note: Technically should be WITA but commonly grouped with WIB
    ];

    /**
     * Provinces in WITA (Waktu Indonesia Tengah) - UTC+8
     * Covers Bali, Nusa Tenggara, Sulawesi, East & North Kalimantan
     */
    public const WITA_PROVINCES = [
        // Bali & Nusa Tenggara
        'bali',
        'nusa tenggara barat',
        'ntb',
        'nusa tenggara timur',
        'ntt',
        'lombok',

        // Sulawesi
        'sulawesi selatan',
        'sulawesi tengah',
        'sulawesi tenggara',
        'sulawesi barat',
        'sulawesi utara',
        'gorontalo',

        // East & North Kalimantan
        'kalimantan timur',
        'kalimantan utara',
        'kalimantan timur dan utara',
    ];

    /**
     * Provinces in WIT (Waktu Indonesia Timur) - UTC+9
     * Covers Papua region
     */
    public const WIT_PROVINCES = [
        'papua',
        'papua barat',
        'papua tengah',
        'papua pegunungan',
        'papua selatan',
        'papua barat daya',
        'papua jaya',
        'papua Barat',
        'West Papua',
        'West Papua',
    ];

    /**
     * Major cities and their timezones
     * Used when province info is not available
     */
    public const CITY_TIMEZONES = [
        // WIB Cities
        'jakarta' => 'Asia/Jakarta',
        'bandung' => 'Asia/Jakarta',
        'surabaya' => 'Asia/Jakarta',
        'semarang' => 'Asia/Jakarta',
        'yogyakarta' => 'Asia/Jakarta',
        'medan' => 'Asia/Jakarta',
        'palembang' => 'Asia/Jakarta',
        'pekanbaru' => 'Asia/Jakarta',
        'padang' => 'Asia/Jakarta',
        'bandar lampung' => 'Asia/Jakarta',
        'jambi' => 'Asia/Jakarta',
        'bengkulu' => 'Asia/Jakarta',

        // WITA Cities
        'makassar' => 'Asia/Makassar',
        'denpasar' => 'Asia/Makassar',
        'manado' => 'Asia/Makassar',
        'kendari' => 'Asia/Makassar',
        'gorontalo' => 'Asia/Makassar',
        'palu' => 'Asia/Makassar',
        'mataram' => 'Asia/Makassar',
        'kupang' => 'Asia/Makassar',
        'balikpapan' => 'Asia/Makassar',
        'samarinda' => 'Asia/Makassar',
        'tarakan' => 'Asia/Makassar',

        // WIT Cities
        'jayapura' => 'Asia/Jayapura',
        'sorong' => 'Asia/Jayapura',
        'merauke' => 'Asia/Jayapura',
        'timika' => 'Asia/Jayapura',
        'wamena' => 'Asia/Jayapura',
    ];

    /**
     * Determine timezone from province name
     */
    public static function getTimezoneFromProvince(?string $province): string
    {
        if (!$province) {
            return 'Asia/Jakarta'; // Default
        }

        $normalizedProvince = self::normalizeString($province);

        // Check WIB provinces
        foreach (self::WIB_PROVINCES as $wibProvince) {
            if (str_contains($normalizedProvince, $wibProvince)) {
                return 'Asia/Jakarta';
            }
        }

        // Check WITA provinces
        foreach (self::WITA_PROVINCES as $witaProvince) {
            if (str_contains($normalizedProvince, $witaProvince)) {
                return 'Asia/Makassar';
            }
        }

        // Check WIT provinces
        foreach (self::WIT_PROVINCES as $witProvince) {
            if (str_contains($normalizedProvince, $witProvince)) {
                return 'Asia/Jayapura';
            }
        }

        // Default to WIB
        return 'Asia/Jakarta';
    }

    /**
     * Determine timezone from city name
     */
    public static function getTimezoneFromCity(?string $city): string
    {
        if (!$city) {
            return 'Asia/Jakarta';
        }

        $normalizedCity = self::normalizeString($city);

        return self::CITY_TIMEZONES[$normalizedCity] ?? self::getTimezoneFromProvince($city);
    }

    /**
     * Determine timezone from full address (from Nominatim reverse geocoding)
     */
    public static function getTimezoneFromAddress(?string $address): string
    {
        if (!$address) {
            return 'Asia/Jakarta';
        }

        $normalizedAddress = self::normalizeString($address);

        // Try to find province in address
        foreach (self::WIB_PROVINCES as $province) {
            if (str_contains($normalizedAddress, $province)) {
                return 'Asia/Jakarta';
            }
        }

        foreach (self::WITA_PROVINCES as $province) {
            if (str_contains($normalizedAddress, $province)) {
                return 'Asia/Makassar';
            }
        }

        foreach (self::WIT_PROVINCES as $province) {
            if (str_contains($normalizedAddress, $province)) {
                return 'Asia/Jayapura';
            }
        }

        // Try to find city
        foreach (self::CITY_TIMEZONES as $city => $timezone) {
            if (str_contains($normalizedAddress, $city)) {
                return $timezone;
            }
        }

        return 'Asia/Jakarta';
    }

    /**
     * Get timezone info (name, offset, label)
     */
    public static function getTimezoneInfo(string $timezone): array
    {
        return self::TIMEZONES[$timezone] ?? self::TIMEZONES['Asia/Jakarta'];
    }

    /**
     * Get timezone name abbreviation (WIB, WITA, WIT)
     */
    public static function getTimezoneName(string $timezone): string
    {
        return self::TIMEZONES[$timezone]['name'] ?? 'WIB';
    }

    /**
     * Get timezone offset (UTC+7, UTC+8, UTC+9)
     */
    public static function getTimezoneOffset(string $timezone): string
    {
        return self::TIMEZONES[$timezone]['offset'] ?? 'UTC+7';
    }

    /**
     * Format time with timezone
     */
    public static function formatTimeWithTimezone(
        ?Carbon $time,
        string $timezone,
        string $format = 'H:i:s'
    ): string {
        if (!$time) {
            return '-';
        }

        $formattedTime = $time->copy()->timezone($timezone)->format($format);
        $tzName = self::getTimezoneName($timezone);

        return $formattedTime . ' ' . $tzName;
    }

    /**
     * Format time with full timezone info
     */
    public static function formatTimeFull(
        ?Carbon $time,
        string $timezone,
        string $format = 'H:i:s'
    ): string {
        if (!$time) {
            return '-';
        }

        $formattedTime = $time->copy()->timezone($timezone)->format($format);
        $tzName = self::getTimezoneName($timezone);
        $tzOffset = self::getTimezoneOffset($timezone);

        return $formattedTime . ' ' . $tzName . ' (' . $tzOffset . ')';
    }

    /**
     * Get current time in specific timezone
     */
    public static function now(string $timezone): Carbon
    {
        return Carbon::now($timezone);
    }

    /**
     * Parse time in specific timezone
     */
    public static function parseInTimezone(string $time, string $timezone): Carbon
    {
        return Carbon::parse($time, $timezone);
    }

    /**
     * Normalize string for comparison (lowercase, trim, remove extra spaces)
     */
    protected static function normalizeString(string $str): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $str)));
    }

    /**
     * Extract province from Nominatim address
     */
    public static function extractProvinceFromNominatim(array $addressData): ?string
    {
        // Nominatim provides these fields:
        // state, state_district, county, city, municipality, etc.

        $fields = [
            'state',
            'state_district',
            'county',
            'region',
        ];

        foreach ($fields as $field) {
            if (!empty($addressData[$field])) {
                return $addressData[$field];
            }
        }

        return null;
    }

    /**
     * Extract city from Nominatim address
     */
    public static function extractCityFromNominatim(array $addressData): ?string
    {
        $fields = [
            'city',
            'municipality',
            'town',
            'village',
            'locality',
        ];

        foreach ($fields as $field) {
            if (!empty($addressData[$field])) {
                return $addressData[$field];
            }
        }

        return null;
    }

    /**
     * Get all available timezones
     */
    public static function getAvailableTimezones(): array
    {
        return array_keys(self::TIMEZONES);
    }

    /**
     * Check if a timezone is valid
     */
    public static function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, self::getAvailableTimezones());
    }
}
