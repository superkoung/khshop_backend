<?php

namespace App\Services;

use App\Models\Product_variant;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\OutOfStockNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Central place to resolve recipients and send database notifications
 * without breaking the calling business transaction.
 */
class NotificationDispatcher
{
    /** Same threshold used by InventoryController / DashboardController. */
    public const LOW_STOCK_THRESHOLD = 10;

    /** Role names used by CheckRoleMiddleware for admin+staff routes. */
    public const ADMIN_STAFF_ROLES = ['admin', 'superAdmin', 'staff'];

    /** Role names used for admin-only reports/settings. */
    public const ADMIN_ONLY_ROLES = ['admin', 'superAdmin'];

    /**
     * Active non-deleted users whose role name is in $roleNames.
     *
     * @param  array<int, string>  $roleNames
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function recipients(array $roleNames)
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($q) use ($roleNames) {
                $q->whereIn('name', $roleNames);
            })
            ->get();
    }

    /**
     * Send to active Admin + Staff users.
     */
    public static function toAdminStaff(Notification $notification): void
    {
        static::send(self::recipients(self::ADMIN_STAFF_ROLES), $notification);
    }

    /**
     * Send to active Admin (+ superAdmin) only.
     */
    public static function toAdminOnly(Notification $notification): void
    {
        static::send(self::recipients(self::ADMIN_ONLY_ROLES), $notification);
    }

    /**
     * Send to specific users (e.g. order customer), skipping inactive.
     *
     * @param  iterable<int, User>  $users
     */
    public static function sendToUsers(iterable $users, Notification $notification): void
    {
        $active = collect($users)->filter(fn (User $u) => (bool) $u->is_active);
        static::send($active, $notification);
    }

    /**
     * Notify Admin+Staff and the related customer (if active).
     * Used for payment confirmation and order status updates.
     */
    public static function toAdminStaffAndCustomer(Notification $notification, ?User $customer): void
    {
        static::toAdminStaff($notification);

        if ($customer) {
            static::sendToUsers([$customer], clone $notification);
        }
    }

    /**
     * Fire stock threshold alerts after an inventory-changing event only.
     * Safe to call when stock did not change into a threshold band — no-op.
     */
    public static function notifyStockChange(Product_variant $variant, ?int $previousStock = null): void
    {
        $variant->loadMissing('product:id,name');
        $newStock = (int) $variant->stock;

        // Only react to actual changes when previous value is known.
        if ($previousStock !== null && $previousStock === $newStock) {
            return;
        }

        $productName = $variant->product->name ?? 'Product';

        if ($newStock === 0) {
            static::toAdminStaff(new OutOfStockNotification(
                (int) $variant->id,
                $productName
            ));
            return;
        }

        if ($newStock <= self::LOW_STOCK_THRESHOLD) {
            static::toAdminStaff(new LowStockNotification(
                (int) $variant->id,
                $productName,
                $newStock
            ));
        }
    }

    /**
     * Never let notification failure roll back or fail the business action.
     *
     * @param  \Illuminate\Support\Collection|array  $users
     */
    protected static function send($users, Notification $notification): void
    {
        try {
            if (empty($users)) {
                return;
            }
            \Illuminate\Support\Facades\Notification::send($users, $notification);
        } catch (\Throwable $e) {
            Log::error('Failed to send notification', [
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
