<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public User $rejectedBy,
        public string $reason
    ) {}
}
