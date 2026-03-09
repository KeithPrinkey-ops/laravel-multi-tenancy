<?php

namespace Worldesports\MultiTenancy\Listeners;

use Worldesports\MultiTenancy\Facades\MultiTenancy;
use Worldesports\MultiTenancy\Models\Tenant;

class CleanupTenantOnDeletion
{
    public function handle($event): void
    {
        // This listener can be attached to custom tenant deletion events
        if (isset($event->tenant) && $event->tenant instanceof Tenant) {
            $tenant = $event->tenant;

            try {
                // Reset current tenant if it's the one being deleted
                if (MultiTenancy::hasTenant() && MultiTenancy::getTenantId() === $tenant->id) {
                    MultiTenancy::resetTenant();
                }

                // Log the deletion
                \Log::info("Tenant deleted: {$tenant->name} (ID: {$tenant->id})");

                // Optional: Cleanup database connections
                MultiTenancy::purgeConnections();

            } catch (\Exception $e) {
                \Log::error("Error during tenant deletion cleanup: {$e->getMessage()}");
            }
        }
    }
}
