<?php

namespace Worldesports\MultiTenancy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Builder
 */
class TenantDatabase extends Model
{
    protected $guarded = [];
    protected $casts = [
        'connection_details' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(TenantDatabaseMetadata::class);
    }
}
