<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * BreadcrumbBuilder - Global breadcrumb generation service
 *
 * Each breadcrumb item has these properties:
 * - label: Display text
 * - icon: Font Awesome icon class (optional)
 * - url: Link URL (null = no link)
 * - current: Boolean (true = current page)
 * - clickable: Boolean (optional)
 *   - false = always render as <span> even if url exists
 *   - true = render as <a> if url exists
 *   - null = auto-detect from url presence (backward compatible)
 * - parent: Parent route name for hierarchy (optional)
 *
 * Example configuration:
 * 'karyawan.index' => [
 *     'label' => 'Karyawan',
 *     'icon' => 'fa-users',
 *     'parent' => null,
 *     'clickable' => false,  // <-- Makes this breadcrumb non-clickable
 * ],
 */
class BreadcrumbBuilder
{
    /**
     * Custom title overrides set via Breadcrumb::set()
     * @var array
     */
    protected static array $customTitles = [];

    /**
     * Custom breadcrumb trail overrides
     * @var array|null
     */
    protected static ?array $customTrail = null;

    /**
     * Get the breadcrumb trail for the current route
     */
    public static function build(): array
    {
        if (self::$customTrail !== null) {
            $trail = self::$customTrail;
            self::$customTrail = null;
            return self::normalizeTrail($trail);
        }

        $route = Route::current();
        if (!$route) {
            return self::normalizeTrail(self::dashboardTrail());
        }

        $routeName = $route->getName() ?? '';
        $routeUri = '/' . ltrim($route->uri(), '/');

        if ($routeName === 'dashboard' || $routeName === 'beranda' || $routeUri === 'dashboard') {
            return self::normalizeTrail(self::dashboardTrail());
        }

        $definition = self::findDefinition($routeName, $routeUri);

        if (!$definition) {
            return self::normalizeTrail(self::autoGenerate($routeName, $route));
        }

        return self::normalizeTrail(self::buildFromDefinition($definition, $route));
    }

    /**
     * Normalize trail to ensure clickable property is set
     */
    protected static function normalizeTrail(array $trail): array
    {
        return array_map(function ($crumb) {
            // If clickable is explicitly set, respect it
            // Otherwise, auto-detect from url presence (backward compatible)
            if (!isset($crumb['clickable'])) {
                $crumb['clickable'] = isset($crumb['url']);
            }
            return $crumb;
        }, $trail);
    }

    /**
     * Set a custom title for the current page
     */
    public static function setTitle(string $title): void
    {
        $routeName = self::currentRouteName();
        self::$customTitles[$routeName] = $title;
    }

    /**
     * Set a custom trail (overrides auto-generation)
     */
    public static function setTrail(array $trail): void
    {
        self::$customTrail = $trail;
    }

    /**
     * Set multiple custom titles at once
     */
    public static function setTitles(array $titles): void
    {
        foreach ($titles as $routeName => $title) {
            self::$customTitles[$routeName] = $title;
        }
    }

    /**
     * Reset all custom titles
     */
    public static function reset(): void
    {
        self::$customTitles = [];
        self::$customTrail = null;
    }

    /**
     * Get current route name
     */
    protected static function currentRouteName(): string
    {
        return Route::currentRouteName() ?? '';
    }

    /**
     * Get beranda trail
     */
    protected static function dashboardTrail(): array
    {
        return [
            [
                'label' => 'Beranda',
                'icon' => 'fa-solid fa-house',
                'url' => route('dashboard'),
                'current' => false,
                'clickable' => true,
            ],
        ];
    }

    /**
     * Find definition for a route
     */
    protected static function findDefinition(string $routeName, string $routeUri): ?array
    {
        $map = self::getBreadcrumbMap();

        // Exact match
        if (isset($map[$routeName])) {
            return $map[$routeName];
        }

        // Wildcard match (e.g., projects.show)
        foreach ($map as $pattern => $definition) {
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace('*', '[^.]+', $pattern) . '$/';
                if (preg_match($regex, $routeName)) {
                    return $definition;
                }
            }
        }

