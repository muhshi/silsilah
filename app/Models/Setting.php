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
        'group',
    ];

    /**
     * Get a setting value by key with optional default fallback
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting || $setting->value === null) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * Set / update a setting value by key
     */
    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'group' => $group,
            ]
        );
    }

    /**
     * Check if a setting key exists and has a non-null value
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->whereNotNull('value')->exists();
    }

    /**
     * Forget / delete a setting key
     */
    public static function forget(string $key): bool
    {
        return (bool) static::where('key', $key)->delete();
    }
}
