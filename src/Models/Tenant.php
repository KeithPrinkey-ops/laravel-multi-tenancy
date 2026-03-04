<?php

namespace Worldesports\MultiTenancy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

    /**
     * @mixin Builder
     */
    class Tenant extends Model
    {
        protected $guarded = [];

        public function user(): BelongsTo
        {
            return $this->belongsTo(config( 'multi-tenancy.user_model'));
        }

        public function databases(): HasMany
        {
            return $this->hasMany(TenantDatabase::class);
        }
    }
