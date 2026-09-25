<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $variantId,
        public string $productName,
        public int $stock
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'title' => 'Low Stock',
            'message' => "{$this->productName} — only {$this->stock} left",
            'variant_id' => $this->variantId,
            'stock' => $this->stock,
        ];
    }
}
