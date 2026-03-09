<?php

namespace Worldesports\MultiTenancy\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Worldesports\MultiTenancy\Facades\MultiTenancy;
use Worldesports\MultiTenancy\Models\Tenant;
use Worldesports\MultiTenancy\Models\TenantDatabase;

class TenantSeedCommand extends Command
{
    public $signature = 'tenant:seed
                        {--tenant= : Specific tenant ID to seed}
                        {--database= : Specific database ID to seed}
                        {--class= : Specific seeder class to run}';

    public $description = 'Run database seeders on tenant databases';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $databaseId = $this->option('database');
        $seederClass = $this->option('class');

        try {
            if ($databaseId) {
                $database = TenantDatabase::find($databaseId);
                if (! $database instanceof TenantDatabase) {
                    $this->error("Database with ID $databaseId not found.");

                    return self::FAILURE;
                }

                return $this->runSeedForDatabase($database, $seederClass);
            }

            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                if (! $tenant instanceof Tenant) {
                    $this->error("Tenant with ID $tenantId not found.");

                    return self::FAILURE;
                }

                return $this->runSeedsForTenant($tenant, $seederClass);
            }

            // Run for all tenants
            $this->info('Running seeders for all tenants...');
            $tenants = Tenant::with('databases')->get();

            foreach ($tenants as $tenant) {
                $result = $this->runSeedsForTenant($tenant, $seederClass);
                if ($result === self::FAILURE) {
                    return self::FAILURE;
                }
            }

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("Seeding failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function runSeedsForTenant(Tenant $tenant, ?string $seederClass = null): int
    {
        $this->info("Running seeders for tenant: $tenant->name (ID: $tenant->id)");

        /** @var TenantDatabase $database */
        foreach ($tenant->databases as $database) {
            $result = $this->runSeedForDatabase($database, $seederClass);
            if ($result === self::FAILURE) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function runSeedForDatabase(TenantDatabase $database, ?string $seederClass = null): int
    {
        $this->info("  Processing database: $database->name");

        try {
            // Set up tenant connection
            $connectionName = MultiTenancy::setTenantDatabaseConnection($database);

            // Test connection
            DB::connection($connectionName)->getPdo();

            // Prepare seeder options
            $options = ['--database' => $connectionName, '--force' => true];

            if ($seederClass) {
                $options['--class'] = $seederClass;
            }

            // Run seeder
            $exitCode = Artisan::call('db:seed', $options);

            if ($exitCode === 0) {
                $this->info("    ✅ Seeders completed for $database->name");
            } else {
                $this->error("    ❌ Seeding failed for $database->name");
                $this->line('    '.Artisan::output());

                return self::FAILURE;
            }

        } catch (Exception $e) {
            $this->error("    ❌ Error with $database->name: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
