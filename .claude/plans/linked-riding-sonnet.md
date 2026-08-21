# Developer Center & Addons Management - Implementation Plan

## Context

The CRM has outgrown its single-tenant model and is being transformed into a SaaS platform. A **Developer** role (Super Administrator) is needed to manage all tenants, licenses, payments, and addons from a dedicated Developer Center. The existing codebase already has:
- A skeleton Developer Center layout (`resources/views/developer/layouts/app.blade.php`)
- 9 Developer controllers (mostly stubs)
- The existing `Marketplace` menu needs to be renamed to `Addons`
- The existing module/license/payment models are mature and reusable

This plan builds on top of what exists — extending controllers, completing views, and wiring the audit trail — rather than rebuilding.

---

## Implementation Overview

### Files to Create (New)
- `app/Services/DeveloperAuditService.php` — centralized audit logging service
- `resources/views/developer/licenses/index.blade.php` — License Manager view
- `resources/views/developer/licenses/show.blade.php` — License detail view
- `resources/views/developer/licenses/partials/manual-activation-modal.blade.php`
- `resources/views/developer/subscriptions/index.blade.php` — Subscriptions management view
- `resources/views/developer/subscriptions/partials/subscription-modal.blade.php`
- `resources/views/developer/gateways/index.blade.php` — Payment Gateway list
- `resources/views/developer/gateways/show.blade.php` — Gateway config detail
- `resources/views/developer/companies/show.blade.php` — Complete with tabs (replace existing)
- `resources/views/developer/companies/partials/tab-users.blade.php`
- `resources/views/developer/companies/partials/tab-subscriptions.blade.php`
- `resources/views/developer/companies/partials/tab-addons.blade.php`
- `resources/views/developer/companies/partials/tab-activity.blade.php`
- `resources/views/developer/addons/edit.blade.php` — Addon edit (replace existing minimal view)
- `resources/views/developer/addons/partials/pricing-form.blade.php`
- `resources/views/developer/payments/show.blade.php` — Enhanced payment detail
- `database/migrations/2026_06_25_xxxx_add_activity_log_category_for_developer.php` — add `developer` log_name constant

### Files to Modify

1. **`resources/views/components/sidebar.blade.php`**
   - Rename `Marketplace` label → `Addons`
   - Change icon from `fa-store` to `fa-puzzle-piece` (or keep)
   - Change route: `marketplace.index` → rename route (see routes)

2. **`routes/web.php`**
   - Add route alias: `marketplace.index` stays the same, but sidebar text changes
   - Add new developer routes: `/developer/companies/{company}/subscriptions`, `/developer/companies/{company}/addon/{module}/toggle`, `/developer/companies/{company}/addon/{module}/extend`

3. **`app/Http/Controllers/Developer/LicenseController.php`**
   - Implement `manualActivate()` with full logic
   - Add `filterTypes` scope (Active / Expired / Lifetime / Trial / All)
   - Log to audit service on every action

4. **`app/Http/Controllers/Developer/CompanyController.php`**
   - Add tab routes: `tabUsers()`, `tabSubscriptions()`, `tabAddons()`, `tabActivity()`
   - Add actions: `activateAddon()`, `deactivateAddon()`, `extendAddon()`, `makeLifetime()`
   - Log all actions via DeveloperAuditService

5. **`app/Http/Controllers/Developer/AddonController.php`**
   - Implement `update()` with pricing + description + trial changes
   - Add promo management: `setPromo()`, `removePromo()`
   - Add `toggleStatus()` for active/inactive
   - Log all changes via DeveloperAuditService

6. **`app/Http/Controllers/Developer/SubscriptionController.php`**
   - Implement `suspend()`, `activate()`, `extend()`, `cancel()`
   - Log all via DeveloperAuditService

7. **`app/Http/Controllers/Developer/PaymentController.php`**
   - Implement `retry()`, `refund()`, `markPaid()`, `cancel()`
   - Add failure reason detail: Gateway Timeout / Invalid Signature / Callback Failed / Merchant Error / Network Error
   - Log all via DeveloperAuditService

