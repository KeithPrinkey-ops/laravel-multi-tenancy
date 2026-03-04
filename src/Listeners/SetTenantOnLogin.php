<?php

namespace Worldesports\MultiTenancy\Listeners;

use Illuminate\Auth\Events\Login;
use Worldesports\MultiTenancy\Facades\MultiTenancy;
use Worldesports\MultiTenancy\Models\Tenant;

class SetTenantOnLogin
{
    public function handle(Login $event): void
    {
        /** @var \Illuminate\Database\Eloquent\Model $user */
        $user = $event->user;

        $tenant = Tenant::where('user_id', $user->id)->first();
        if ($tenant) {
            MultiTenancy::setTenant($tenant);
        }
    }
}
