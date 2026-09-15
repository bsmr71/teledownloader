<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
    ];

    /**
     * Get a setting value by key with cache support.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever('app_settings', function () {
            return static::all()->mapWithKeys(function ($item) {
                return [$item->key => [
                    'value' => $item->value,
                    'type' => $item->type,
                ]];
            })->toArray();
        });

        if (! is_array($settings) || ! isset($settings[$key])) {
            return $default;
        }

        $setting = $settings[$key];

        return static::castValue($setting['value'] ?? null, $setting['type'] ?? 'string');
    }

    /**
     * Set a setting value and clear cache.
     */
    public static function set(string $key, mixed $value, ?string $group = 'general', ?string $type = null, ?string $description = null): self
    {
        if ($type === null) {
            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_array($value) => 'json',
                default => 'string',
            };
        }

        $existing = static::where('key', $key)->first();
        $group = $group ?? ($existing ? $existing->group : 'general');
        $type = $type ?? ($existing ? $existing->type : 'string');

        $formattedValue = static::formatValueForSave($value, $type);

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $formattedValue,
                'group' => $group,
                'type' => $type,
                'description' => $description ?? ($existing ? $existing->description : null),
            ]
        );

        Cache::forget('app_settings');

        return $setting;
    }

    /**
     * Get all settings grouped by group name.
     */
    public static function getAllGrouped(): array
    {
        $settings = static::all();
        $grouped = [];

        foreach ($settings as $setting) {
            $grouped[$setting->group][$setting->key] = [
                'value' => static::castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        }

        return $grouped;
    }

    /**
     * Cast string value from database to native PHP type.
     */
    public static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'json', 'array' => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /**
     * Format PHP value to string for database storage.
     */
    public static function formatValueForSave(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };
    }
}
