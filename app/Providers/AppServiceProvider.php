<?php

namespace App\Providers;

use App\Models\SidebarPermission;
use App\Services\BreadcrumbBuilder;
use App\Services\CRM\CrmPermissionService;
use App\Services\HR\HRReportService;
use App\Services\HR\ReportFilterService;
use App\Services\HR\ReportFilterInfoService;
use App\Services\TenantService;
use App\Services\Permission\UserPermissionService;
use App\View\Composers\CompanyComposer;
use App\View\Composers\GuestCompanyComposer;
use App\View\Composers\UserDisplayNameComposer;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register TenantService as singleton
        $this->app->singleton(TenantService::class, function ($app) {
            return new TenantService();
        });

        // Register as 'tenant' alias
        $this->app->instance('tenant', $this->app->make(TenantService::class));

        // Register UserPermissionService as singleton (SINGLE SOURCE OF TRUTH)
        $this->app->singleton(UserPermissionService::class, function ($app) {
            return new UserPermissionService();
        });

        // Register as 'permission' alias for easy access
        $this->app->singleton('permission', function ($app) {
            return $app->make(UserPermissionService::class);
        });

        // Keep old CrmPermissionService for backward compatibility (delegates to UserPermissionService)
        $this->app->singleton(CrmPermissionService::class, function ($app) {
            return new CrmPermissionService();
        });

        // Register HR Report Services
        $this->app->singleton(HRReportService::class, function ($app) {
            return new HRReportService();
        });

        $this->app->singleton(ReportFilterService::class, function ($app) {
            return new ReportFilterService($app->make(HRReportService::class));
        });

        $this->app->singleton(ReportFilterInfoService::class, function ($app) {
            return new ReportFilterInfoService(
                $app->make(HRReportService::class),
                $app->make(ReportFilterService::class)
            );
        });
    }

    public function boot(): void
    {
        // Set default timezone and locale for Carbon
        // This ensures all date/time operations use Indonesian timezone and locale
        date_default_timezone_set('Asia/Jakarta');
        Carbon::setLocale('id');
        Carbon::now()->timezone('Asia/Jakarta');

        // Register View Composers
        // - UserDisplayNameComposer: Bind display_name to all views
        // - CompanyComposer: Bind company logo/favicon to all views (requires auth user)
        // - GuestCompanyComposer: Bind company logo/favicon for guest/unauthenticated views
        View::composer('*', UserDisplayNameComposer::class);
        View::composer('*', CompanyComposer::class);
        View::composer(['layouts.guest', 'auth.login'], GuestCompanyComposer::class);

        // Directive to safely check if a variable is a paginator
        Blade::directive('ispages', function ($expression) {
            return "<?php if(is_object({$expression}) && method_exists({$expression}, 'hasPages') && {$expression}->hasPages()): ?>";
        });

        // Directive to end the ispages block
        Blade::directive('endispages', function () {
            return "<?php endif; ?>";
        });

        // Directive to get total count (works for both paginators and collections)
        Blade::directive('total', function ($expression) {
            return "<?php echo is_object({$expression}) && method_exists({$expression}, 'total') ? {$expression}->total() : {$expression}->count(); ?>";
        });

        // === Breadcrumb Directives ===

        /**
         * @breadcrumb('Custom Title') - Set custom title for the current page breadcrumb
         * Usage in Blade: @breadcrumb('PT ABC Indonesia')
         */
        Blade::directive('breadcrumb', function ($expression) {
            // expression is the title string
            return "<?php \App\Services\BreadcrumbBuilder::setTitle({$expression}); ?>";
        });

        /**
         * @breadtrail([...]) - Set custom breadcrumb trail (full override)
         * Usage: @breadtrail([['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'fa-home'], ...])
         */
        Blade::directive('breadtrail', function ($expression) {
            return "<?php \App\Services\BreadcrumbBuilder::setTrail({$expression}); ?>";
        });

        // === Sidebar Permission Directives ===

        /**
         * @can('module') - Check if current user can ACCESS a module
         * @can('module', 'view') - Same as above
         * @can('module', 'create') - Check if can CREATE
         * @can('module', 'update') - Check if can UPDATE
         * @can('module', 'delete') - Check if can DELETE
         *
         * Usage: @can('projects') ... @endcan
         *        @can('projects', 'create') ... @endcan
         */
        Blade::directive('can', function ($expression) {
            // Parse: 'module' or 'module', 'action'
            $parts = array_map('trim', explode(',', $expression));
            $module = trim($parts[0], "'\"");
            $action = isset($parts[1]) ? trim($parts[1], "'\"") : 'access';

            $check = match ($action) {
                'view' => "app('permission')->canView({$parts[0]})",
                'create' => "app('permission')->canCreate({$parts[0]})",
                'update' => "app('permission')->canUpdate({$parts[0]})",
                'delete' => "app('permission')->canDelete({$parts[0]})",
                default => "app('permission')->can({$parts[0]})",
            };

            return "<?php if({$check}): ?>";
        });

        Blade::directive('endcan', function () {
            return "<?php endif; ?>";
        });

        /**
         * @canSidebar('permission_key') - Check if current user can access sidebar item
         * Usage: @canSidebar('projects') ... @endcanSidebar
         */
        Blade::directive('canSidebar', function ($expression) {
            $permission = trim($expression, "'\"");
            return "<?php if(app('permission')->canAccessSidebar({$expression})): ?>";
        });

        Blade::directive('endcanSidebar', function () {
            return "<?php endif; ?>";
        });

        /**
         * @canAny(['module1', 'module2']) - Check if user can access ANY of the modules
         */
        Blade::directive('canAny', function ($expression) {
            return "<?php if(app('permission')->canAny({$expression})): ?>";
        });

        Blade::directive('endcanAny', function () {
            return "<?php endif; ?>";
        });
    }
}
