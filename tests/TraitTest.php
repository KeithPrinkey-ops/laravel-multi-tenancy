<?php

namespace Worldesports\MultiTenancy\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Worldesports\MultiTenancy\Facades\MultiTenancy;
use Worldesports\MultiTenancy\Models\Tenant;
use Worldesports\MultiTenancy\Models\TenantDatabase;
use Worldesports\MultiTenancy\Traits\BelongsToTenant;

class TraitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runPackageMigrations();

        // Create a test model table that would use the trait
        Schema::create('test_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });

        // Create users table
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
    public function trait_uses_tenant_connection_when_tenant_is_set()
    {
        // Create tenant with database
        $tenant = $this->createTenantWithDatabase();
        MultiTenancy::setTenant($tenant);

        // Create model instance
        $post = new TestPost;

        // Check that model uses tenant connection
        expect($post->getConnectionName())->toBe(MultiTenancy::getCurrentConnectionName());
        expect($post->getConnectionName())->toContain('tenant_connection_');
    }

    /** @test */
    public function trait_uses_default_connection_when_no_tenant_is_set()
    {
        // Ensure no tenant is set
        MultiTenancy::resetTenant();

        // Create model instance
        $post = new TestPost;

        // Check that model uses default connection
        expect($post->getConnectionName())->toBe(config('database.default'));
        expect($post->getConnectionName())->not->toContain('tenant_connection_');
    }

    /** @test */
    public function scope_for_tenant_switches_to_specific_tenant_connection()
    {
        // Create two tenants with databases
        $tenant1 = $this->createTenantWithDatabase(1, 'Tenant 1');
        $tenant2 = $this->createTenantWithDatabase(2, 'Tenant 2'); // Need user 2

        // Add user 2
        DB::table('users')->insert([
            'id' => 2,
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Set tenant1 as current
        MultiTenancy::setTenant($tenant1);
        $originalConnection = MultiTenancy::getCurrentConnectionName();

        // Use scope to query for specific tenant
        $query = TestPost::forTenant($tenant2->id);

        // The query should be using tenant2's connection now
        expect($query->getConnection()->getName())->not->toBe($originalConnection);
        expect($query->getConnection()->getName())->toContain('tenant_connection_');
    }

    protected function createTenantWithDatabase(int $userId = 1, string $name = 'Test Company'): Tenant
    {
        $tenant = Tenant::create([
            'user_id' => $userId,
            'name' => $name,
        ]);

        TenantDatabase::create([
            'tenant_id' => $tenant->id,
            'name' => "test_tenant_{$userId}_db",
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

// Test model that uses the BelongsToTenant trait
class TestPost extends Model
{
    use BelongsToTenant;

    protected $table = 'test_posts';

    protected $guarded = [];
}
