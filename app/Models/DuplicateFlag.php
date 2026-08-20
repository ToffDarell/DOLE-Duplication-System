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
}
