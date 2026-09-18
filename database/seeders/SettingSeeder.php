<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // General
            ['key' => 'store_name',    'value' => 'KhShop',           'type' => 'string',  'group' => 'general',  'description' => 'Store display name'],
            ['key' => 'store_email',   'value' => 'admin@khshop.com', 'type' => 'string',  'group' => 'general',  'description' => 'Primary store email'],
            ['key' => 'store_phone',   'value' => '+855 12 345 678',   'type' => 'string',  'group' => 'general',  'description' => 'Store contact phone'],
            ['key' => 'store_address', 'value' => 'Phnom Penh, Cambodia', 'type' => 'string', 'group' => 'general', 'description' => 'Store physical address'],

            // Store
            ['key' => 'currency',         'value' => 'USD', 'type' => 'string',  'group' => 'store', 'description' => 'Store currency code'],
            ['key' => 'tax_rate',         'value' => '0',   'type' => 'number',  'group' => 'store', 'description' => 'Tax rate percentage'],
            ['key' => 'maintenance_mode', 'value' => '0',   'type' => 'boolean', 'group' => 'store', 'description' => 'Enable maintenance mode'],

            // Checkout
            ['key' => 'shipping_fee'            , 'value' => '5',   'type' => 'number',  'group' => 'checkout', 'description' => 'Default shipping fee'],
            ['key' => 'free_shipping_threshold' , 'value' => '50',  'type' => 'number',  'group' => 'checkout', 'description' => 'Minimum order for free shipping'],
            ['key' => 'guest_checkout'          , 'value' => '0',   'type' => 'boolean', 'group' => 'checkout', 'description' => 'Allow guest checkout'],

            // Order
            ['key' => 'default_order_status', 'value' => 'pending',    'type' => 'string',  'group' => 'order', 'description' => 'Default status for new orders'],
            ['key' => 'allow_cancellation'  , 'value' => '1',          'type' => 'boolean', 'group' => 'order', 'description' => 'Allow customers to cancel orders'],
            ['key' => 'auto_cancel_pending' , 'value' => '0',          'type' => 'boolean', 'group' => 'order', 'description' => 'Auto-cancel pending orders after timeout'],
        ];

        foreach ($defaults as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                $item
            );
        }
    }
}
