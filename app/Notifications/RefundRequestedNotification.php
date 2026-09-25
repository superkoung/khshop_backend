<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Structure only — wired when a refund/return workflow exists.
 * Current KhShop DB has payment_status='refunded' but no refund request flow.
 */
class RefundRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $orderLabel,
        public string $message = 'Customer requested refund'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'refund',
            'title' => 'Refund / Return',
            'message' => "{$this->message} for order {$this->orderLabel}",
            'order_id' => $this->orderId,
        ];
    }
}
