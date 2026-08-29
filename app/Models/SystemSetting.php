<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Request-scoped cache of resolved setting values. `false` marks a key that
     * was looked up and does not exist, so repeated reads (e.g. the duplicate
     * engine running once per imported row) don't re-hit the database.
     *
     * @var array<string, string|null|false>
     */
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $cached = self::$cache[$key] ??= static::query()->where('key', $key)->value('value') ?? false;

        return $cached === false ? $default : $cached;
    }

    public static function set(string $key, mixed $value, ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description]
        );

        unset(self::$cache[$key]);
    }

    /**
     * Drop the in-memory cache (used between tests).
     */
    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
