<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Sidebar Items Provider - Complete sidebar menu structure
 * All modules are FREE for all CRM users
 */
class SidebarItemsService
{
    public static function getSidebarItems(): array
    {
        return Cache::remember('sidebar_items_tree', 3600, function () {
            return [
                // Dashboard
                'dashboard' => [
                    'label' => 'Beranda',
                    'icon' => 'fa-home',
                    'href' => 'dashboard',
                    'type' => 'free',
                    'children' => [],
                ],

                // Finance (includes multiple modules)
                'finance' => [
                    'label' => 'Finance',
                    'icon' => 'fa-wallet',
                    'type' => 'free',
                    'children' => [
                        ['key' => 'proposals', 'text' => 'Proposal', 'href' => 'proposals.index', 'icon' => 'fa-file-contract'],
                        ['key' => 'estimates', 'text' => 'Estimasi', 'href' => 'estimates.index', 'icon' => 'fa-file-invoice'],
                        ['key' => 'invoices', 'text' => 'Faktur', 'href' => 'invoices.index', 'icon' => 'fa-file-invoice-dollar'],
                        ['key' => 'payments', 'text' => 'Pembayaran', 'href' => 'payments.index', 'icon' => 'fa-credit-card'],
                        ['key' => 'credit_notes', 'text' => 'Nota Kredit', 'href' => 'credit-notes.index', 'icon' => 'fa-file-circle-minus'],
                        ['key' => 'items', 'text' => 'Item', 'href' => 'items.index', 'icon' => 'fa-box'],
                        ['key' => 'transactions', 'text' => 'Keuangan', 'href' => 'transactions.index', 'icon' => 'fa-wallet'],
                        ['key' => 'contracts', 'text' => 'Kontrak', 'href' => 'contracts.index', 'icon' => 'fa-file-signature'],
                        ['key' => 'subscriptions', 'text' => 'Langganan', 'href' => 'subscriptions.index', 'icon' => 'fa-rotate'],
                        ['key' => 'clients', 'text' => 'Klien', 'href' => 'clients.index', 'icon' => 'fa-users'],
                        ['key' => 'leads', 'text' => 'Prospek', 'href' => 'leads.index', 'icon' => 'fa-bullseye'],
                        ['key' => 'estimate_requests', 'text' => 'Estimate Request', 'href' => 'estimate-requests.index', 'icon' => 'fa-list-check'],
                    ],
                ],

                // Sales Management
                'sales_management' => [
                    'label' => 'Sales',
                    'icon' => 'fa-chart-line',
                    'type' => 'free',
                    'children' => [
                        ['key' => 'sales.dashboard', 'text' => 'Dashboard Sales', 'href' => 'sales.dashboard', 'icon' => 'fa-gauge'],
                        ['key' => 'sales.leads', 'text' => 'Leads', 'href' => 'sales.leads.index', 'icon' => 'fa-user-plus'],
                        ['key' => 'sales.prospects', 'text' => 'Prospects', 'href' => 'sales.prospects.index', 'icon' => 'fa-bullseye'],
                        ['key' => 'sales.deals', 'text' => 'Deals', 'href' => 'sales.deals.index', 'icon' => 'fa-handshake'],
                        ['key' => 'sales.customers', 'text' => 'Customers', 'href' => 'sales.customers.index', 'icon' => 'fa-users'],
                        ['key' => 'sales.quotations', 'text' => 'Quotations', 'href' => 'sales.quotations.index', 'icon' => 'fa-file-contract'],
                        ['key' => 'sales.orders', 'text' => 'Sales Orders', 'href' => 'sales.orders.index', 'icon' => 'fa-cart-shopping'],
                        ['key' => 'sales.activities', 'text' => 'Activities', 'href' => 'sales.activities.index', 'icon' => 'fa-clock-rotate-left'],
                    ],
                ],

                // Proyek & Tugas
                'project_tasks' => [
                    'label' => 'Proyek & Tugas',
                    'icon' => 'fa-briefcase',
                    'type' => 'free',
                    'children' => [
                        ['key' => 'projects', 'text' => 'Proyek', 'href' => 'projects.index', 'icon' => 'fa-folder-open'],
                        ['key' => 'tasks', 'text' => 'Daftar Tugas', 'href' => 'tasks.index', 'icon' => 'fa-list-check'],
                        ['key' => 'tasks.calendar', 'text' => 'Kalender Tugas', 'href' => 'tasks.calendar', 'icon' => 'fa-calendar'],
                    ],
                ],

                // Kelola Aset & Akses
                'assets' => [
                    'label' => 'Kelola Aset & Akses',
                    'icon' => 'fa-building',
                    'type' => 'free',
                    'children' => [
                        ['key' => 'assets', 'text' => 'Manajemen Aset', 'href' => 'assets.index', 'icon' => 'fa-laptop'],
                        ['key' => 'asset_categories', 'text' => 'Kategori Aset', 'href' => 'asset-categories.index', 'icon' => 'fa-layer-group'],
                    ],
                ],

                // Panduan Dasar
                'guides' => [
                    'label' => 'Panduan Dasar',
                    'icon' => 'fa-book-open',
                    'href' => 'guides.index',
                    'type' => 'free',
                    'children' => [],
                ],

                // Laporan & Utilitas
                'reports_utilities' => [
                    'label' => 'Laporan & Utilitas',
                    'icon' => 'fa-chart-line',
                    'type' => 'free',
                    'children' => [
                        ['key' => 'reports', 'text' => 'Laporan', 'href' => 'reports.index', 'icon' => 'fa-chart-pie'],
                        ['key' => 'audit', 'text' => 'Audit', 'href' => 'audit.index', 'icon' => 'fa-clipboard-check'],
                        ['key' => 'tools', 'text' => 'Alat Berguna', 'href' => 'tools.index', 'icon' => 'fa-screwdriver-wrench'],
                    ],
                ],

                // HRD
                'hrd_expert' => [
                    'label' => 'HRD',
                    'icon' => 'fa-users-gear',
                    'type' => 'free',
                    'children' => [
                        ['key' => 'hrd.dashboard', 'text' => 'Dashboard HRD', 'href' => 'hrd.dashboard', 'icon' => 'fa-gauge'],
                        ['key' => 'hrd.employees', 'text' => 'Data Karyawan', 'href' => 'hrd.employees.index', 'icon' => 'fa-users'],
                        ['key' => 'hrd.attendances', 'text' => 'Absensi', 'href' => 'hrd.attendances.index', 'icon' => 'fa-calendar-check'],
                        ['key' => 'hrd.reports', 'text' => 'Laporan HRD', 'href' => 'hrd.reports.index', 'icon' => 'fa-chart-bar'],
                    ],
                ],

                // Page Builder
                'page_builder' => [
                    'label' => 'Pembuat Halaman',
                    'icon' => 'fa-layer-group',
                    'href' => 'page-builder.index',
                    'type' => 'free',
                    'children' => [],
                ],

                // Addons
                'addons' => [
                    'label' => 'Addons',
                    'icon' => 'fa-puzzle-piece',
                    'href' => 'marketplace.index',
                    'type' => 'free',
                    'children' => [],
                ],

                // Kelola Modul (Developer only)
                'admin_modules' => [
                    'label' => 'Kelola Modul',
                    'icon' => 'fa-puzzle-piece',
                    'href' => 'admin.modules.index',
                    'type' => 'free',
                    'children' => [],
                ],

                // Settings
                'settings' => [
                    'label' => 'Pengaturan',
                    'icon' => 'fa-gear',
                    'href' => 'settings.index',
                    'type' => 'free',
                    'children' => [],
                ],

                // Perusahaan Saya
                'company_settings' => [
                    'label' => 'Perusahaan Saya',
                    'icon' => 'fa-building-user',
                    'type' => 'free',
                    'children' => [
                        ['key' => 'companies.show', 'text' => 'Detail Perusahaan', 'href' => 'companies.show', 'icon' => 'fa-building'],
                        ['key' => 'companies.divisions', 'text' => 'Divisi', 'href' => 'companies.divisions.index', 'icon' => 'fa-sitemap'],
                    ],
                ],
            ];
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('sidebar_items_tree');
    }

    public static function getPermissionTree(): array
    {
        $items = self::getSidebarItems();
        $tree = [];
        foreach ($items as $key => $item) {
            $hasChildren = !empty($item['children']);
            $tree[$key] = [
                'key' => $key,
                'label' => $item['label'],
                'icon' => $item['icon'] ?? null,
                'type' => 'free',
                'is_parent' => $hasChildren,
                'children' => [],
            ];
            if ($hasChildren) {
                foreach ($item['children'] as $child) {
                    $tree[$key]['children'][$child['key']] = [
                        'key' => $child['key'],
                        'text' => $child['text'],
                        'href' => $child['href'] ?? null,
                    ];
                }
            }
        }
        return $tree;
    }

    public static function getAllPermissionKeys(): array
    {
        $keys = [];
        foreach (self::getSidebarItems() as $key => $item) {
            $keys[] = $key;
            if (!empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    $keys[] = $child['key'];
                }
            }
        }
        return $keys;
    }

    public static function getChildKeys(string $parentKey): array
    {
        $items = self::getSidebarItems();
        if (!isset($items[$parentKey]) || empty($items[$parentKey]['children'])) return [];
        return array_column($items[$parentKey]['children'], 'key');
    }

    public static function getParentKey(string $childKey): ?string
    {
        foreach (self::getSidebarItems() as $parentKey => $item) {
            if (!empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    if ($child['key'] === $childKey) return $parentKey;
                }
            }
        }
        return null;
    }

    public static function isParentKey(string $key): bool
    {
        $items = self::getSidebarItems();
        return isset($items[$key]) && !empty($items[$key]['children']);
    }

    public static function getPermissionsByType(): array
    {
        $items = self::getSidebarItems();
        $free = [];
        foreach ($items as $key => $item) {
            $hasChildren = !empty($item['children']);
            $data = [
                'key' => $key,
                'label' => $item['label'],
                'icon' => $item['icon'] ?? null,
                'is_parent' => $hasChildren,
                'children' => [],
            ];
            if ($hasChildren) {
                $data['children'] = array_map(fn($c) => ['key' => $c['key'], 'text' => $c['text']], $item['children']);
            }
            $free[$key] = $data;
        }
        return ['free' => $free, 'premium' => [], 'owner_access' => []];
    }

    public static function getFlatPermissions(): array
    {
        $permissions = [];
        foreach (self::getSidebarItems() as $parentKey => $item) {
            if (!empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    $permissions[$child['key']] = $item['label'] . ' > ' . $child['text'];
                }
            } else {
                $permissions[$parentKey] = $item['label'];
            }
        }
        return $permissions;
    }

    public static function getPermissionKeys(): array
    {
        return array_keys(self::getFlatPermissions());
    }
}
