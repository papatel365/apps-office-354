<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DeveloperSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'developer_settings_';

    /**
     * Cache TTL in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get a setting value by group and key.
     */
    public static function getValue(string $group, string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . "{$group}.{$key}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group, $key, $default) {
            $setting = self::where('group', $group)
                ->where('key', $key)
                ->first();

            if (!$setting) {
                return $default;
            }

            // Decode based on type
            return self::decodeValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $group, string $key, $value, string $type = 'string', bool $isEncrypted = false): void
    {
        $encodedValue = self::encodeValue($value, $type, $isEncrypted);

        self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => $encodedValue,
                'type' => $type,
                'is_encrypted' => $isEncrypted,
            ]
        );

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . "{$group}.{$key}");
    }

    /**
     * Delete a setting.
     */
    public static function deleteValue(string $group, string $key): void
    {
        self::where('group', $group)->where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX . "{$group}.{$key}");
    }

    /**
     * Get all settings for a group.
     */
    public static function getGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = self::decodeValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Encode a value for storage.
     */
    protected static function encodeValue($value, string $type, bool $isEncrypted): string
    {
        if ($isEncrypted) {
            return encrypt($value);
        }

        return match ($type) {
            'array', 'object', 'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'float' => (string) (float) $value,
            default => (string) $value,
        };
    }

    /**
     * Decode a value from storage.
     */
    protected static function decodeValue(string $value, string $type)
    {
        if (empty($value)) {
            return match ($type) {
                'boolean' => false,
                'integer' => 0,
                'float' => 0.0,
                'array', 'object', 'json' => [],
                default => null,
            };
        }

        // Check if it looks like encrypted
        if (str_starts_with($value, 'eyJ') || str_starts_with($value, '[') === false && strlen($value) > 50) {
            try {
                return decrypt($value);
            } catch (\Exception $e) {
                // Not encrypted, continue with normal decoding
            }
        }

        return match ($type) {
            'array', 'object', 'json' => json_decode($value, true),
            'boolean' => in_array($value, ['1', 'true', 'yes'], true),
            'integer' => (int) $value,
            'float' => (float) $value,
            default => $value,
        };
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        // Note: In production, you'd want a more robust cache tagging strategy
        Cache::flush();
    }
}
