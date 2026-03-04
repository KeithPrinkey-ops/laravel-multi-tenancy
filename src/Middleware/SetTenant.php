<?php

namespace Worldesports\MultiTenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Worldesports\MultiTenancy\Facades\MultiTenancy;
use Worldesports\MultiTenancy\Models\Tenant;

class SetTenant
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            /** @var \Illuminate\Database\Eloquent\Model $user */
            $tenant = Tenant::where('user_id', $user->id)->first();
            if ($tenant) {
                MultiTenancy::setTenant($tenant);
                // Optionally switch to default database here
            }
        }

        return $next($request);
    }
}
