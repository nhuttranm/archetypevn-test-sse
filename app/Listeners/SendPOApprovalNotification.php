<?php

namespace App\Listeners;

use App\Events\PurchaseOrderApproved;
use App\Notifications\POApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPOApprovalNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The queue connection that should be used.
     */
    public string $connection = 'redis';

    /**
     * The queue that the job should be sent to.
     */
    public string $queue = 'notifications';

    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderApproved $event): void
    {
        $po = $event->purchaseOrder;
        $creator = $po->creator;

        // Notify the PO creator
        $creator->notify(new POApprovedNotification($po, $event->approvedBy));
    }

    /**
     * Determine the number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Determine the number of seconds before the job should be retried.
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }
}
