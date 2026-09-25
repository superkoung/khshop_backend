<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $orderLabel,
        public string $status
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status',
            'title' => 'Order Status',
            'message' => "Order {$this->orderLabel} is {$this->status}",
            'order_id' => $this->orderId,
            'status' => $this->status,
        ];
    }
}
