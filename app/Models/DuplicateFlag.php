<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicateFlag extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'matched_beneficiary_id',
        'match_score',
        'match_type',
        'matched_fields',
        'household_match_flag',
        'status',
        'reviewed_by',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'matched_fields' => 'array',
            'match_score' => 'integer',
            'household_match_flag' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Beneficiary, $this>
     */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    /**
     * @return BelongsTo<Beneficiary, $this>
     */
    public function matchedBeneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class, 'matched_beneficiary_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Dynamically compute the household address comparison string using live beneficiary data.
     */
    public function getHouseholdAddressDetail(): string
    {
        $newAddr = trim($this->beneficiary?->address ?? '');
        $existAddr = trim($this->matchedBeneficiary?->address ?? '');

        if (! empty($newAddr) && ! empty($existAddr) && mb_strtolower($newAddr) === mb_strtolower($existAddr)) {
            return "SAME ADDRESS: Both records list identical address \"{$newAddr}\" — likely SAME household.";
        }

        if (empty($newAddr) && empty($existAddr)) {
            return 'No Sitio/House Address specified on both records — Validator must confirm if applicants live in separate physical houses.';
        }

        if (empty($newAddr) || empty($existAddr)) {
            $newDisplay = ! empty($newAddr) ? $newAddr : '(not specified)';
            $existDisplay = ! empty($existAddr) ? $existAddr : '(not specified)';

            return "Incomplete address details: New=\"{$newDisplay}\" vs Existing=\"{$existDisplay}\" — Sitio/House No. needed to confirm separate households.";
        }

        return "Different addresses detected: New=\"{$newAddr}\" vs Existing=\"{$existAddr}\" — verify if truly separate households.";
    }
}
