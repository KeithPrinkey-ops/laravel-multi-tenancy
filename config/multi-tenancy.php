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
    'user_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Main Database Connection
    |--------------------------------------------------------------------------
    |
    | The main database connection that will be used for tenant management
    | and when no tenant is active.
    |
    */
    'main_connection' => config('database.default', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Automatic Tenant Detection
    |--------------------------------------------------------------------------
    |
    | Configure how tenants are automatically detected when users log in.
    |
    */

    // Auto-detect tenant by email domain (user@company.com -> Company tenant)
    'auto_detect_by_email' => true,

    // Auto-detect tenant by subdomain (tenant1.app.com -> tenant1)
    'auto_detect_by_subdomain' => false,

    // Auto-create tenant for users without existing tenant
    'auto_create_tenant' => false,

    // Auto-create database when creating tenant
    'auto_create_database' => false,

    /*
    |--------------------------------------------------------------------------
    | Default Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automatically created tenants.
    |
    */
    'default_tenant_name' => 'Tenant for :name',

    /*
    |--------------------------------------------------------------------------
    | Subdomain Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for subdomain-based tenant detection.
    |
    */
    'subdomain' => [
        'excluded' => ['www', 'app', 'api', 'admin'], // Subdomains to ignore
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimizations.
    |
    */
    'cache_connections' => true,

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configurations.
    |
    */
    'encrypt_connection_details' => false,

    'security' => [
        'check_user_tenant_access' => true, // Verify user has access to tenant
        'log_tenant_switches' => true, // Log when tenants are switched
        'max_connection_attempts' => 3, // Max attempts for database connections
    ],
];
