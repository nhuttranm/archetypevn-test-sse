<?php

namespace App\Providers;

use App\Events\PurchaseOrderApproved;
use App\Events\PurchaseOrderRejected;
use App\Listeners\SendPOApprovalNotification;
use App\Listeners\SendPORejectionNotification;
use App\Models\PurchaseOrder;
use App\Policies\PurchaseOrderPolicy;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings.
     */
    protected $listen = [
        PurchaseOrderApproved::class => [
            SendPOApprovalNotification::class,
        ],
        PurchaseOrderRejected::class => [
            SendPORejectionNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
    }
}
