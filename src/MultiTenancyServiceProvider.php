<?php

namespace Worldesports\MultiTenancy;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Worldesports\MultiTenancy\Commands\CreateTenantCommand;
use Worldesports\MultiTenancy\Commands\MultiTenancyCommand;
use Worldesports\MultiTenancy\Commands\TenantMigrateCommand;

class MultiTenancyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
                ->name('multi-tenancy')
                ->hasConfigFile()
                ->hasViews()
                ->hasMigration('create_tenant_table')
                ->hasMigration('create_tenant_databases_table')
                ->hasMigration('create_tenant_database_metadata_table')
                ->hasCommand(MultiTenancyCommand::class)
                ->hasCommand(CreateTenantCommand::class)
                ->hasCommand(TenantMigrateCommand::class);
    }
}
