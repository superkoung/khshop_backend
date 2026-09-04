<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with(['orderItems.variant', 'payments'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->with(['orderItems.variant', 'payments'])
            ->where('order_id', $id)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $order
        ], 200);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'shipping_address'   => 'required|string',
            'receiver_phone'     => 'required|string',
            'order_type'         => 'nullable|string',
            'payment_method'     => 'required|string',
            'note'               => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.qty'        => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $subTotalSum = 0;
            $itemsToCreate = [];

            foreach ($validated['items'] as $itemData) {
                // ប្រើ lockForUpdate() ដើម្បីការពារ Concurrency Lock
                $variant = ProductVariant::where('id', $itemData['variant_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$variant) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Variant not found: {$itemData['variant_id']}"
                    ], 404);
                }

                if ($variant->stock < $itemData['qty']) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Insufficient stock for variant ID: {$itemData['variant_id']}. Available: {$variant->stock}"
                    ], 400);
                }

                $unitPrice = $variant->price_modifier ?? 0;
                $itemSubTotal = $unitPrice * $itemData['qty'];
                $subTotalSum += $itemSubTotal;

                // រក្សាទុក variant instance ចូលក្នុង array
                $itemsToCreate[] = [
                    'variant'    => $variant,
                    'variant_id' => $variant->id,
                    'qty'        => $itemData['qty'],
                    'unit_price' => $unitPrice,
                    'size'       => $variant->size_id ?? null,
                    'color'      => $variant->color_id ?? null,
                    'sub_total'  => $itemSubTotal,
                ];
            }

            $discountAmount = 0;
            $taxAmount = 0;
            $netAmount = ($subTotalSum + $taxAmount) - $discountAmount;

            $order = Order::create([
                'user_id'          => $user->id,
                'total_amount'     => $subTotalSum,
                'discount_amount'  => $discountAmount,
                'tax_amount'       => $taxAmount,
                'net_amount'       => $netAmount,
                'shipping_address' => $validated['shipping_address'],
                'receiver_phone'   => $validated['receiver_phone'],
                'status'           => 'pending',
                'order_type'       => $validated['order_type'] ?? 'delivery',
                'payment_status'   => 'unpaid',
                'note'             => $validated['note'] ?? null,
            ]);

            foreach ($itemsToCreate as $item) {
                $order->orderItems()->create([
                    'variant_id' => $item['variant_id'],
                    'qty'        => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'size'       => $item['size'],
                    'color'      => $item['color'],
                    'sub_total'  => $item['sub_total'],
                ]);

                // ដក Stock ដោយសុវត្ថិភាព
                $item['variant']->decrement('stock', $item['qty']);
            }

            Payment::create([
                'order_id'        => $order->id, // កែប្រែមកប្រើ Primary Key ($order->id)
                'method'          => $validated['payment_method'],
                'amount_paid'     => $netAmount,
                'tendered_amount' => $netAmount,
                'change_amount'   => 0,
                'transaction_id'  => 'TXN-' . strtoupper(uniqid()),
                'status'          => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Order created successfully',
                'data'    => $order->load('orderItems', 'payments')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Order creation failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->where('order_id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'status'         => 'sometimes|string',
            'payment_status' => 'sometimes|string',
            'note'           => 'sometimes|nullable|string',
        ]);

        $order->update($validated);

        return response()->json([
            'status' => 'success',
            'data'   => $order
        ]);
    }

    public function destroy(Request $request, $id) // បន្ថែម Request $request
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->where('order_id', $id)
            ->firstOrFail();

        $order->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Order deleted successfully'
        ]);
    }
}
