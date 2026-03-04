<?php

namespace Worldesports\MultiTenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDatabaseMetadata extends Model
{
    protected $guarded = [];

    public function database(): BelongsTo
    {
        return $this->belongsTo(TenantDatabase::class, 'tenant_database_id');
    }
}
