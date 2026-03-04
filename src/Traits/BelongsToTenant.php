<?php

namespace Worldesports\MultiTenancy\Traits;

use Worldesports\MultiTenancy\Facades\MultiTenancy;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (MultiTenancy::hasTenant()) {
                $builder->on(MultiTenancy::getCurrentConnectionName());
            }
        });
    }

    public function getConnectionName()
    {
        if (MultiTenancy::hasTenant() && MultiTenancy::getCurrentConnectionName()) {
            return MultiTenancy::getCurrentConnectionName();
        }

        return parent::getConnectionName();
    }

    public function scopeForTenant(Builder $query, ?int $tenantId = null): Builder
    {
        if ($tenantId) {
            // Switch to specific tenant connection temporarily
            $tenant = \Worldesports\MultiTenancy\Models\Tenant::find($tenantId);
            if ($tenant) {
                $database = $tenant->databases()->first();
                if ($database) {
                    $connectionName = MultiTenancy::setTenantDatabaseConnection($database);
                    return $query->on($connectionName);
                }
            }
        }

        if (MultiTenancy::hasTenant()) {
            return $query->on(MultiTenancy::getCurrentConnectionName());
        }

        return $query;
    }
}
