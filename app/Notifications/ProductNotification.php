<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $productId,
        public string $productName,
        public string $action = 'created' // created|updated
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'product',
            'title' => 'Product',
            'message' => $this->action === 'updated'
                ? "Product successfully updated: {$this->productName}"
                : "Product successfully added: {$this->productName}",
            'product_id' => $this->productId,
            'action' => $this->action,
        ];
    }
}
