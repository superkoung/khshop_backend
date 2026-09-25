<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $orderLabel,
        public string $customerName = ''
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = "New order {$this->orderLabel} received";
        if ($this->customerName !== '') {
            $message .= " from {$this->customerName}";
        }

        return [
            'type' => 'new_order',
            'title' => 'New Order',
            'message' => $message,
            'order_id' => $this->orderId,
        ];
    }
}
