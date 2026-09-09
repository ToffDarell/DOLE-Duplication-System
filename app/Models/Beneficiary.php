<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Beneficiary extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Canonical Bukidnon municipality spellings, in display (Title Case) form.
     *
     * @var list<string>
     */
    public const BUKIDNON_MUNICIPALITIES = [
        'Baungon', 'Cabanglasan', 'Damulog', 'Dangcagan', 'Don Carlos',
        'Impasugong', 'Kadingilan', 'Kibawe', 'Kitaotao', 'Lantapan',
        'Libona', 'Malaybalay City', 'Maramag', 'Manolo Fortich',
        'Pangantucan', 'Quezon', 'San Fernando', 'Sumilao',
        'Talakag', 'Valencia City',
    ];

    protected $fillable = [
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'date_of_birth',
        'sex',
        'civil_status',
        'government_id_type',
        'government_id_number',
        'contact_number',
        'address',
        'barangay',
        'municipality',
        'province',
        'district',
        'is_senior_citizen',
        'is_pwd',
        'is_student',
        'is_government_employee',
        'is_graduating_college',
        'created_by',
    ];

    /**
     * The accessors to append to the model's array and JSON form.
     *
     * @var list<string>
     */
    protected $appends = [
        'full_name',
        'age',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_senior_citizen' => 'boolean',
            'is_pwd' => 'boolean',
            'is_student' => 'boolean',
            'is_government_employee' => 'boolean',
            'is_graduating_college' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<BeneficiaryProgram, $this>
     */
    public function beneficiaryPrograms(): HasMany
    {
        return $this->hasMany(BeneficiaryProgram::class);
    }

    /**
     * Alias for beneficiaryPrograms relationship (Availments history).
     *
     * @return HasMany<BeneficiaryProgram, $this>
     */
    public function availments(): HasMany
    {
        return $this->hasMany(BeneficiaryProgram::class);
    }

    /**
     * @return HasMany<DuplicateFlag, $this>
     */
    public function duplicateFlags(): HasMany
    {
        return $this->hasMany(DuplicateFlag::class);
    }

    /**
     * @return HasMany<DuplicateFlag, $this>
     */
    public function matchedDuplicateFlags(): HasMany
    {
        return $this->hasMany(DuplicateFlag::class, 'matched_beneficiary_id');
    }

    /**
     * Get the beneficiary's full name, falling back to components if the column is blank.
     */
    public function getFullNameAttribute(?string $value): string
    {
        if (! empty($value)) {
            return $value;
        }

        return self::buildFullName(
            (string) ($this->first_name ?? ''),
            $this->middle_name,
            (string) ($this->last_name ?? ''),
            $this->suffix
        );
    }

    /**
     * Compute age from date_of_birth — never trust an imported age column.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    /**
     * Build full_name from component parts before saving.
     */
    public static function buildFullName(string $firstName, ?string $middleName, string $lastName, ?string $suffix = null): string
    {
        $parts = array_filter([$lastName, $firstName, $middleName, $suffix]);

        return implode(', ', array_slice($parts, 0, 1))
             .', '
             .implode(' ', array_slice($parts, 1));
    }

    /**
     * Standardise a raw municipality string to its canonical Bukidnon spelling
     * so casing/spacing variants ("VALENCIA CITY", " valencia city ") collapse
     * to a single value and no longer split GROUP BY aggregations.
     */
    public static function canonicalMunicipality(?string $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        if ($trimmed === '') {
            return null;
        }

        $key = preg_replace('/\s+/', ' ', mb_strtolower($trimmed));

        foreach (self::BUKIDNON_MUNICIPALITIES as $canonical) {
            $canonicalKey = mb_strtolower($canonical);

            if ($key === $canonicalKey
                || str_replace(' city', '', $key) === str_replace(' city', '', $canonicalKey)) {
                return $canonical;
            }
        }

        return Str::title($key);
    }
}
