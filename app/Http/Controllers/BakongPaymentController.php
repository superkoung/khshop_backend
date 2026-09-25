<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentConfirmedNotification;
use App\Services\BakongService;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BakongPaymentController extends Controller
{
    public function __construct(
        protected BakongService $bakong
    ) {}

    /**
     * Create a Bakong/KHQR payment for an existing order.
     *
     * POST /api/v1/payment/bakong/create
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $user   = $request->user();
        $orderId = $validated['order_id'];

        // 1. Load order & verify ownership
        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // 2. Prevent payment for already-paid orders
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This order has already been paid.',
            ], 422);
        }

        // 3. Prevent payment for cancelled orders
        if ($order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot pay for a cancelled order.',
            ], 422);
        }

        // 4. Check for existing pending payment — reuse it
        $existingPayment = Payment::where('order_id', $order->id)
            ->where('method', 'bakong')
            ->where('status', 'pending')
            ->first();

        if ($existingPayment) {
            // Check if payment has not expired (10 min timeout)
            $timeout = config('bakong.payment_timeout', 600);
            if ($existingPayment->created_at->addSeconds($timeout)->isFuture()) {
                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id' => $existingPayment->id,
                        'order_id'   => $order->id,
                        'amount'     => (float) $existingPayment->amount_paid,
                        'currency'   => 'USD',
                        'qr'         => $existingPayment->qr,
                        'md5'        => $existingPayment->md5,
                        'expires_at' => (int) (
                            $existingPayment->created_at->timestamp * 1000
                            + ((int) $timeout * 1000)
                        ),
                        'polling_interval' => max(
                            1,
                            (int) config('bakong.polling_interval', 8)
                        ),
                    ],
                ]);
            }

            // Expired — mark as expired and create new one
            $existingPayment->update(['status' => 'expired']);
        }

        // 5. Generate KHQR via local SDK
        try {
            $amount   = (float) $order->net_amount;
            $orderRef = "ORD-{$order->id}";
            $khqrData = $this->bakong->generateKhqr($amount, $orderRef);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Invalid Bakong KHQR configuration', [
                'order_id' => $order->id,
                'amount' => $order->net_amount,
                'exception' => $e::class,
                'error' => mb_substr($e->getMessage(), 0, 400),
                'config_state' => $this->bakong->configPresence(),
            ]);

            $isConfigMessage = str_contains($e->getMessage(), 'configuration');

            return response()->json([
                'success' => false,
                'message' => $isConfigMessage
                    ? $e->getMessage()
                    : 'Unable to generate Bakong KHQR.',
            ], 422);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $isConfigError = str_contains($message, 'not configured');
            $isUpstream = str_contains($message, 'UPSTREAM_UNAVAILABLE')
                || $this->bakong->isUpstreamUnavailableMessage($message);

            Log::error('Bakong KHQR creation failed for order', [
                'order_id'     => $order->id,
                'amount'       => $order->net_amount,
                'exception'    => $e::class,
                'error'        => mb_substr($message, 0, 400),
                'config_state' => $this->bakong->configPresence(),
                'upstream'     => $isUpstream,
            ]);

            $payload = [
                'success' => false,
                'message' => $isConfigError
                    ? 'Bakong payment is not configured.'
                    : ($isUpstream
                        ? 'Bakong service is temporarily unavailable. Please try again.'
                        : 'Unable to generate Bakong KHQR.'),
            ];

            if ($isConfigError) {
                $payload['missing'] = $this->bakong->getMissingKhqrConfig();
            }

            return response()->json($payload, $isConfigError ? 503 : 502);
        }

        // 6. Create payment record
        $payment = Payment::create([
            'order_id'    => $order->id,
            'method'      => 'bakong',
            'amount_paid' => $amount,
            'qr'          => $khqrData['qr'],
            'md5'         => $khqrData['md5'],
            'status'      => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'payment_id' => $payment->id,
                'order_id'   => $order->id,
                'amount'     => $amount,
                'currency'   => 'USD',
                'qr'         => $khqrData['qr'],
                'md5'        => $khqrData['md5'],
                'expires_at' => $khqrData['expires_at'] ?? null,
                'polling_interval' => max(
                    1,
                    (int) config('bakong.polling_interval', 8)
                ),
            ],
        ], 201);
    }


    /**
     * Check / verify a Bakong payment.
     *
     * POST /api/v1/payment/bakong/check
     */
    public function check(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
        ]);

        $user = $request->user();
        $receivedPaymentId = (int) $validated['payment_id'];

        // Temporary safe trace — never log tokens/credentials.
        Log::info('Bakong check received', [
            'payment_id' => $receivedPaymentId,
            'user_id'    => $user->id,
        ]);

        // 1. Load payment with order
        $payment = Payment::with('order')->find($receivedPaymentId);

        if (!$payment) {
            Log::warning('Bakong check payment not found', [
                'payment_id' => $receivedPaymentId,
                'user_id'    => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment not found.',
            ], 404);
        }

        Log::info('Bakong check payment loaded', [
            'payment_id'  => $payment->id,
            'order_id'    => $payment->order_id,
            'status'      => $payment->status,
            'method'      => $payment->method,
            'md5_len'     => strlen((string) $payment->md5),
            'md5_present' => $payment->md5 !== null && $payment->md5 !== '',
            'is_test'     => config('bakong.is_test'),
        ]);

        // 2. Verify ownership
        if ($payment->order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // 3. Already completed — return current status (idempotent)
        if ($payment->status === 'completed') {
            return response()->json([
                'success' => true,
                'data'    => [
                    'payment_id' => $payment->id,
                    'status'     => 'completed',
                    'message'    => 'Payment already verified.',
                ],
            ]);
        }

        // 4. Verify with Bakong API (service uses payments.md5, not payment_id).
        // Verification runs BEFORE the timeout check so a payment Bakong has
        // already acknowledged is never lost to the expiry window.
        Log::info('Bakong check calling service', [
            'payment_id' => $payment->id,
            'identifier' => 'payments.md5',
            'md5_len'    => strlen((string) $payment->md5),
            'is_test'    => config('bakong.is_test'),
        ]);

        try {
            $result = $this->bakong->checkPayment($payment->md5);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $isConfigError = str_contains($message, 'not configured');
            $isUpstream = str_contains($message, 'UPSTREAM_UNAVAILABLE')
                || $this->bakong->isUpstreamUnavailableMessage($message);

            Log::error('Bakong verification failed', [
                'payment_id'  => $payment->id,
                'order_id'    => $payment->order_id,
                'exception'   => $e::class,
                'error'       => mb_substr($message, 0, 400),
                'error_type'  => $isConfigError ? 'config' : ($isUpstream ? 'temporary' : 'unknown'),
                'config_state'=> $this->bakong->configPresence(),
                'upstream'    => $isUpstream,
                'is_test'     => config('bakong.is_test'),
            ]);

            if ($isConfigError) {
                // Server misconfiguration (e.g. placeholder/missing BAKONG_TOKEN).
                // Keep HTTP 503 — do not fake 200. Frontend stops polling on this.
                return response()->json([
                    'success'    => false,
                    'message'    => 'Bakong payment verification is not configured on the server.',
                    'error_type' => 'config',
                    'missing'    => $this->bakong->getMissingApiConfig(),
                ], 503);
            }

            if ($isUpstream) {
                // Temporary Bakong/network failure (e.g. SIT CloudFront 403/5xx/timeout).
                // Clear 503 body — payment stays pending, not failed.
                // Frontend continues polling with backoff (not treated as payment failed).
                return response()->json([
                    'success'     => false,
                    'message'     => 'Bakong service is temporarily unavailable. Please keep this window open.',
                    'error_type'  => 'temporary',
                    'temporary'   => true,
                    'payment_id'  => $payment->id,
                ], 503);
            }

            return response()->json([
                'success'    => false,
                'message'    => 'Unable to verify payment right now. Please try again.',
                'error_type' => 'unknown',
            ], 502);
        }

        Log::info('Bakong check service result', [
            'payment_id'     => $payment->id,
            'status'         => $result['status'] ?? null,
            'transaction_id' => $result['transaction_id'] ?? null,
            'is_test'        => config('bakong.is_test'),
        ]);

        // 6. Handle result
        if ($result['status'] === 'completed') {
            // Idempotent: already completed returns earlier — this path runs once per confirmation.
            $order = $payment->order;
            $orderLabel = '#KH' . $order->id;

            DB::transaction(function () use ($payment, $result) {
                $payment->update([
                    'status'         => 'completed',
                    'transaction_id' => $result['transaction_id'],
                    'paid_at'        => now(),
                ]);

                // Lock the order row so this transition is serialized against
                // the khqr:expire-pending command (payment vs. expiry race).
                $order = Order::whereKey($payment->order_id)->lockForUpdate()->first();

                if ($order) {
                    if ($order->status === 'pending') {
                        // Payment verified: Pending → Processing.
                        // Stock was already deducted at order creation — never touched here.
                        $order->update([
                            'payment_status' => 'paid',
                            'status'         => 'processing',
                        ]);
                    } elseif ($order->status === 'cancelled') {
                        // Expiry cancellation won the race — record the payment
                        // but never revive the order or touch stock again.
                        $order->update(['payment_status' => 'paid']);
                    } else {
                        $order->update(['payment_status' => 'paid']);
                    }
                }

                // Cart clear only after verified payment success (bakong).
                $cart = Cart::where('user_id', $payment->order->user_id)->first();
                if ($cart) {
                    $cart->cartItems()->delete();
                }
            });

            NotificationDispatcher::toAdminStaffAndCustomer(
                new PaymentConfirmedNotification((int) $order->id, $orderLabel),
                $order->user
            );

            return response()->json([
                'success' => true,
                'data'    => [
                    'payment_id' => $payment->id,
                    'status'     => 'completed',
                    'message'    => 'Payment verified successfully.',
                ],
            ]);
        }

        // Failed is a final verdict from Bakong — never downgrade it to expired.
        if ($result['status'] === 'failed') {
            return response()->json([
                'success' => true,
                'data'    => [
                    'payment_id' => $payment->id,
                    'status'     => 'failed',
                    'message'    => 'Payment was not successful.',
                ],
            ]);
        }

        // Not paid — expire only once the payment window has passed.
        $timeout = config('bakong.payment_timeout', 600);
        if ($payment->created_at->addSeconds($timeout)->isPast()) {
            $payment->update(['status' => 'expired']);

            return response()->json([
                'success' => true,
                'data'    => [
                    'payment_id' => $payment->id,
                    'status'     => 'expired',
                    'message'    => 'Payment has expired.',
                ],
            ]);
        }

        // Still pending
        return response()->json([
            'success' => true,
            'data'    => [
                'payment_id' => $payment->id,
                'status'     => 'pending',
                'message'    => 'Payment is still pending. Please complete the payment using your banking app.',
            ],
        ]);
    }
}
