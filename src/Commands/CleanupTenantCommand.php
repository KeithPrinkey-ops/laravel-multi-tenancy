<?php

namespace Worldesports\MultiTenancy\Commands;

use Exception;
use Illuminate\Console\Command;
use Worldesports\MultiTenancy\Models\Tenant;
use Worldesports\MultiTenancy\Models\TenantDatabase;

class CleanupTenantCommand extends Command
{
    public $signature = 'tenant:cleanup
                        {tenant : The tenant ID to cleanup}
                        {--drop-database : Actually drop the database (use with caution)}
                        {--force : Skip confirmation prompts}';

    public $description = 'Cleanup tenant and optionally drop their database';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant');
        $dropDatabase = $this->option('drop-database');
        $force = $this->option('force');

        $tenant = Tenant::with('databases')->find($tenantId);
        if (! $tenant) {
            $this->error("Tenant with ID $tenantId not found.");

            return self::FAILURE;
        }

        if (! $force) {
            $this->warn("⚠️  You are about to cleanup tenant: {$tenant->name} (ID: {$tenant->id})");
            if ($dropDatabase) {
                $this->error("⚠️  This will also DROP the tenant's databases permanently!");
            }

            if (! $this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        try {
            // Cleanup databases if requested
            if ($dropDatabase) {
                foreach ($tenant->databases as $database) {
                    $this->cleanupDatabase($database);
                }
            }

            // Delete tenant record (will cascade to databases and metadata)
            $tenant->delete();

            $this->info("✅ Tenant '{$tenant->name}' has been cleaned up successfully.");

            if ($dropDatabase) {
                $this->info('✅ All tenant databases have been dropped.');
            } else {
                $this->info('ℹ️  Tenant databases were not dropped. Use --drop-database to remove them.');
            }

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("Cleanup failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function cleanupDatabase(TenantDatabase $database): void
    {
        $this->info("Dropping database: {$database->name}");

        try {
            $connectionDetails = $database->connection_details;

            // Connect to MySQL without specifying database
            $dsn = "{$connectionDetails['driver']}:host={$connectionDetails['host']};port={$connectionDetails['port']}";
            $pdo = new \PDO($dsn, $connectionDetails['username'], $connectionDetails['password']);

            // Drop the database
            $pdo->exec("DROP DATABASE IF EXISTS `{$connectionDetails['database']}`");

            $this->info("  ✅ Database '{$connectionDetails['database']}' dropped successfully.");

        } catch (Exception $e) {
            $this->error("  ❌ Failed to drop database '{$database->name}': {$e->getMessage()}");
            throw $e;
        }
    }
}
