<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = ['code', 'name'];

    /**
     * Active program codes configured for this installation.
     *
     * @return list<string>
     */
    public static function enabledCodes(): array
    {
        $codes = config('dole.enabled_programs', ['TUPAD']);

        return array_values(array_unique(array_map('strtoupper', (array) $codes)));
    }

    /**
     * Whether the portal is operating in TUPAD-Only Mode.
     */
    public static function isTupadOnly(): bool
    {
        return self::enabledCodes() === ['TUPAD'];
    }

    /**
     * Scope a query to only active/enabled DOLE programs.
     *
     * @param  Builder<Program>  $query
     * @return Builder<Program>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->whereIn('code', self::enabledCodes());
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<BeneficiaryProgram, $this>
     */
    public function beneficiaryPrograms(): HasMany
    {
        return $this->hasMany(BeneficiaryProgram::class);
    }
}
