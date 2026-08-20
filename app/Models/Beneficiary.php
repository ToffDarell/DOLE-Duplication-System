<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use HasFactory, SoftDeletes;

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
        'is_senior_citizen',
        'is_pwd',
        'is_student',
        'is_government_employee',
        'is_graduating_college',
        'created_by',
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
}
