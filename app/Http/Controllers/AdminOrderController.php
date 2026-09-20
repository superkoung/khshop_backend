<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Order::query()
            ->with([
                'user:id,name,email,phone',
                'items.variant.product:id,name',
            ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('order_type') && $request->order_type !== 'all') {
            $query->where('order_type', $request->order_type);
        }

        $orders = $query->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return $this->successResponse(
            ['orders' => $orders],
            'Orders retrieved successfully',
            200
        );
    }

    public function show(int $id)
    {
        $order = Order::with([
            'user:id,name,email,phone',
            'items.variant.product:id,name,slug',
            'items.variant.color:id,name',
            'items.variant.size:id,name',
            'items.variant.image:id,image_path',
        ])->find($id);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        return $this->successResponse(
            ['order' => $order],
            'Order retrieved successfully',
            200
        );
    }

    public function updateStatus(Request $request, int $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,completed,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);

        $order->load([
            'user:id,name,email,phone',
            'items.variant.product:id,name',
        ]);

        return $this->successResponse(
            ['order' => $order],
            'Order status updated successfully',
            200
        );
    }

    public function cancel(int $id)
    {
        try {
            $order = DB::transaction(function () use ($id) {
                $order = Order::with('items.variant')->find($id);

                if (!$order) {
                    abort(404, 'Order not found.');
                }

                if ($order->status === 'cancelled') {
                    abort(422, 'Order is already cancelled.');
                }

                if ($order->status === 'completed') {
                    abort(422, 'Cannot cancel a completed order.');
                }

                foreach ($order->items as $item) {
                    $variant = $item->variant;
                    if ($variant) {
                        $variant->increment('stock', $item->qty);
                    }
                }

                Cache::flush();

                $order->update(['status' => 'cancelled']);

                return $order;
            });

            $order->load([
                'user:id,name,email,phone',
                'items.variant.product:id,name',
            ]);

            return $this->successResponse(
                ['order' => $order],
                'Order cancelled successfully',
                200
            );

        } catch (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return $this->errorResponse($e->getMessage(), $e->getStatusCode());
            }

            return $this->errorResponse('Failed to cancel order.', 500);
        }
    }
}
