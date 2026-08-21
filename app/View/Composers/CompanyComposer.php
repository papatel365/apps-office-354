<?php

namespace App\View\Composers;

use App\Models\Company;
use App\Modules\System\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CompanyComposer
{
    const DEFAULT_COMPANY_NAME = 'Office 354';

    /**
     * Bind company logo and favicon to all views (for authenticated users only)
     * Guest users should use GuestCompanyComposer instead
     *
     * Single Company Context:
     * If exactly 1 company exists in database, use it regardless of user's stale company_id.
     * Fallback to system defaults if no company exists.
     */
    public function compose(View $view): void
    {
        $user = auth()->user();

        // Only set company data for authenticated users
        if (!$user) {
            return;
        }

        // Always get company data (returns defaults if no company)
        $companyData = $this->getCompanyData();

        $view->with([
            'appLogo' => $companyData['logo_url'],
            'appFavicon' => $companyData['favicon_url'],
            'appLogoPath' => $companyData['logo_path'],
            'appFaviconPath' => $companyData['favicon_path'],
            'appFooterText' => $companyData['footer_text'],
        ]);
    }

    /**
     * Get company data with caching.
     * Uses single-company context: if exactly 1 company exists, use it.
     */
    protected function getCompanyData(): array
    {
        $company = $this->getActiveCompany();

        if (!$company) {
            return $this->getDefaultData();
        }

        $defaultFooter = '© ' . date('Y') . ' ' . self::DEFAULT_COMPANY_NAME . '. All rights reserved.';

        return [
            'logo_url' => $company->logo_url,
            'favicon_url' => $company->favicon_url,
            'logo_path' => $company->logo,
            'favicon_path' => $company->favicon,
            'footer_text' => $company->footer_text ?: $defaultFooter,
        ];
    }

    /**
     * Get active company using single-company context.
     *
     * @return Company|null
     */
    protected function getActiveCompany(): ?Company
    {
        $count = Company::count();

        // No companies or multiple companies - return null
        if ($count !== 1) {
            return null;
        }

        return Company::first();
    }

    /**
     * Get default fallback data when no company exists.
     */
    protected function getDefaultData(): array
    {
        $defaultFooter = '© ' . date('Y') . ' ' . self::DEFAULT_COMPANY_NAME . '. All rights reserved.';

        return [
            'logo_url' => null,
            'favicon_url' => null,
            'logo_path' => null,
            'favicon_path' => null,
            'footer_text' => $defaultFooter,
        ];
    }
}
