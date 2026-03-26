<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PORejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected PurchaseOrder $purchaseOrder,
        protected User $rejectedBy,
        protected string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Purchase Order {$this->purchaseOrder->po_number} Rejected")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your purchase order **{$this->purchaseOrder->po_number}** has been rejected.")
            ->line("**Reason:** {$this->reason}")
            ->line("**Rejected by:** {$this->rejectedBy->name}")
            ->action('View Purchase Order', url("/purchase-orders/{$this->purchaseOrder->id}"))
            ->line('You may revise and resubmit the purchase order.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'po_rejected',
            'purchase_order_id' => $this->purchaseOrder->id,
            'po_number' => $this->purchaseOrder->po_number,
            'rejected_by' => $this->rejectedBy->name,
            'reason' => $this->reason,
            'message' => "PO {$this->purchaseOrder->po_number} has been rejected: {$this->reason}",
        ];
    }
}
