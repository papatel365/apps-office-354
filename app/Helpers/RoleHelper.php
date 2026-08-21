<?php

namespace App\Helpers;

/**
 * RoleHelper - Helper untuk menampilkan label role dalam Bahasa Indonesia
 *
 * Database menyimpan: owner, director, admin, manager, staff, developer, pusat
 * UI menampilkan: Pemilik, Direktur, Administrator, Manajer, Staff, Developer, Pusat
 */
class RoleHelper
{
    /**
     * Mapping role database ke label Bahasa Indonesia
     */
    private const LABELS = [
        'owner' => 'Pemilik',
        'director' => 'Direktur',
        'admin' => 'Administrator',
        'manager' => 'Manajer',
        'staff' => 'Staff',
        'developer' => 'Developer',
        'pusat' => 'Pusat',
    ];

    /**
     * Mapping role ke CSS class untuk badge styling
     */
    private const BADGE_CLASSES = [
        'owner' => 'bg-purple-100 text-purple-800',
        'director' => 'bg-amber-100 text-amber-800',
        'admin' => 'bg-blue-100 text-blue-800',
        'manager' => 'bg-green-100 text-green-800',
        'staff' => 'bg-gray-100 text-gray-800',
        'developer' => 'bg-emerald-100 text-emerald-800',
        'pusat' => 'bg-red-100 text-red-800',
    ];

    /**
     * Get Indonesian label for a role
     *
     * @param string|null $role
     * @return string
     */
    public static function label(?string $role): string
    {
        if ($role === null) {
            return '-';
        }

        return self::LABELS[$role] ?? ucfirst($role);
    }

    /**
     * Get badge CSS class for a role
     *
     * @param string|null $role
     * @return string
     */
    public static function badgeClass(?string $role): string
    {
        if ($role === null) {
            return 'bg-gray-100 text-gray-800';
        }

        return self::BADGE_CLASSES[$role] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get all available roles with their labels
     * Includes emoji prefix for dropdown display
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'owner' => '🎯 Pemilik',
            'director' => '👑 Direktur',
            'admin' => '👨‍💼 Administrator',
            'manager' => '📊 Manajer',
            'staff' => '👤 Staff',
            'developer' => '🔧 Developer',
            'pusat' => '🏢 Pusat',
        ];
    }

    /**
     * Get all roles as options for select dropdown (value => label)
     * Without emoji prefix for cleaner dropdowns
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::LABELS;
    }
}
