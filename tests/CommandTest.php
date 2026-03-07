<?php

namespace Worldesports\MultiTenancy\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Worldesports\MultiTenancy\Models\Tenant;
use Worldesports\MultiTenancy\Models\TenantDatabase;

class CommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runPackageMigrations();

        // Create users table and test user
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function create_tenant_command_creates_tenant_with_database()
    {
        $this->artisan('tenant:create', [
            'user_id' => 1,
            'name' => 'Test Company',
            '--db-name' => 'test_tenant_db',
            '--db-username' => 'test_user',
            '--db-password' => 'test_pass',
            '--db-driver' => 'sqlite',
            '--db-host' => ':memory:',
        ])
            ->expectsOutput('✅ Tenant \'Test Company\' created successfully!')
            ->assertExitCode(0);

        // Verify tenant was created
        $tenant = Tenant::where('user_id', 1)->first();
        expect($tenant)->not->toBeNull();
        expect($tenant->name)->toBe('Test Company');

        // Verify tenant database was created
        $tenantDb = $tenant->databases()->first();
        expect($tenantDb)->not->toBeNull();
        expect($tenantDb->name)->toBe('test_tenant_db');
        expect($tenantDb->connection_details['driver'])->toBe('sqlite');
        expect($tenantDb->connection_details['username'])->toBe('test_user');
    }

    /** @test */
    public function create_tenant_command_fails_for_nonexistent_user()
    {
        $this->artisan('tenant:create', [
            'user_id' => 999, // Non-existent user
            'name' => 'Test Company',
            '--db-name' => 'test_tenant_db',
            '--db-username' => 'test_user',
            '--db-password' => 'test_pass',
        ])
            ->expectsOutput('User with ID 999 not found.')
            ->assertExitCode(1);

        // Verify no tenant was created
        expect(Tenant::count())->toBe(0);
    }

    /** @test */
    public function create_tenant_command_fails_if_tenant_already_exists()
    {
        // Create existing tenant
        Tenant::create([
            'user_id' => 1,
            'name' => 'Existing Company',
        ]);

        $this->artisan('tenant:create', [
            'user_id' => 1,
            'name' => 'Test Company',
            '--db-name' => 'test_tenant_db',
            '--db-username' => 'test_user',
            '--db-password' => 'test_pass',
        ])
            ->expectsOutput('Tenant already exists for user ID 1.')
            ->assertExitCode(1);

        // Verify only one tenant exists
        expect(Tenant::count())->toBe(1);
    }

    /** @test */
    public function create_tenant_command_validates_required_database_credentials()
    {
        $this->artisan('tenant:create', [
            'user_id' => 1,
            'name' => 'Test Company',
            '--db-name' => 'test_tenant_db',
            // Missing username and password
        ])
            ->expectsOutput('Database username and password are required.')
            ->assertExitCode(1);

        // Verify no tenant was created
        expect(Tenant::count())->toBe(0);
    }

    /** @test */
    public function tenant_status_command_shows_no_tenants_message()
    {
        $this->artisan('tenant:status')
            ->expectsOutput('🏢 Multi-Tenancy Status')
            ->expectsOutput('Total Tenants: 0')
            ->expectsOutput('No tenants found. Create one with: php artisan tenant:create')
            ->assertExitCode(0);
    }

    /** @test */
    public function tenant_status_command_shows_existing_tenants()
    {
        // Create tenant with database
        $tenant = Tenant::create([
            'user_id' => 1,
            'name' => 'Test Company',
        ]);

        TenantDatabase::create([
            'tenant_id' => $tenant->id,
            'name' => 'test_tenant_db',
            'connection_details' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $this->artisan('tenant:status')
            ->expectsOutput('🏢 Multi-Tenancy Status')
            ->expectsOutput('Total Tenants: 1')
            ->expectsOutput('Tenant List:')
            ->expectsOutputToContain("ID: {$tenant->id} | Name: Test Company | User ID: 1")
            ->expectsOutputToContain('Database: test_tenant_db')
            ->assertExitCode(0);
    }

    /** @test */
    public function tenant_status_command_shows_tenants_without_databases()
    {
        // Create tenant without database
        $tenant = Tenant::create([
            'user_id' => 1,
            'name' => 'Test Company No DB',
        ]);

        $this->artisan('tenant:status')
            ->expectsOutput('🏢 Multi-Tenancy Status')
            ->expectsOutput('Total Tenants: 1')
            ->expectsOutputToContain("ID: {$tenant->id} | Name: Test Company No DB | User ID: 1")
            ->expectsOutputToContain('No databases configured')
            ->assertExitCode(0);
    }

    protected function runPackageMigrations(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('tenant_databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('connection_details');
            $table->timestamps();
        });

        Schema::create('tenant_database_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_database_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }
}
