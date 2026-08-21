<?php

namespace App\Services;

use App\Models\Company;
use App\Modules\System\Models\User;
use Illuminate\Support\Facades\Auth;

class CompanyService
{
    protected ?int $companyId = null;

    public function setCompany(?int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function getCurrentCompany(): ?Company
    {
        if ($this->companyId) {
            return Company::find($this->companyId);
        }

        $user = $this->getCurrentUser();
        if ($user && $user->company_id) {
            return $user->company;
        }

        return null;
    }

    public function getCurrentUser(): ?User
    {
        return Auth::user();
    }

    public function isPusatAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user->company_role === User::ROLE_PUSAT;
    }

    public function canAccessCompany(int $companyId): bool
    {
        $user = $this->getCurrentUser();

        if (!$user) {
            return false;
        }

        // Pusat admin can access all companies
        if ($user->company_role === User::ROLE_PUSAT) {
            return true;
        }

        // Others can only access their own company
        return $user->company_id === $companyId;
    }
}
