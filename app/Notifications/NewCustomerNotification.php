<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCustomerNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $customerId,
        public string $customerName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_customer',
            'title' => 'New Customer',
            'message' => "New customer registered: {$this->customerName}",
            'customer_id' => $this->customerId,
        ];
    }
}
