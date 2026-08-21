<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Configuration
    |--------------------------------------------------------------------------
    */
    'tenant' => [
        'cache_enabled' => env('TENANT_CACHE_ENABLED', true),
        'cache_ttl' => env('TENANT_CACHE_TTL', 3600),
        'auto_scope' => env('TENANT_AUTO_SCOPE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'ignore_fields' => ['updated_at', 'remember_token'],
        'log_batch' => true,
        'retention_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Configuration
    |--------------------------------------------------------------------------
    */
    'activity' => [
        'enabled' => env('ACTIVITY_ENABLED', true),
        'log_login' => true,
        'log_logout' => true,
        'log_crud' => true,
        'retention_days' => 180,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Roles
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'super_admin' => ['name' => 'Super Admin', 'description' => 'Full system access'],
        'director' => ['name' => 'Director', 'description' => 'Executive level access'],
        'admin' => ['name' => 'Administrator', 'description' => 'Administrative access'],
        'manager' => ['name' => 'Manager', 'description' => 'Management level access'],
        'staff' => ['name' => 'Staff', 'description' => 'Standard staff access'],
        'client' => ['name' => 'Client', 'description' => 'External client portal access'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'default' => [
            'client_management',
            'lead_management',
            'project_management',
            'task_management',
            'helpdesk',
            'knowledge_base',
        ],
        'professional' => [
            'proposal_estimate',
            'contract_management',
            'invoice_payment',
            'subscription_management',
            'advanced_reports',
            'multi_language',
        ],
        'enterprise' => [
            'workflow_automation',
            'custom_approval',
            'api_access',
            'white_label',
            'advanced_analytics',
            'sso_saml',
        ],
    ],
];
