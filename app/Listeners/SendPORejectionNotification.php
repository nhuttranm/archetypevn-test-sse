<?php

namespace App\Listeners;

use App\Events\PurchaseOrderRejected;
use App\Notifications\PORejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPORejectionNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $connection = 'redis';
    public string $queue = 'notifications';
    public int $tries = 3;

    public function handle(PurchaseOrderRejected $event): void
    {
        $po = $event->purchaseOrder;
        $creator = $po->creator;

        $creator->notify(new PORejectedNotification($po, $event->rejectedBy, $event->reason));
    }

    public function backoff(): array
    {
        return [30, 60, 120];
    }
}
