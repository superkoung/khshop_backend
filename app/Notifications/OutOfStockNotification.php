<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OutOfStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $variantId,
        public string $productName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'out_of_stock',
            'title' => 'Out of Stock',
            'message' => "{$this->productName} — out of stock",
            'variant_id' => $this->variantId,
            'stock' => 0,
        ];
    }
}
