<?php

namespace Worldesports\MultiTenancy\Commands;

use Illuminate\Console\Command;
use Worldesports\MultiTenancy\Facades\MultiTenancy;
use Worldesports\MultiTenancy\Models\Tenant;

class MultiTenancyCommand extends Command
{
    public $signature = 'tenant:status
                        {--list : Show detailed tenant list}
                        {--tenant= : Show specific tenant details}
                        {--connections : Test all tenant database connections}';

    public $description = 'Show tenant and database status with optional details';

    public function handle(): int
    {
        if ($this->option('connections')) {
            return $this->testConnections();
        }

        if ($this->option('tenant')) {
            return $this->showTenantDetails();
        }

        if ($this->option('list')) {
            return $this->showDetailedList();
        }

        // Default status view
        return $this->showStatus();
    }

    private function showStatus(): int
    {
        $this->info('🏢 Multi-Tenancy Status');
        $this->line('');

        $tenantCount = Tenant::count();
        $this->info("Total Tenants: {$tenantCount}");

        if ($tenantCount === 0) {
            $this->comment('No tenants found. Create one with: php artisan tenant:create');

            return self::SUCCESS;
        }

        $totalDatabases = \DB::table('tenant_databases')->count();
        $this->info("Total Databases: {$totalDatabases}");

        if (MultiTenancy::hasTenant()) {
            $currentTenant = MultiTenancy::getTenant();
            $this->info("Current Active Tenant: {$currentTenant->name} (ID: {$currentTenant->id})");
        } else {
            $this->comment('No active tenant in current context');
        }

        return self::SUCCESS;
    }

    private function showDetailedList(): int
    {
        $tenants = Tenant::with('databases', 'user')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return self::SUCCESS;
        }

        $this->info('📋 Detailed Tenant List');
        $this->line('');

        $headers = ['ID', 'Name', 'User', 'Databases', 'Created'];
        $rows = [];

        foreach ($tenants as $tenant) {
            $userName = $tenant->user ? $tenant->user->name : 'N/A';
            $rows[] = [
                $tenant->id,
                $tenant->name,
                $userName,
                $tenant->databases->count(),
                $tenant->created_at->format('Y-m-d H:i'),
            ];
        }

        $this->table($headers, $rows);

        return self::SUCCESS;
    }

    private function showTenantDetails(): int
    {
        $tenantId = $this->option('tenant');
        $tenant = Tenant::with('databases.metadata', 'user')->find($tenantId);

        if (! $tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");

            return self::FAILURE;
        }

        $this->info("🏢 Tenant: {$tenant->name}");
        $this->line('');
        $this->line("ID: {$tenant->id}");
        $this->line("User ID: {$tenant->user_id}");

        if ($tenant->user) {
            $this->line("User: {$tenant->user->name} ({$tenant->user->email})");
        }

        $this->line("Created: {$tenant->created_at->format('Y-m-d H:i:s')}");

        if ($tenant->databases->isNotEmpty()) {
            $this->line('');
            $this->info('Databases:');
            foreach ($tenant->databases as $database) {
                $this->line("  • {$database->name} (ID: {$database->id})");
                $details = $database->connection_details;
                $this->line("    Host: {$details['host']}:{$details['port']}");
                $this->line("    Database: {$details['database']}");
            }
        }

        return self::SUCCESS;
    }

    private function testConnections(): int
    {
        $this->info('🔌 Testing Tenant Database Connections');
        $this->line('');

        $tenants = Tenant::with('databases')->get();
        $totalTests = 0;
        $successfulTests = 0;

        foreach ($tenants as $tenant) {
            $this->line("Testing tenant: {$tenant->name}");

            foreach ($tenant->databases as $database) {
                $totalTests++;
                $this->line("  • {$database->name}... ", false);

                try {
                    $connectionName = MultiTenancy::setTenantDatabaseConnection($database);
                    \DB::connection($connectionName)->getPdo();
                    $this->info('✅ Connected');
                    $successfulTests++;
                } catch (\Exception $e) {
                    $this->error('❌ Failed');
                    $this->line("    Error: {$e->getMessage()}");
                }
            }
        }

        $this->line('');
        $this->info("Connection Test Results: {$successfulTests}/{$totalTests} successful");

        return $successfulTests === $totalTests ? self::SUCCESS : self::FAILURE;
    }
}