8. **`app/Http/Controllers/Developer/GatewayController.php`**
   - Implement `update()` with full config (API Key, Server Key, Merchant ID, Mode, Callback URL)
   - Implement `test()` with connection test
   - Implement `toggleActive()`
   - Implement `setDefault()`
   - Implement `viewLog()` for gateway-specific logs
   - Log all via DeveloperAuditService

9. **`app/Http/Controllers/Developer/MonitoringController.php`**
   - Add: Top Addons, Top Customers, Payment Success/Failed rate, Active Trials
   - Return richer stats array

10. **`app/Http/Controllers/Developer/AuditLogController.php`**
    - Filter by `log_name = 'developer'`
    - Show action, description, user, IP, timestamp
    - Link to related entity (company, addon, payment, license)

11. **`app/Models/CompanySubscription.php`**
    - Add `scopeActive()`, `scopeExpired()`, `scopeTrial()`, `scopeLifetime()`
    - Add `scopeByCompany()` for License Manager filtering

12. **`app/Models/ModuleTransaction.php`**
    - Add `scopePending()`, `scopeFailed()`, `scopeRefunded()`

13. **`app/Services/DeveloperAuditService.php`** (new)
    - `log(string $action, string $description, ?Model $subject = null, array $properties = [])`
    - Creates ActivityLog with `log_name = 'developer'`
    - Records: actor user, IP, user-agent, subject type/id, description, old/new values

14. **`resources/views/developer/logs/index.blade.php`**
    - Upgrade to filter by action type (Grant License, Remove License, Update Harga, Edit Gateway, Refund, Retry Callback, etc.)

15. **`resources/views/developer/monitoring/index.blade.php`**
    - Upgrade with charts (use simple CSS bars, no Chart.js dependency), Top Addons list, Top Customers list

16. **`resources/views/crm/marketplace/index.blade.php`**
    - Change page title and banner text from "Marketplace" to "Addons"

---

## Detailed Implementation Steps

### Phase 1: Sidebar & Route Rename

**`sidebar.blade.php` changes:**
```blade
{{-- Marketplace → Addons --}}
<x-sidebar-item
    href="{{ route('marketplace.index') }}"
    icon="fa-solid fa-puzzle-piece"
    text="Addons"
    :active="$isActive('marketplace')"
/>
```
The route name `marketplace.index` stays — only the display text changes.

### Phase 2: DeveloperAuditService

Create `app/Services/DeveloperAuditService.php`:

```php
public static function log(
    string $action,        // e.g. 'license.grant', 'addon.price.update', 'payment.refund'
    string $description,   // e.g. 'Granted Lifetime license for Finance Expert to PT ABC'
    ?Model $subject = null, // related model (Company, Module, ModuleTransaction, etc.)
    array $properties = [] // old_values, new_values, etc.
): void
```

This service is called at the end of every developer controller action. Uses the existing `ActivityLog` model with `log_name = 'developer'`.

### Phase 3: Complete LicenseController

Routes:
- `GET /developer/licenses` — list with filters (company, addon, expired, active, lifetime, trial)
- `GET /developer/licenses/{company}` — detail per company
- `POST /developer/licenses/{company}/manual-activate` — manual activation form
- `POST /developer/licenses/{company}/revoke` — revoke a license

Manual Activation Form fields:
- Module selection (dropdown of all modules)
- Activation type: Trial / Standard / Lifetime
- Start date, End date (hidden for Lifetime)
- Reason: Compensation / Manual Activation / Technical Issue / Promo / Custom

### Phase 4: Complete Company Detail Tabs

The existing `show.blade.php` has placeholder tabs. Replace with functional tabs:
- **Overview** — company info, quick stats
- **Users** — paginated user list with role display
- **Subscriptions** — all subscriptions with status badges
- **Purchased Addons** — each addon card with action buttons (Activate, Deactivate, Extend, Lifetime)
- **Activity Log** — company's recent activity from ActivityLog

