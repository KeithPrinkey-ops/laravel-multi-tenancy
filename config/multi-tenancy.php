<?php

// config for Worldesports/MultiTenancy
return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class that will be used to determine tenant relationships.
    | This should be set to your application's User model class.
    |
    */
    'user_model' => env('MULTI_TENANT_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Main Database Connection
    |--------------------------------------------------------------------------
    |
    | The main database connection that will be used for tenant management
    | and when no tenant is active.
    |
    */
    'main_connection' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Auto Create Tenant
    |--------------------------------------------------------------------------
    |
    | Whether to automatically create a tenant when a user registers.
    | This requires the CreateTenantOnRegistration listener to be enabled.
    |
    */
    'auto_create_tenant' => env('MULTI_TENANT_AUTO_CREATE', false),

    /*
    |--------------------------------------------------------------------------
    | Default Tenant Name
    |--------------------------------------------------------------------------
    |
    | The default name format for auto-created tenants.
    | You can use :name placeholder which will be replaced with user's name.
    |
    */
    'default_tenant_name' => 'Tenant for :name',

    /*
    |--------------------------------------------------------------------------
    | Connection Cache
    |--------------------------------------------------------------------------
    |
    | Whether to cache database connections to improve performance.
    | Recommended to keep enabled unless debugging connection issues.
    |
    */
    'cache_connections' => env('MULTI_TENANT_CACHE_CONNECTIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    |
    | Whether to encrypt sensitive connection details in the database.
    | This adds security but may impact performance slightly.
    |
    */
    'encrypt_connection_details' => env('MULTI_TENANT_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security-related configurations.
    |
    */
    'security' => [
        'check_user_tenant_access' => true, // Verify user has access to tenant
        'log_tenant_switches' => true, // Log when tenants are switched
        'max_connection_attempts' => 3, // Max attempts for database connections
    ],
];
