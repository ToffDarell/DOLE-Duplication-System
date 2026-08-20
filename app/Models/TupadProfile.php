<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TupadProfile extends Model
{
    protected $fillable = [
        'beneficiary_program_id',
        'project_location_barangay',
        'project_location_municipality',
        'project_location_province',
        'project_location_district',
        'epayment_account_no',
        'beneficiary_type',
        'occupation',
        'average_monthly_income',
        'dependent_name',
        'dependent_relationship',
        'interested_in_employment',
        'employment_interest_detail',
        'skills_training_needed',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interested_in_employment' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<BeneficiaryProgram, $this>
     */
    public function beneficiaryProgram(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryProgram::class);
    }
}
