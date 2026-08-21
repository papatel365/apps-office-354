# Developer Authorization Architecture

## Overview

This document describes the refactored authorization architecture that separates **Developer (Super Admin SaaS)** from **Tenant** users.

## The Problem (Before)

Previously, the system had a flawed authorization architecture where:

1. **TenantMiddleware** blocked developers from accessing tenant routes with a 403 error
2. **DashboardController** redirected developers to Developer Center
3. **Scopes** applied data isolation to developers

This meant developers could not:
- View tenant dashboards
- Access tenant data for debugging
- Perform administrative tasks across all tenants

## The Solution (After)

Now the system has a clean separation:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           AUTHORIZATION ARCHITECTURE                         │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────┐          ┌──────────────────┐                        │
│  │  DEVELOPER ROLE   │          │   TENANT ROLE    │                        │
│  │  (Super Admin)   │          │   (Company User) │                        │
│  └────────┬─────────┘          └────────┬─────────┘                        │
│           │                                │                                  │
│           │    ┌────────────────────────────┴────────────────────────────┐   │
│           │    │                    HTTP KERNEL                         │   │
│           │    │    Middleware Aliases Registered                        │   │
│           │    │    - 'auth'          : Standard auth                   │   │
│           │    │    - 'developer'     : DeveloperAccess middleware      │   │
│           │    │    - 'tenant'        : ResolveTenant middleware        │   │
│           │    │    - 'tenant.auth'   : TenantMiddleware                │   │
│           │    │    - 'module.access' : CheckModuleAccess middleware    │   │
│           │    └────────────────────────────┬────────────────────────────┘   │
│           │                                 │                                  │
│           │                                 │                                  │
│           ▼                                 ▼                                  │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                         ROUTE LAYER                                     │ │
│  │  ┌─────────────────────────────┐    ┌──────────────────────────────┐    │ │
│  │  │  /developer/*               │    │  /dashboard, /clients, etc.  │    │ │
│  │  │  Developer Center Routes    │    │  Tenant Routes              │    │ │
│  │  │  Middleware:                │    │  Middleware:                │    │ │
│  │  │  - auth                     │    │  - auth                     │    │ │
│  │  │  - developer                │    │  - tenant.auth              │    │ │
│  │  └─────────────────────────────┘    └──────────────────────────────┘    │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Authorization Flow

### For Developers Accessing Tenant Routes

```
Developer requests /dashboard
         │
         ▼
┌─────────────────┐
│ Auth Middleware │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ TenantMiddleware │
└────────┬────────┘
         │
         │  Check: is user developer?
         │  ┌─────────────────────┐
         │  │ YES → ALLOW THROUGH │
         │  │     (No company_id  │
         │  │      required)      │
         │  └─────────────────────┘
         │  ┌─────────────────────┐
         │  │ NO → Check company_ │
         │  │     id exists?     │
         │  │     - YES → ALLOW  │
         │  │     - NO → 403     │
         │  └─────────────────────┘
         │
         ▼
┌─────────────────┐
│ DashboardController │
└────────┬────────┘
         │
         │  Check: is user developer?
         │  ┌─────────────────────┐
         │  │ YES → SHOW DASHBOARD│
         │  │     (All data or   │
         │  │      tenant data)  │
         │  └─────────────────────┘
         │
         ▼
┌─────────────────┐
│ TenantScope     │ ──► Returns null = No scope applied = See ALL data
│ CompanyScope    │ ──► Returns null = No scope applied = See ALL data
└─────────────────┘
```

## Files Changed

### 1. TenantMiddleware (`app/Http/Middleware/TenantMiddleware.php`)

**BEFORE:**
```php
// Developers should use developer routes only
if ($user->is_developer) {
    abort(403, 'Access denied. Developer accounts should use the Developer Center.');
}
```

**AFTER:**
```php
// ============================================================
// DEVELOPER BYPASS - Developers have full system access
// ============================================================
if ($user->is_developer) {
    Log::debug('[TenantMiddleware] Developer bypass - allowing full access');
    return $next($request);
}
// ============================================================
```

### 2. DashboardController (`app/Modules/System/Http/Controllers/DashboardController.php`)

**BEFORE:**
```php
// Developer users → redirect to Developer Center
if ($user->is_developer) {
    return redirect()->route('developer.dashboard');
}
```

**AFTER:**
```php
// ============================================================
// DEVELOPER ACCESS - Allow developers to view tenant dashboard
// ============================================================
// Developers can access ANY dashboard for administration purposes.
```

### 3. TenantScope (`app/Core/Scopes/TenantScope.php`)

**BEFORE:**
```php
// Skip for developers - they have access to all tenants
if ($user && $user->is_developer) {
    return null;
}
```

**AFTER:**
```php
// ============================================================
// DEVELOPER BYPASS - Developers see all data across all tenants
// ============================================================
if ($user && $user->is_developer) {
    return null; // null = no scope applied = see all data
}
// ============================================================
```

### 4. CompanyScope (`app/Core/Scopes/CompanyScope.php`)

**BEFORE:**
```php
// Developer and pusat admin can see all - don't apply scope
if ($user->is_developer || $user->is_pusat_admin) {
    return null;
}
```

**AFTER:**
```php
// ============================================================
// DEVELOPER & PUSAT ADMIN BYPASS
// Developers and Pusat Admins see ALL data across all companies
// ============================================================
if ($user->is_developer || $user->is_pusat_admin) {
    return null; // null = no scope applied = see all data
}
// ============================================================
```

## Middleware Summary

| Middleware | Purpose | Developer Bypass |
|------------|---------|-----------------|
| `auth` | Ensure user is logged in | No - always required |
| `developer` | Restrict to Developer only | N/A - only for developer routes |
| `tenant` (ResolveTenant) | Set current tenant context | Yes - developers pass through |
| `tenant.auth` (TenantMiddleware) | Ensure user belongs to company | **Yes - developers bypass** |
| `module.access` | Check module subscription | Yes - developers have all access |

## Authorization Matrix

| Route Type | User Type | Auth Required | Company Required | Data Scope |
|------------|-----------|---------------|------------------|------------|
| `/developer/*` | Developer | ✓ | ✗ | All |
| `/developer/*` | Tenant | ✓ | ✓ | Own Company |
| `/dashboard` | Developer | ✓ | ✗ | **All** |
| `/dashboard` | Tenant | ✓ | ✓ | Own Company |
| `/clients` | Developer | ✓ | ✗ | **All** |
| `/clients` | Tenant | ✓ | ✓ | Own Company |

## Developer Capabilities

After this refactor, Developers can:

1. ✅ Access `/dashboard` without 403 errors
2. ✅ View all tenant data across all companies
3. ✅ Perform administrative tasks across all tenants
4. ✅ Debug issues by viewing tenant data
5. ✅ See system-wide statistics
6. ✅ Access Developer Center (`/developer/*`)
7. ✅ NOT require `company_id` to access routes

## Why This Works

The key insight is that **authorization is handled at multiple layers**:

1. **Route Layer**: Routes are separated by prefix (`/developer/*` vs `/dashboard`)
2. **Middleware Layer**: Different middleware sets for different route types
3. **Controller Layer**: Controllers check user type and adjust data scope
4. **Database Layer**: Scopes apply data isolation (or not, for developers)

This is called **defense in depth** - multiple layers ensure proper authorization.

## Migration Notes

If you have custom controllers that need to handle developer access:

```php
public function index(Request $request)
{
    $user = auth()->user();

    $query = Model::query();

    // For developers, don't apply tenant/company scope
    if (!$user->is_developer && !$user->is_pusat_admin) {
        $query->where('company_id', $user->company_id);
    }

    // Developers see all data
    return $query->get();
}
```

## Related Files

- `app/Http/Middleware/TenantMiddleware.php` - Main fix for 403 errors
- `app/Modules/System/Http/Controllers/DashboardController.php` - Dashboard access
- `app/Core/Scopes/TenantScope.php` - Data isolation
- `app/Core/Scopes/CompanyScope.php` - Data isolation
- `app/Core/Middleware/ResolveTenant.php` - Tenant resolution
- `app/Http/Middleware/CheckModuleAccess.php` - Module access (already handles developers)
- `routes/web.php` - Route definitions
- `routes/developer.php` - Developer route definitions
- `bootstrap/app.php` - Middleware registration