        return null;
    }

    /**
     * Build breadcrumb from definition
     */
    protected static function buildFromDefinition(array $definition, $route): array
    {
        $trail = [];
        $resolvedName = self::resolveModelName($route);

        // Build hierarchy - last segment is the current/trailing segment
        $segments = explode('.', self::currentRouteName());
        $trail = self::buildHierarchy($segments, $route, $definition, $resolvedName, true);

        return $trail;
    }

    /**
     * Resolve model name from route parameters
     */
    protected static function resolveModelName($route): ?string
    {
        if (!$route) {
            return null;
        }

        $routeName = self::currentRouteName();
        $params = $route->parameters() ?? [];

        // Try to resolve common model names from route parameters
        $modelResolvers = [
            'company' => [\App\Models\Company::class, 'name'],
            'user' => [\App\Modules\System\Models\User::class, 'name'],
            'project' => [\App\Models\Project::class, 'name'],
            'task' => [\App\Models\Task::class, 'name'],
        ];

        foreach ($params as $paramKey => $paramValue) {
            if (isset($modelResolvers[$paramKey]) && is_object($paramValue)) {
                [$modelClass, $nameField] = $modelResolvers[$paramKey];
                if ($paramValue instanceof $modelClass) {
                    return $paramValue->{$nameField} ?? $paramValue->getKey();
                }
            }
        }

        // Try to resolve from parameter IDs
        foreach ($params as $paramKey => $paramValue) {
            if (isset($modelResolvers[$paramKey]) && is_numeric($paramValue)) {
                [$modelClass, $nameField] = $modelResolvers[$paramKey];
                try {
                    $model = $modelClass::find($paramValue);
                    if ($model) {
                        return $model->{$nameField} ?? $model->getKey();
                    }
                } catch (\Exception $e) {
                    // Model not found or column doesn't exist
                }
            }
        }

        // Special handling for developer company routes
        if (str_starts_with($routeName, 'developer.companies.show') && isset($params['company'])) {
            $company = $params['company'];
            if (is_object($company)) {
                return $company->name;
            }
        }

        return null;
    }

    /**
     * Build hierarchy from route name segments
     */
    protected static function buildHierarchy(array $segments, $route, ?array $definition, ?string $resolvedName = null, bool $isTrailingSegment = true): array
    {
        $trail = [];
        $currentRouteName = implode('.', $segments);
        $lastSegment = last($segments);
        $label = $definition['label'] ?? self::labelFromRoute($currentRouteName);
        $icon = $definition['icon'] ?? null;
        $clickable = $definition['clickable'] ?? null; // NEW: Explicit clickable property

        // Custom title takes precedence
        if (isset(self::$customTitles[$currentRouteName])) {
            $label = self::$customTitles[$currentRouteName];
        }
        // Resolved model name takes precedence over default labels
        elseif ($resolvedName && in_array($lastSegment, ['show', 'edit'])) {
            $label = $resolvedName;
        }

        // Index routes are always clickable
        // Current page is NOT clickable
        $isIndexRoute = $lastSegment === 'index';
        $isCurrentPage = $isTrailingSegment && in_array($lastSegment, ['show', 'create', 'edit']);

        // Skip auto-generation for 'index' routes that already have labels
        if (empty($definition['label']) && self::isAction($lastSegment)) {
            $label = self::labelFromRoute($currentRouteName);
        }

        // Skip empty labels (e.g., 'index' segments)
        if (!empty($label)) {
            // Generate URL safely - index routes are always clickable
            $url = null;
            try {
                // Index routes always have URL (clickable)
                if ($isIndexRoute) {
                    $url = route($currentRouteName);
                }
                // For non-index routes (show, edit, create), only add URL if NOT the current trailing page
                elseif (!$isCurrentPage) {
                    $url = route($currentRouteName);
                }
            } catch (\Throwable $e) {
                // Route requires parameters - skip URL for this breadcrumb
                $url = null;
            }

            // Determine clickable:
            // 1. If explicitly set via definition, use that value
            // 2. If current page, not clickable
            // 3. Otherwise, based on URL existence
            $isClickable = match(true) {
                $clickable === false => false,
                $clickable === true => true,
                $isCurrentPage => false,
                default => $url !== null,
            };

            $trail[] = [
                'label' => $label,
                'icon' => $icon,
                'url' => $url,
                'current' => $isCurrentPage,
                'clickable' => $isClickable,
            ];
        }

        // Add parent breadcrumb if exists
        if (isset($definition['parent']) && $definition['parent']) {
            $parentDef = self::findDefinition($definition['parent'], '');
            if ($parentDef) {
                $parentSegments = explode('.', $definition['parent']);
                // For parent breadcrumb, it's NOT the trailing segment
                $parentTrail = self::buildHierarchy($parentSegments, $route, $parentDef, null, false);
                $trail = array_merge($parentTrail, $trail);
            }
        }

        return $trail;
    }

    /**
     * Check if segment is an action
     */
    protected static function isAction(string $segment): bool
    {
        return in_array($segment, ['create', 'edit', 'show', 'index', 'store', 'update', 'destroy']);
    }

    /**
     * Generate label from route name
     */
    protected static function labelFromRoute(string $routeName): string
    {
        $segments = explode('.', $routeName);
        $lastSegment = last($segments);

        return self::humanize($lastSegment);
    }

    /**
     * Humanize a string
     */
    protected static function humanize(string $value): string
    {
        // Skip 'index' - it has no semantic value as a breadcrumb label
        if (strtolower($value) === 'index') {
            return '';
        }
        return Str::title(str_replace(['-', '_'], ' ', $value));
    }

    /**
     * Auto-generate breadcrumbs for undefined routes
     */
    protected static function autoGenerate(string $routeName, $route): array
    {
        $segments = explode('.', $routeName);
        $trail = [];
        $lastSegment = last($segments);

        // Determine which segment is the current (last) page
        $isCurrentPage = in_array($lastSegment, ['show', 'create', 'edit', 'index']);
        $isIndexRoute = $lastSegment === 'index';

        $currentRouteName = $routeName;
        foreach (array_reverse($segments) as $index => $segment) {
            $parentRoute = implode('.', array_slice($segments, 0, count($segments) - $index - 1));

            $label = self::humanize($segment);

            // Skip empty labels (e.g., 'index')
            if (empty($label)) {
                continue;
            }

            // Determine if this is the current page we're on
            $isThisCurrent = ($index === 0);

            if ($isThisCurrent) {
                $label = self::humanize(last($segments));
            }

            // Generate URL - index routes always clickable, others only if not current
            $url = null;
            if (!$isThisCurrent && $parentRoute) {
                try {
                    $url = route($parentRoute);
                } catch (\Exception $e) {
                    $url = null;
                }
            } elseif ($segment === 'index') {
                // Index routes are always clickable
                try {
                    $url = route($parentRoute ?: $routeName);
                } catch (\Exception $e) {
                    $url = null;
                }
            }

            // Determine clickable
            $isClickable = match(true) {
                $isThisCurrent => false,
                $isIndexRoute => true,
                default => $url !== null,
            };

            $trail[] = [
                'label' => $label,
                'url' => $url,
                'current' => $isThisCurrent,
                'clickable' => $isClickable,
            ];
        }

        return array_reverse($trail);
    }

    /**
     * Get module definitions
     */
    protected static function getModuleDefinitions(): array
    {
        return [
            'developer' => [
                'label' => 'Developer Center',
                'icon' => 'fa-code',
                'parent' => null,
            ],
            'hrd' => [
                'label' => 'Staff',
                'icon' => 'fa-users',
                'parent' => null,
            ],
            'companies' => [
                'label' => 'Perusahaan',
                'icon' => 'fa-building',
                'parent' => null,
            ],
        ];
    }

    /**
     * Check if segment is a module prefix
     */
    protected static function isModulePrefix(string $segment): bool
    {
        $modules = array_keys(self::getModuleDefinitions());
        return in_array($segment, $modules);
    }

    /**
     * Get module definition
     */
    protected static function getModuleDefinition(string $module): ?array
    {
        return self::getModuleDefinitions()[$module] ?? null;
    }

    /**
     * Get complete breadcrumb map (route definitions)
     */
    protected static function getBreadcrumbMap(): array
    {
        return [
            // === Core CRM ===
            'dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-home', 'parent' => null],

            // Projects
            'projects.index' => ['label' => 'Proyek', 'icon' => 'fa-folder-open', 'parent' => null],
            'projects.create' => ['label' => 'Tambah Proyek', 'icon' => 'fa-plus', 'parent' => 'projects.index'],
            'projects.show' => ['label' => 'Detail Proyek', 'icon' => 'fa-folder', 'parent' => 'projects.index'],
            'projects.edit' => ['label' => 'Edit Proyek', 'icon' => 'fa-pen', 'parent' => 'projects.show'],
            'projects.dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-chart', 'parent' => 'projects.show'],
            'projects.milestones.index' => ['label' => 'Milestone', 'icon' => 'fa-flag', 'parent' => 'projects.show'],
            'projects.files.index' => ['label' => 'File', 'icon' => 'fa-file', 'parent' => 'projects.show'],
            'projects.discussions.index' => ['label' => 'Diskusi', 'icon' => 'fa-comments', 'parent' => 'projects.show'],
            'projects.notes.index' => ['label' => 'Catatan', 'icon' => 'fa-sticky-note', 'parent' => 'projects.show'],
            'projects.activities.index' => ['label' => 'Aktivitas', 'icon' => 'fa-clock', 'parent' => 'projects.show'],

            // Tasks
            'tasks.index' => ['label' => 'Tugas', 'icon' => 'fa-list-check', 'parent' => null],
            'tasks.create' => ['label' => 'Tambah Tugas', 'icon' => 'fa-plus', 'parent' => 'tasks.index'],
            'tasks.show' => ['label' => 'Detail Tugas', 'icon' => 'fa-task', 'parent' => 'tasks.index'],
            'tasks.edit' => ['label' => 'Edit Tugas', 'icon' => 'fa-pen', 'parent' => 'tasks.show'],
            'tasks.calendar' => ['label' => 'Kalender', 'icon' => 'fa-calendar', 'parent' => null],
            'tasks.photos.index' => ['label' => 'Foto', 'icon' => 'fa-image', 'parent' => 'tasks.show'],
            'tasks.work-updates.index' => ['label' => 'Update Kerja', 'icon' => 'fa-sync', 'parent' => 'tasks.show'],

            // Assets
            'assets.index' => ['label' => 'Aset', 'icon' => 'fa-laptop', 'parent' => null],
            'assets.create' => ['label' => 'Tambah Aset', 'icon' => 'fa-plus', 'parent' => 'assets.index'],
            'assets.show' => ['label' => 'Detail Aset', 'icon' => 'fa-laptop', 'parent' => 'assets.index'],
            'assets.edit' => ['label' => 'Edit Aset', 'icon' => 'fa-pen', 'parent' => 'assets.show'],
            'asset-categories.index' => ['label' => 'Kategori Aset', 'icon' => 'fa-tags', 'parent' => null],

            // Reports & Audit
            'reports.index' => ['label' => 'Laporan', 'icon' => 'fa-chart-pie', 'parent' => null],
            'audit.index' => ['label' => 'Audit', 'icon' => 'fa-clipboard-check', 'parent' => null],

            // Pengaturan Module (Indonesian settings routes)
            'pengaturan.index' => ['label' => 'Pengaturan', 'icon' => 'fa-cogs', 'parent' => null],
            'settings.index' => ['label' => 'Pengaturan CRM', 'icon' => 'fa-gear', 'parent' => null],
            'pengaturan.umum.index' => ['label' => 'Umum', 'icon' => 'fa-home', 'parent' => 'pengaturan.index'],
            'pengaturan.hak_akses.index' => ['label' => 'Hak Akses', 'icon' => 'fa-shield-halved', 'parent' => 'pengaturan.index'],
            'pengaturan.hak_akses.show' => ['label' => 'Detail Hak Akses', 'icon' => 'fa-shield-halved', 'parent' => 'pengaturan.hak_akses.index'],
            'pengaturan.backup.index' => ['label' => 'Backup', 'icon' => 'fa-database', 'parent' => 'pengaturan.index'],
            'pengaturan.backup.history' => ['label' => 'Riwayat Backup', 'icon' => 'fa-history', 'parent' => 'pengaturan.backup.index'],
            'pengaturan.backup.settings' => ['label' => 'Pengaturan Backup', 'icon' => 'fa-cog', 'parent' => 'pengaturan.backup.index'],

            // Settings
            'settings.index' => ['label' => 'Pengaturan CRM', 'icon' => 'fa-gear', 'parent' => null],

            // Profile
            'profile.index' => ['label' => 'Profil', 'icon' => 'fa-user', 'parent' => null],

            // Notifications
            'notifications.index' => ['label' => 'Notifikasi', 'icon' => 'fa-bell', 'parent' => null],

            // Companies
            'companies.index' => ['label' => 'Perusahaan', 'icon' => 'fa-building', 'parent' => null],
            'companies.create' => ['label' => 'Tambah Perusahaan', 'icon' => 'fa-plus', 'parent' => 'companies.index'],
            'companies.show' => ['label' => 'Detail Perusahaan', 'icon' => 'fa-building', 'parent' => 'companies.index'],
            'companies.edit' => ['label' => 'Edit Perusahaan', 'icon' => 'fa-pen', 'parent' => 'companies.show'],
            'companies.members.index' => ['label' => 'Member', 'icon' => 'fa-users', 'parent' => 'companies.show'],
            'companies.divisions.index' => ['label' => 'Divisi', 'icon' => 'fa-sitemap', 'parent' => 'companies.show'],

            // Divisions
            'divisions.index' => ['label' => 'Divisi', 'icon' => 'fa-sitemap', 'parent' => null],

            // Members & Employees (Company Structure)
            'members.index' => ['label' => 'Member', 'icon' => 'fa-users', 'parent' => null],
            '' => ['label' => 'Karyawan', 'icon' => 'fa-user', 'parent' => null],

            // === HRD Expert ===
            'hrd.dashboard' => ['label' => 'Staff', 'icon' => 'fa-users', 'parent' => null],
            'hrd.index' => ['label' => 'Staff', 'icon' => 'fa-users', 'parent' => null],
            'hrd.employees.index' => ['label' => 'Data Karyawan', 'icon' => 'fa-user', 'parent' => 'hrd.index'],
            'hrd.employees.wizard.create' => ['label' => 'Tambah Karyawan', 'icon' => 'fa-plus', 'parent' => 'hrd.employees.index'],
            'hrd.employees.wizard.edit' => ['label' => 'Edit Karyawan', 'icon' => 'fa-pen', 'parent' => 'hrd.employees.index'],
            'hrd.employees.show' => ['label' => 'Detail Karyawan', 'icon' => 'fa-user', 'parent' => 'hrd.employees.index'],
            'hrd.placements.index' => ['label' => 'Penempatan', 'icon' => 'fa-map-pin', 'parent' => 'hrd.index'],
            'hrd.attendances.index' => ['label' => 'Absensi', 'icon' => 'fa-calendar-check', 'parent' => 'hrd.index'],
            'hrd.face-attendance' => ['label' => 'Absensi Wajah', 'icon' => 'fa-user-check', 'parent' => 'hrd.attendances.index'],
            'hrd.reports.index' => ['label' => 'Laporan', 'icon' => 'fa-chart-bar', 'parent' => 'hrd.index'],
            'hrd.leaves.index' => ['label' => 'Cuti', 'icon' => 'fa-umbrella-beach', 'parent' => 'hrd.index'],
            'hrd.overtimes.index' => ['label' => 'Lembur', 'icon' => 'fa-clock', 'parent' => 'hrd.index'],
            'hrd.payroll.index' => ['label' => 'Penggajian', 'icon' => 'fa-money-bill', 'parent' => 'hrd.index'],
            'hrd.audit.index' => ['label' => 'Audit', 'icon' => 'fa-clipboard-check', 'parent' => 'hrd.index'],
            'hrd.recruitment.index' => ['label' => 'Rekrutmen', 'icon' => 'fa-user-plus', 'parent' => 'hrd.index'],
            'hrd.trainings.index' => ['label' => 'Pelatihan', 'icon' => 'fa-chalkboard-teacher', 'parent' => 'hrd.index'],

            // === Administrasi Module ===
            // Root "Administrasi" breadcrumb is NON-CLICKABLE
            'administrasi.dashboard' => ['label' => 'Administrasi', 'icon' => 'fa-users', 'parent' => null, 'clickable' => false],
            'administrasi.index' => ['label' => 'Administrasi', 'icon' => 'fa-users', 'parent' => null, 'clickable' => false],
            'administrasi.data_karyawan.index' => ['label' => 'Data Karyawan', 'icon' => 'fa-user', 'parent' => 'administrasi.index'],
            'administrasi.data_karyawan.wizard.create' => ['label' => 'Tambah Karyawan', 'icon' => 'fa-plus', 'parent' => 'administrasi.data_karyawan.index'],
            'administrasi.data_karyawan.wizard.edit' => ['label' => 'Edit Karyawan', 'icon' => 'fa-pen', 'parent' => 'administrasi.data_karyawan.index'],
            'administrasi.data_karyawan.show' => ['label' => 'Detail Karyawan', 'icon' => 'fa-user', 'parent' => 'administrasi.data_karyawan.index'],
            'administrasi.placements.index' => ['label' => 'Penempatan', 'icon' => 'fa-map-pin', 'parent' => 'administrasi.index'],
            'administrasi.absen.index' => ['label' => 'Absensi', 'icon' => 'fa-calendar-check', 'parent' => 'administrasi.index'],
            'administrasi.absen.face' => ['label' => 'Absensi Wajah', 'icon' => 'fa-user-check', 'parent' => 'administrasi.absen.index'],
            'administrasi.laporan.index' => ['label' => 'Laporan', 'icon' => 'fa-chart-bar', 'parent' => 'administrasi.index'],
            'administrasi.laporan.attendance' => ['label' => 'Attendance', 'icon' => 'fa-calendar-check', 'parent' => 'administrasi.laporan.index'],
            'administrasi.laporan.employees' => ['label' => 'Employees', 'icon' => 'fa-users', 'parent' => 'administrasi.laporan.index'],
            'laporan.absensi' => ['label' => 'Laporan Absensi', 'icon' => 'fa-calendar-check', 'parent' => 'administrasi.laporan.index'],
            'laporan.karyawan' => ['label' => 'Laporan Karyawan', 'icon' => 'fa-users', 'parent' => 'administrasi.laporan.index'],
            'administrasi.laporan.leaves' => ['label' => 'Cuti', 'icon' => 'fa-umbrella-beach', 'parent' => 'administrasi.laporan.index'],
            'administrasi.laporan.overtime' => ['label' => 'Lembur', 'icon' => 'fa-clock', 'parent' => 'administrasi.laporan.index'],
            'administrasi.laporan.salary' => ['label' => 'Penggajian', 'icon' => 'fa-money-bill', 'parent' => 'administrasi.laporan.index'],
            'administrasi.laporan.training' => ['label' => 'Pelatihan', 'icon' => 'fa-chalkboard-teacher', 'parent' => 'administrasi.laporan.index'],
            'administrasi.laporan.recruitment' => ['label' => 'Rekrutmen', 'icon' => 'fa-user-plus', 'parent' => 'administrasi.laporan.index'],
            'administrasi.leaves.index' => ['label' => 'Cuti', 'icon' => 'fa-umbrella-beach', 'parent' => 'administrasi.index'],
            'administrasi.overtimes.index' => ['label' => 'Lembur', 'icon' => 'fa-clock', 'parent' => 'administrasi.index'],
            'administrasi.payroll.index' => ['label' => 'Penggajian', 'icon' => 'fa-money-bill', 'parent' => 'administrasi.index'],
            'administrasi.audit.index' => ['label' => 'Audit', 'icon' => 'fa-clipboard-check', 'parent' => 'administrasi.index'],
            'administrasi.recruitment.index' => ['label' => 'Rekrutmen', 'icon' => 'fa-user-plus', 'parent' => 'administrasi.index'],
            'administrasi.trainings.index' => ['label' => 'Pelatihan', 'icon' => 'fa-chalkboard-teacher', 'parent' => 'administrasi.index'],

            // === Developer Center ===
            'developer.dashboard' => ['label' => 'Developer Center', 'icon' => 'fa-code', 'parent' => null],
            'developer.company.index' => ['label' => 'Perusahaan Saya', 'icon' => 'fa-building', 'parent' => null],
            'developer.company.my.show' => ['label' => 'Detail', 'icon' => 'fa-building', 'parent' => 'developer.company.index'],
            'developer.company.my.edit' => ['label' => 'Edit', 'icon' => 'fa-pen', 'parent' => 'developer.company.my.show'],
            'developer.company.staff.index' => ['label' => 'Staff', 'icon' => 'fa-users', 'parent' => 'developer.company.index'],
            'developer.company.staff.create' => ['label' => 'Tambah Staff', 'icon' => 'fa-plus', 'parent' => 'developer.company.staff.index'],
            'developer.company.staff.edit' => ['label' => 'Edit Staff', 'icon' => 'fa-pen', 'parent' => 'developer.company.staff.index'],
            'developer.company.staff.permissions' => ['label' => 'Izin', 'icon' => 'fa-key', 'parent' => 'developer.company.staff.edit'],
            'developer.profile.index' => ['label' => 'Profil', 'icon' => 'fa-user', 'parent' => null],
            'developer.notifications.index' => ['label' => 'Notifikasi', 'icon' => 'fa-bell', 'parent' => null],
        ];
    }

    /**
     * Get icon for a route segment
     */
    public static function getIcon(string $routeName): ?string
    {
        $def = self::findDefinition($routeName, '');
        return $def['icon'] ?? null;
    }

    /**
     * Get label for a route segment
     */
    public static function getLabel(string $routeName): string
    {
        $def = self::findDefinition($routeName, '');
        if ($def) {
            return self::$customTitles[$routeName] ?? $def['label'];
        }
        return self::labelFromRoute($routeName);
    }
}
