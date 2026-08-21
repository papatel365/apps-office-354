<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SidebarMenuConfig
{
    /**
     * Get all sidebar menu configuration
     */
    public static function getMenuConfig(): array
    {
        return Cache::remember('sidebar_menu_config_v8', 86400, function () {
            return [
                // Beranda
                [
                    'key' => 'dashboard',
                    'label' => 'Beranda',
                    'icon' => 'fa-home',
                    'icon_class' => 'fa-solid fa-home',
                    'type' => 'item',
                    'route' => 'dashboard',
                    'permission_key' => 'sidebar.dashboard',
                    'group' => null,
                    'is_visible' => true,
                    'children' => [],
                ],

                // Dropdown Group: Proyek & Tugas (HIDDEN)
                [
                    'key' => 'projects_tasks',
                    'label' => 'Proyek & Tugas',
                    'icon' => 'fa-briefcase',
                    'icon_class' => 'fa-solid fa-briefcase',
                    'type' => 'group',
                    'route' => null,
                    'permission_key' => null,
                    'group' => 'projects_tasks',
                    'is_visible' => false,
                    'children' => [],
                ],

                // Dropdown Group: Administrasi
                [
                    'key' => 'staff',
                    'label' => 'Administrasi',
                    'icon' => 'fa-users',
                    'icon_class' => 'fa-solid fa-users',
                    'type' => 'group',
                    'route' => null,
                    'permission_key' => null,
                    'group' => 'staff',
                    'is_visible' => true,
                    'children' => [
                        [
                            'key' => 'staff_dashboard',
                            'label' => 'Staff',
                            'icon' => 'fa-gauge',
                            'icon_class' => 'fa-solid fa-gauge',
                            'type' => 'item',
                            'route' => 'administrasi.dashboard',
                            'permission_key' => 'sidebar.staff_dashboard',
                            'group' => 'staff',
                            'is_visible' => false,
                            'children' => [],
                        ],
                        [
                            'key' => 'employees',
                            'label' => 'Data Karyawan',
                            'icon' => 'fa-user',
                            'icon_class' => 'fa-solid fa-user',
                            'type' => 'item',
                            'route' => 'administrasi.data_karyawan.index',
                            'permission_key' => 'sidebar.employees',
                            'group' => 'staff',
                            'is_visible' => true,
                            'children' => [],
                        ],
                        [
                            'key' => 'attendances',
                            'label' => 'Absensi',
                            'icon' => 'fa-calendar-check',
                            'icon_class' => 'fa-solid fa-calendar-check',
                            'type' => 'item',
                            'route' => 'administrasi.absen.index',
                            'permission_key' => 'sidebar.attendances',
                            'group' => 'staff',
                            'is_visible' => true,
                            'children' => [],
                        ],
                        [
                            'key' => 'staff_reports',
                            'label' => 'Laporan',
                            'icon' => 'fa-chart-bar',
                            'icon_class' => 'fa-solid fa-chart-bar',
                            'type' => 'item',
                            'route' => 'administrasi.laporan.index',
                            'permission_key' => 'sidebar.staff_reports',
                            'group' => 'staff',
                            'is_visible' => true,
                            'children' => [],
                        ],
                    ],
                ],

                // Dropdown Group: Pengaturan
                [
                    'key' => 'atur_crm',
                    'label' => 'Pengaturan',
                    'icon' => 'fa-cogs',
                    'icon_class' => 'fa-solid fa-cogs',
                    'type' => 'group',
                    'route' => 'atur_crm', // Route key for reference
                    'permission_key' => null,
                    'group' => 'atur_crm',
                    'is_visible' => true,
                    // Redirect to Pengaturan Umum when clicking parent dropdown
                    'redirect_to' => 'pengaturan.umum.index',
                    'children' => [
                        [
                            'key' => 'backup',
                            'label' => 'Backup',
                            'icon' => 'fa-download',
                            'icon_class' => 'fa-solid fa-download',
                            'type' => 'item',
                            'route' => 'pengaturan.backup.index',
                            'permission_key' => 'sidebar.backup',
                            'group' => 'atur_crm',
                            'is_visible' => true,
                            'children' => [],
                        ],
                        [
                            'key' => 'hak_akses',
                            'label' => 'Hak Akses',
                            'icon' => 'fa-user-shield',
                            'icon_class' => 'fa-solid fa-user-shield',
                            'type' => 'item',
                            'route' => 'pengaturan.hak_akses.index',
                            'permission_key' => 'sidebar.hak_akses',
                            'group' => 'atur_crm',
                            'is_visible' => true,
                            'children' => [],
                        ],
                        [
                            'key' => 'master_data_umum',
                            'label' => 'Umum',
                            'icon' => 'fa-sliders-h',
                            'icon_class' => 'fa-solid fa-sliders-h',
                            'type' => 'item',
                            'route' => 'pengaturan.umum.index',
                            'permission_key' => 'sidebar.master_data_umum',
                            'group' => 'atur_crm',
                            'is_visible' => true,
                            'children' => [],
                        ],
                    ],
                ],
            ];
        });
    }

    /**
     * Get visible sidebar menu configuration
     */
    public static function getVisibleMenuConfig(): array
    {
        $menuConfig = self::getMenuConfig();
        $visible = [];

        foreach ($menuConfig as $menu) {
            if (isset($menu['is_visible']) && $menu['is_visible'] === false) {
                continue;
            }

            if (!empty($menu['children'])) {
                $visibleChildren = [];
                foreach ($menu['children'] as $child) {
                    if (!isset($child['is_visible']) || $child['is_visible'] !== false) {
                        $visibleChildren[] = $child;
                    }
                }
                $menu['children'] = $visibleChildren;
            }

            $visible[] = $menu;
        }

        return $visible;
    }

    /**
     * Get all sidebar menu items in a tree structure.
     * Alias for getMenuConfig() for compatibility.
     */
    public static function getMenuTree(): array
    {
        return self::getMenuConfig();
    }

    /**
     * Get all permission keys (flat list).
     */
    public static function getAllPermissionKeys(): array
    {
        $menuConfig = self::getMenuConfig();
        $keys = [];

        foreach ($menuConfig as $menu) {
            // Add parent key if it has a permission_key
            if (!empty($menu['permission_key'])) {
                $keys[] = $menu['permission_key'];
            }

            // Add child keys
            if (!empty($menu['children'])) {
                foreach ($menu['children'] as $child) {
                    if (!empty($child['permission_key'])) {
                        $keys[] = $child['permission_key'];
                    }
                }
            }
        }

        return array_unique($keys);
    }

    /**
     * Get only parent keys (group keys).
     */
    public static function getParentKeys(): array
    {
        $menuConfig = self::getMenuConfig();
        $keys = [];

        foreach ($menuConfig as $menu) {
            if ($menu['type'] === 'group' && !empty($menu['permission_key'])) {
                $keys[] = $menu['permission_key'];
            }
        }

        return $keys;
    }

    /**
     * Get child keys for a specific parent key.
     */
    public static function getChildKeys(string $parentKey): array
    {
        $menuConfig = self::getMenuConfig();
        $keys = [];

        foreach ($menuConfig as $menu) {
            if ($menu['type'] === 'group' && !empty($menu['permission_key']) && $menu['permission_key'] === $parentKey) {
                foreach ($menu['children'] ?? [] as $child) {
                    if (!empty($child['permission_key'])) {
                        $keys[] = $child['permission_key'];
                    }
                }
                break;
            }
        }

        return $keys;
    }

    /**
     * Get managed modules for CRM permissions.
     * Returns the list of modules that have permission management.
     * IMPORTANT: Module keys must match 'key' field in menu config for sidebar permissions.
     */
    public static function getManagedModules(): array
    {
        return [
            'projects',
            'tasks',
            'employees',
            'attendances',
            'staff_reports',
            'backup',
            'hak_akses',      // Key for "Hak Akses" menu (matches menu config 'key')
            'master_data_umum',
        ];
    }

    /**
     * Get module to permission key mapping.
     * Maps module key (from getManagedModules) to actual permission_key in menu config.
     */
    public static function getModulePermissionKeyMap(): array
    {
        return [
            'projects' => 'sidebar.projects',
            'tasks' => 'sidebar.tasks',
            'employees' => 'sidebar.employees',
            'attendances' => 'sidebar.attendances',
            'staff_reports' => 'sidebar.staff_reports',
            'backup' => 'sidebar.backup',
            'hak_akses' => 'sidebar.hak_akses',
            'master_data_umum' => 'sidebar.master_data_umum',
        ];
    }

    /**
     * Get module key by permission key.
     * Reverse lookup from permission_key to module key.
     */
    public static function getModuleKeyByPermissionKey(string $permissionKey): ?string
    {
        $map = self::getModulePermissionKeyMap();
        $flipped = array_flip($map);
        return $flipped[$permissionKey] ?? null;
    }

    /**
     * Check if Projects & Tasks module is enabled
     */
    public static function isProjectsModuleEnabled(): bool
    {
        // Check if projects_tasks group exists in menu config
        $menuConfig = self::getMenuConfig();

        foreach ($menuConfig as $menu) {
            if ($menu['key'] === 'projects_tasks' && !empty($menu['children'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear sidebar cache
     */
    public static function clearCache(): void
    {
        Cache::forget('sidebar_menu_config_v8');
    }

    /**
     * Get visible permission modules structure for CRM permissions UI.
     * Returns hierarchical structure: groups with children modules.
     *
     * @return array
     */
    public static function getVisiblePermissionModules(): array
    {
        $menuConfig = self::getMenuConfig();
        $modules = [];

        // Map menu keys to permission groups
        $groupMapping = [
            'projects_tasks' => [
                'key' => 'projects_tasks',
                'label' => 'Proyek & Tugas',
                'icon' => 'fa-briefcase',
            ],
            'staff' => [
                'key' => 'staff',
                'label' => 'Administrasi',
                'icon' => 'fa-users',
            ],
            'atur_crm' => [
                'key' => 'atur_crm',
                'label' => 'Pengaturan',
                'icon' => 'fa-cogs',
            ],
        ];

        foreach ($menuConfig as $menu) {
            $groupKey = $menu['key'] ?? null;

            // Skip hidden groups
            if (isset($menu['is_visible']) && $menu['is_visible'] === false && empty($menu['children'])) {
                continue;
            }

            // Check if this group should be a permission module
            if (isset($groupMapping[$groupKey])) {
                $group = $groupMapping[$groupKey];
                $children = [];

                // Process children
                if (!empty($menu['children'])) {
                    foreach ($menu['children'] as $child) {
                        if (!isset($child['is_visible']) || $child['is_visible'] !== false) {
                            $children[] = [
                                'key' => $child['key'],
                                'label' => $child['label'],
                                'icon' => $child['icon_class'] ?? $child['icon'] ?? 'fa-file',
                            ];
                        }
                    }
                }

                if (!empty($children)) {
                    $group['children'] = $children;
                    $modules[] = $group;
                }
            }
        }

        return $modules;
    }

    /**
     * Get default module permissions by role.
     * Used for initializing new user permissions.
     */
    public static function getDefaultModulePermissionsByRole(string $role): array
    {
        $managedModules = self::getManagedModules();

        // Default: no permissions (false for all)
        $defaults = [];
        foreach ($managedModules as $module) {
            $defaults[$module] = [
                'scope_own' => false,
                'scope_global' => false,
                'can_view' => false,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ];
        }

        // Owner, Director, Admin, Manager, Developer get all permissions by default
        $fullAccessRoles = ['owner', 'director', 'admin', 'manager', 'developer'];

        if (in_array($role, $fullAccessRoles)) {
            foreach ($defaults as $module => &$perm) {
                $perm = [
                    'scope_own' => true,
                    'scope_global' => true,
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ];
            }
        }

        // Staff gets limited access
        if ($role === 'staff') {
            // Staff can view employees, attendances, and reports
            if (isset($defaults['employees'])) {
                $defaults['employees'] = [
                    'scope_own' => true,
                    'scope_global' => false,
                    'can_view' => true,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                ];
            }
            if (isset($defaults['attendances'])) {
                $defaults['attendances'] = [
                    'scope_own' => true,
                    'scope_global' => false,
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => false,
                    'can_delete' => false,
                ];
            }
            if (isset($defaults['staff_reports'])) {
                $defaults['staff_reports'] = [
                    'scope_own' => true,
                    'scope_global' => false,
                    'can_view' => true,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                ];
            }
        }

        return $defaults;
    }
}