### Phase 5: Complete SubscriptionController

Actions:
- `suspend(subscription)` — sets status to 'suspended'
- `activate(subscription)` — re-activates a suspended or expired subscription
- `extend(subscription, days)` — extends end_date by X days
- `cancel(subscription)` — cancels subscription

### Phase 6: Complete PaymentController

Add to `show.blade.php`:
- Invoice info (ID, Gateway, Transaction ID, Reference)
- Amount, Paid At, Status
- Failure reason (if failed): Gateway Timeout / Invalid Signature / Callback Failed / Merchant Error / Network Error
- Action buttons: Retry Callback / Mark As Paid / Refund / Cancel

### Phase 7: Complete GatewayController

Add to `gateways/show.blade.php`:
- Config form: API Key, Server Key, Merchant ID, Mode (Sandbox/Production), Callback URL
- Test Connection button (calls `/developer/gateways/{code}/test`)
- View Log button (shows recent gateway activity)
- Active/Inactive toggle
- Set as Default button

### Phase 8: Complete AddonController

Extend `addons/edit.blade.php`:
- Edit name, description, icon
- Edit monthly/yearly pricing (via nested form)
- Toggle active/inactive
- Set promo (discount %, start date, end date)
- Set trial days (0 to disable)
- Version field (read-only, set via code only)

### Phase 9: Complete MonitoringController

Enhanced `index()` returning:
```php
$stats = [
    'total_tenants' => Company::count(),
    'active_tenants' => Company::where('status', 'active')->count(),
    'trial_tenants' => CompanySubscription::where('status', 'trial')->count(),
    'expired_tenants' => CompanySubscription::where('status', 'expired')->count(),
    'revenue_this_month' => ...,
    'total_revenue' => ...,
    'payment_success_rate' => ...,
    'payment_failed' => ...,
    'top_addons' => [...],      // top 5 by revenue
    'top_customers' => [...],    // top 5 by revenue
];
```

### Phase 10: Complete AuditLogController

Upgrade logs view with:
- Filter by action category (License, Payment, Addon, Gateway, Subscription)
- Filter by date range
- Search by description
- Columns: Timestamp, User, Action, Description, IP, Subject
- Click to expand and see full details

---

## Reusable Patterns to Follow

1. **Controller pattern** (already in place): Each controller uses `developer.access` middleware, returns `View`, and ends with `DeveloperAuditService::log()`
2. **View pattern**: Developer views extend `developer.layouts.app`, use Tailwind CSS classes matching existing codebase style
3. **Audit pattern**: Every POST/PATCH/DELETE action logs via `DeveloperAuditService`
4. **Model scopes**: Use Eloquent scopes for all filters (`scopeActive()`, `scopeExpired()`, etc.)

---

## Verification Steps

1. **Login as Developer account** → verify Developer Center sidebar appears
2. **Login as regular company admin** → verify Developer Center sidebar is hidden; "Addons" menu appears (not "Marketplace")
3. **Navigate to /developer/companies** → click company → verify all tabs work
4. **Manual License Activation** → select company, module, activation type, submit → verify subscription created
5. **Payment Logs** → find a failed transaction → click "Retry Callback" → verify audit log entry
6. **Gateway Config** → edit gateway API key → click "Test Connection" → verify response
7. **Developer Logs** → verify all actions (grant license, edit addon, refund, etc.) appear with correct details
8. **System Monitoring** → verify stats match database counts

---

## Priority Order

1. Sidebar rename + DeveloperAuditService (foundation)
2. Audit log views + controller (all actions must be logged from the start)
3. LicenseController + License views (most critical for developer workflow)
4. Company detail tabs + Addon management actions
5. PaymentController + GatewayController
6. SubscriptionController
7. MonitoringController upgrade
