<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Cast value based on type column.
     */
    public function getValueAttribute($raw)
    {
        return match ($this->type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'number'  => is_numeric($raw) ? (float) $raw : $raw,
            default   => $raw,
        };
    }

    /**
     * Get a setting by key, return cast value.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set (upsert) a setting by key.
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general', ?string $description = null): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value'       => is_bool($value) ? ($value ? '1' : '0') : $value,
                'type'        => $type,
                'group'       => $group,
                'description' => $description,
            ]
        );
    }

    /**
     * Get all settings grouped by group column.
     * Returns [ 'general' => ['store_name' => '...', ...], ... ]
     */
    public static function getGrouped(): array
    {
        $all = static::all();
        $grouped = [];

        foreach ($all as $setting) {
            $grouped[$setting->group][$setting->key] = $setting->value;
        }

        return $grouped;
    }
}
