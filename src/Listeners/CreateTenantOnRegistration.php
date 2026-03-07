<?php

namespace Worldesports\MultiTenancy\Listeners;

use Illuminate\Auth\Events\Registered;
use Worldesports\MultiTenancy\Models\Tenant;

class CreateTenantOnRegistration
{
    public function handle(Registered $event): void
    {
        // Check if auto-tenant creation is enabled
        if (! config('multi-tenancy.auto_create_tenant', false)) {
            return;
        }

        $user = $event->user;

        // Check if tenant already exists
        if (Tenant::where('user_id', $user->id)->exists()) {
            return;
        }

        try {
            // Create tenant with default name
            $tenantName = config('multi-tenancy.default_tenant_name', 'Tenant for '.$user->name);

            Tenant::create([
                'user_id' => $user->id,
                'name' => $tenantName,
            ]);

            \Log::info("Auto-created tenant for user: {$user->id}");

        } catch (\Exception $e) {
            \Log::error("Failed to auto-create tenant for user {$user->id}: {$e->getMessage()}");
        }
    }
}
