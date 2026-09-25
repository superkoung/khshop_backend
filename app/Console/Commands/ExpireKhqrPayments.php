<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExpireKhqrPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'khqr:expire-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel pending KHQR (Bakong) orders whose payment window expired and restore stock exactly once';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timeout = (int) config('bakong.payment_timeout', 600);
        if ($timeout <= 0) {
            $timeout = 600;
        }

        $cutoff = now()->subSeconds($timeout);

        /*
         * Candidate orders: still pending + not paid + have a Bakong payment
         * record older than the payment window. The existence of a Bakong
         * payment record is the KHQR-order marker (COD orders have none).
         */
        $orderIds = Order::query()
            ->where('status', 'pending')
            ->where('payment_status', '!=', 'paid')
            ->whereHas('payments', function ($q) use ($cutoff) {
                $q->where('method', 'bakong')
                    ->where('created_at', '<=', $cutoff);
            })
            ->pluck('id');

        $expired = 0;

        foreach ($orderIds as $orderId) {
            if ($this->expireOrder((int) $orderId, $timeout)) {
                $expired++;
            }
        }

        $this->info("Expired {$expired} KHQR order(s).");

        return self::SUCCESS;
    }

    /**
     * Cancel one order and restore stock if — and only if — it is still
     * pending/unpaid when re-checked under a row lock (payment race safe).
     */
    protected function expireOrder(int $orderId, int $timeout): bool
    {
        return (bool) DB::transaction(function () use ($orderId, $timeout) {
            $order = Order::whereKey($orderId)->lockForUpdate()->first();

            if (!$order || $order->status !== 'pending' || $order->payment_status === 'paid') {
                // Payment verification won the race — do not cancel, do not restore.
                return false;
            }

            $bakongPayments = Payment::where('order_id', $order->id)
                ->where('method', 'bakong')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            if ($bakongPayments->isEmpty()) {
                return false;
            }

            // A completed Bakong payment means the money arrived — never cancel.
            if ($bakongPayments->contains('status', 'completed')) {
                return false;
            }

            $latest = $bakongPayments->first();

            // A newer QR may have been generated (window restarted) meanwhile.
            if ($latest->created_at->copy()->addSeconds($timeout)->isFuture()) {
                return false;
            }

            $bakongPayments
                ->where('status', 'pending')
                ->each(function (Payment $payment) {
                    $payment->update(['status' => 'expired']);
                });

            // Restore stock exactly once (this row lock + status check above
            // ensure no other cancel path can restore the same items again).
            // Query-builder increment keyed by variant_id: independent of
            // relation loading and soft-delete global scopes.
            $restored = 0;
            $order->items()->get(['id', 'variant_id', 'qty'])->each(function ($item) use (&$restored) {
                if ($item->variant_id && (int) $item->qty > 0) {
                    DB::table('product_variants')
                        ->where('id', $item->variant_id)
                        ->increment('stock', $item->qty);
                    $restored++;
                }
            });

            $order->update(['status' => 'cancelled']);

            Cache::flush();

            $this->info("  Order #{$order->id}: cancelled, stock restored for {$restored} item(s).");

            return true;
        });
    }
}
