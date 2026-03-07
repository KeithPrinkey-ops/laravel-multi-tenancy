<?php

namespace Worldesports\MultiTenancy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallMultiTenancyCommand extends Command
{
    public $signature = 'tenant:install
                        {--force : Overwrite existing files}
                        {--migrate : Run migrations after installation}
                        {--seed : Run seeders after migration}';

    public $description = 'Install the multi-tenancy package';

    public function handle(): int
    {
        $this->info('🚀 Installing Laravel Multi-Tenancy Package');
        $this->line('');

        // Publish config
        $this->info('Publishing configuration file...');
        $configResult = Artisan::call('vendor:publish', [
            '--tag' => 'multi-tenancy-config',
            '--force' => $this->option('force'),
        ]);

        if ($configResult === 0) {
            $this->info('✅ Configuration file published');
        } else {
            $this->warn('⚠️  Configuration file may already exist (use --force to overwrite)');
        }

        // Publish migrations
        $this->info('Publishing migration files...');
        $migrationResult = Artisan::call('vendor:publish', [
            '--tag' => 'multi-tenancy-migrations',
            '--force' => $this->option('force'),
        ]);

        if ($migrationResult === 0) {
            $this->info('✅ Migration files published');
        } else {
            $this->warn('⚠️  Migration files may already exist (use --force to overwrite)');
        }

        // Run migrations if requested
        if ($this->option('migrate')) {
            $this->info('Running migrations...');
            $migrateResult = Artisan::call('migrate');

            if ($migrateResult === 0) {
                $this->info('✅ Migrations completed');
            } else {
                $this->error('❌ Migration failed');

                return self::FAILURE;
            }

            // Run seeders if requested
            if ($this->option('seed')) {
                $this->info('Running database seeders...');
                Artisan::call('db:seed');
                $this->info('✅ Seeders completed');
            }
        }

        $this->line('');
        $this->info('🎉 Multi-tenancy package installation completed!');
        $this->line('');
        $this->comment('Next steps:');
        $this->line('1. Configure your database connections in config/multi-tenancy.php');
        $this->line('2. Run migrations if you haven\'t: php artisan migrate');
        $this->line('3. Create your first tenant: php artisan tenant:create');
        $this->line('4. Add the BelongsToTenant trait to your models');
        $this->line('');
        $this->comment('Documentation: Check the README.md for usage examples');

        return self::SUCCESS;
    }
}
