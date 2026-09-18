<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCustomerController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = User::query()
            ->with('role:id,name')
            ->where('role_id', 1);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active') && $request->is_active !== 'all') {
            $isActive = $request->is_active === '1' || $request->is_active === 'true';
            $query->where('is_active', $isActive);
        }

        $customers = $query->withCount('orders')
            ->withSum('orders', 'net_amount')
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $mapped = $customers->getCollection()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'is_active' => $customer->is_active,
                'created_at' => $customer->created_at,
                'orders_count' => $customer->orders_count ?? 0,
                'total_spent' => $customer->orders_sum_net_amount ?? 0,
            ];
        });

        $customers->setCollection($mapped);

        return $this->successResponse(
            ['customers' => $customers],
            'Customers retrieved successfully',
            200
        );
    }

    public function show(int $id)
    {
        $customer = User::where('role_id', 1)->find($id);

        if (!$customer) {
            return $this->errorResponse('Customer not found', 404);
        }

        $customer->load('role:id,name');

        $orders = $customer->orders()
            ->with('items.variant.product:id,name')
            ->latest()
            ->get();

        $totalOrders = $orders->count();
        $completedOrders = $orders->where('status', 'completed')->count();
        $cancelledOrders = $orders->where('status', 'cancelled')->count();
        $totalSpent = $orders->where('payment_status', 'paid')->sum('net_amount');

        return $this->successResponse(
            [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'is_active' => $customer->is_active,
                    'created_at' => $customer->created_at,
                    'role' => $customer->role->name ?? 'user',
                ],
                'summary' => [
                    'total_orders' => $totalOrders,
                    'completed_orders' => $completedOrders,
                    'cancelled_orders' => $cancelledOrders,
                    'total_spent' => $totalSpent,
                ],
                'orders' => $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'total_amount' => $order->total_amount,
                        'discount_amount' => $order->discount_amount,
                        'tax_amount' => $order->tax_amount,
                        'net_amount' => $order->net_amount,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'order_type' => $order->order_type,
                        'created_at' => $order->created_at,
                        'items_count' => $order->items->count(),
                    ];
                }),
            ],
            'Customer retrieved successfully',
            200
        );
    }

    public function updateStatus(Request $request, int $id)
    {
        $customer = User::where('role_id', 1)->find($id);

        if (!$customer) {
            return $this->errorResponse('Customer not found', 404);
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $customer->update(['is_active' => $validated['is_active']]);

        return $this->successResponse(
            [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'is_active' => $customer->is_active,
                    'created_at' => $customer->created_at,
                ],
            ],
            'Customer status updated successfully',
            200
        );
    }
}
