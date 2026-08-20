<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BeneficiaryProgram extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'program_id',
        'availment_year',
        'enrollment_type',
        'dilp_group_id',
        'internship_duration',
        'status',
        'remarks',
        'reviewed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'availment_year' => 'integer',
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
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<DilpGroup, $this>
     */
    public function dilpGroup(): BelongsTo
    {
        return $this->belongsTo(DilpGroup::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasOne<TupadProfile, $this>
     */
    public function tupadProfile(): HasOne
    {
        return $this->hasOne(TupadProfile::class);
    }
}
