<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Structure only — no automatic monthly report scheduler exists in KhShop.
 * Wire this when report generation becomes an explicit backend process.
 * Admin-only recipients (never staff).
 */
class ReportNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $message = 'Monthly sales report is ready',
        public ?string $reportKey = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report',
            'title' => 'Report',
            'message' => $this->message,
            'report_key' => $this->reportKey,
        ];
    }
}
