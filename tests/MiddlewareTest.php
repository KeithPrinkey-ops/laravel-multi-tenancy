<?php

namespace Worldesports\MultiTenancy\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Worldesports\MultiTenancy\Facades\MultiTenancy;
use Worldesports\MultiTenancy\Middleware\SetTenant;
use Worldesports\MultiTenancy\Models\Tenant;
use Worldesports\MultiTenancy\Models\TenantDatabase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runPackageMigrations();

        // Create test user table and user
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
    public function middleware_sets_tenant_for_authenticated_user()
    {
        // Create tenant for user
        $tenant = $this->createTenantWithDatabase();

        // Create authenticated user mock
        $user = (object) ['id' => 1];

        // Create request with authenticated user
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Ensure no tenant is set initially
        expect(MultiTenancy::hasTenant())->toBeFalse();

        // Process request through middleware
        $middleware = new SetTenant;
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Check that tenant was set
        expect(MultiTenancy::hasTenant())->toBeTrue();
        expect(MultiTenancy::getTenant()->id)->toBe($tenant->id);
        expect($response->getContent())->toBe('OK');
    }

    /** @test */
    public function middleware_does_nothing_for_unauthenticated_user()
    {
        // Create request without authenticated user
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () {
            return null;
        });

        // Ensure no tenant is set initially
        expect(MultiTenancy::hasTenant())->toBeFalse();

        // Process request through middleware
        $middleware = new SetTenant;
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Check that no tenant was set
        expect(MultiTenancy::hasTenant())->toBeFalse();
        expect($response->getContent())->toBe('OK');
    }

    /** @test */
    public function middleware_handles_user_without_tenant_gracefully()
    {
        // Create authenticated user mock (user ID 2 doesn't have a tenant)
        $user = (object) ['id' => 2];

        // Create request with authenticated user
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Ensure no tenant is set initially
        expect(MultiTenancy::hasTenant())->toBeFalse();

        // Process request through middleware
        $middleware = new SetTenant;
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Check that no tenant was set but request continued normally
        expect(MultiTenancy::hasTenant())->toBeFalse();
        expect($response->getContent())->toBe('OK');
    }

    protected function createTenantWithDatabase(): Tenant
    {
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
                'prefix' => '',
            ],
        ]);

        return $tenant->fresh(['databases']);
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
