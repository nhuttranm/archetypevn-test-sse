<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class POApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected PurchaseOrder $purchaseOrder,
        protected User $approvedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Purchase Order {$this->purchaseOrder->po_number} Approved")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your purchase order **{$this->purchaseOrder->po_number}** has been approved.")
            ->line("**Amount:** $" . number_format($this->purchaseOrder->total_amount, 2))
            ->line("**Approved by:** {$this->approvedBy->name}")
            ->action('View Purchase Order', url("/purchase-orders/{$this->purchaseOrder->id}"))
            ->line('Thank you for using our Purchase Order System!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'po_approved',
            'purchase_order_id' => $this->purchaseOrder->id,
            'po_number' => $this->purchaseOrder->po_number,
            'approved_by' => $this->approvedBy->name,
            'total_amount' => $this->purchaseOrder->total_amount,
            'message' => "PO {$this->purchaseOrder->po_number} has been approved by {$this->approvedBy->name}",
        ];
    }
}
