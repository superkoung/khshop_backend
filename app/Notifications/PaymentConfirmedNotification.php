<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $orderLabel
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment',
            'title' => 'Payment',
            'message' => "Order {$this->orderLabel} payment confirmed",
            'order_id' => $this->orderId,
        ];
    }
}
