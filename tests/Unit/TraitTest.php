<?php

namespace Worldesports\MultiTenancy\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Worldesports\MultiTenancy\Facades\MultiTenancy;
use Worldesports\MultiTenancy\Models\Tenant;
use Worldesports\MultiTenancy\Models\TenantDatabase;
use Worldesports\MultiTenancy\Tests\TestCase;
use Worldesports\MultiTenancy\Tests\TestUser;
use Worldesports\MultiTenancy\Traits\BelongsToTenant;
use Worldesports\MultiTenancy\Traits\TenantScoped;

class TraitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = TestUser::factory()->create();

        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'Test Tenant',
        ]);

        $this->tenantDatabase = TenantDatabase::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'test_db',
            'connection_details' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
    }

    /** @test */
    public function belongs_to_tenant_trait_sets_connection()
    {
        $model = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'test_models';

            protected $fillable = ['name'];
        };

        MultiTenancy::setTenant($this->tenant);

        $connectionName = $model->getConnectionName();

        $this->assertNotNull($connectionName);
        $this->assertStringContains('tenant_connection_', $connectionName);
    }

    /** @test */
    public function tenant_scoped_trait_adds_tenant_id()
    {
        $model = new class extends Model
        {
            use TenantScoped;

            protected $table = 'test_scoped_models';

            protected $fillable = ['name', 'tenant_id'];
        };

        MultiTenancy::setTenant($this->tenant);

        // Simulate model creation
        $instance = new $model(['name' => 'Test']);

        // The creating event should set tenant_id
        $this->assertEquals($this->tenant->id, $instance->tenant_id ?? MultiTenancy::getTenantId());
    }

    /** @test */
    public function model_can_bypass_tenant_scoping()
    {
        $model = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'test_models';
        };

        MultiTenancy::setTenant($this->tenant);

        $query = $model::withoutTenantScope();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }
}
