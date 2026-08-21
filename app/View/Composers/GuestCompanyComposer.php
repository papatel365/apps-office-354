<?php

namespace App\View\Composers;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class GuestCompanyComposer
{
    /**
     * Bind company logo and favicon to guest views (login, guest layouts)
     */
    public function compose(View $view): void
    {
        $company = $this->getDefaultCompany();

        $view->with([
            'appLogo' => $company['logo_url'],
            'appFavicon' => $company['favicon_url'],
            'appLogoPath' => $company['logo_path'],
            'appFaviconPath' => $company['favicon_path'],
        ]);
    }

    /**
     * Get company data from first company or cache
     */
    protected function getDefaultCompany(): array
    {
        $cacheKey = 'guest_company_logo_favicon';

        return Cache::remember($cacheKey, 3600, function () {
            // Get first company (for login page)
            $company = Company::first();

            if (!$company) {
                return [
                    'logo_url' => null,
                    'favicon_url' => null,
                    'logo_path' => null,
                    'favicon_path' => null,
                ];
            }

            return [
                'logo_url' => $company->logo_url,
                'favicon_url' => $company->favicon_url,
                'logo_path' => $company->logo,
                'favicon_path' => $company->favicon,
            ];
        });
    }
}
