<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    use ApiResponse;

    private const ALLOWED = [
        'general' => [
            'store_name'    => 'string',
            'store_email'   => 'string',
            'store_phone'   => 'string',
            'store_address' => 'string',
        ],
        'store' => [
            'currency'         => 'string',
            'tax_rate'         => 'number',
            'maintenance_mode' => 'boolean',
        ],
        'checkout' => [
            'shipping_fee'            => 'number',
            'free_shipping_threshold' => 'number',
            'guest_checkout'          => 'boolean',
        ],
        'order' => [
            'default_order_status' => 'string',
            'allow_cancellation'   => 'boolean',
            'auto_cancel_pending'  => 'boolean',
        ],
    ];

    /**
     * GET /api/v1/admin/settings
     */
    public function index()
    {
        $grouped = Setting::getGrouped();

        $result = [];
        foreach (self::ALLOWED as $group => $keys) {
            $result[$group] = [];
            foreach ($keys as $key => $type) {
                $setting = $grouped[$group][$key] ?? null;
                $result[$group][$key] = $this->castValue($setting, $type);
            }
        }

        return $this->successResponse($result, 'Settings retrieved successfully');
    }

    /**
     * PUT/PATCH /api/v1/admin/settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate($this->buildValidationRules($request));

        $saved = [];
        foreach (self::ALLOWED as $group => $keys) {
            $saved[$group] = [];
            foreach ($keys as $key => $type) {
                if (array_key_exists($key, $validated)) {
                    $raw = $validated[$key];
                    $value = $this->normalizeValue($raw, $type);

                    Setting::set($key, $value, $type, $group);
                    // Return the same cast shape as GET so the UI can render immediately.
                    $saved[$group][$key] = $this->castValue($value, $type);
                } else {
                    $existing = Setting::where('key', $key)->first();
                    $saved[$group][$key] = $existing
                        ? $this->castValue($existing->value, $type)
                        : $this->castValue(null, $type);
                }
            }
        }

        return $this->successResponse($saved, 'Settings updated successfully');
    }

    private function buildValidationRules(?Request $request = null): array
    {
        $rules = [];

        foreach (self::ALLOWED as $group => $keys) {
            foreach ($keys as $key => $type) {
                $fieldRules = [];
                if ($type === 'string') {
                    $fieldRules[] = 'nullable';
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    if ($key === 'store_email') {
                        $fieldRules[] = 'email';
                    }
                    if ($key === 'default_order_status') {
                        $fieldRules[] = 'in:pending,processing,shipped,completed,cancelled';
                    }
                } elseif ($type === 'number') {
                    $fieldRules[] = 'nullable';
                    $fieldRules[] = 'numeric';
                    if (str_contains($key, 'rate') || str_contains($key, 'fee') || str_contains($key, 'threshold')) {
                        $fieldRules[] = 'min:0';
                    }
                } elseif ($type === 'boolean') {
                    $fieldRules[] = 'nullable';
                    $fieldRules[] = 'boolean';
                }

                // Frontend sends nested groups: { general: { store_name }, store: { currency }, ... }
                // Laravel rules are flat keys, so flatten the request first when needed.
                if ($request !== null && !array_key_exists($key, $request->all())) {
                    $nested = $request->input($group);
                    if (is_array($nested) && array_key_exists($key, $nested)) {
                        $request->merge([$key => $nested[$key]]);
                    }
                }

                $rules[$key] = $fieldRules;
            }
        }

        return $rules;
    }

    private function castValue($value, string $type)
    {
        if ($value === null) {
            return match ($type) {
                'boolean' => false,
                'number'  => 0,
                default   => '',
            };
        }
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number'  => (float) $value,
            default   => (string) $value,
        };
    }

    private function normalizeValue($raw, string $type)
    {
        if ($raw === null || $raw === '') {
            return match ($type) {
                'boolean' => '0',
                'number'  => '0',
                default   => '',
            };
        }
        if ($type === 'boolean') {
            return $raw ? '1' : '0';
        }
        return (string) $raw;
    }
}
