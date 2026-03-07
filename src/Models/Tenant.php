<?php

namespace Worldesports\MultiTenancy\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, TenantDatabase> $databases
 *
 * @mixin Builder
 */
class Tenant extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('multi-tenancy.user_model', 'App\\Models\\User'));
    }

    public function databases(): HasMany
    {
        return $this->hasMany(TenantDatabase::class);
    }
}
