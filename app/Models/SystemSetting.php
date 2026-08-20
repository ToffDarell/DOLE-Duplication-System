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

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting !== null ? $setting->value : $default;
    }

    public static function set(string $key, mixed $value, ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description]
        );
    }
}
