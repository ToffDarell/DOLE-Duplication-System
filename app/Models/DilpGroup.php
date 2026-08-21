<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DilpGroup extends Model
{
    protected $fillable = [
        'group_name',
        'co_partner_name',
        'co_partner_contact',
    ];

    /**
     * @return HasMany<DilpProject, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(DilpProject::class);
    }

    /**
     * @return HasMany<BeneficiaryProgram, $this>
     */
    public function beneficiaryPrograms(): HasMany
    {
        return $this->hasMany(BeneficiaryProgram::class);
    }

    /**
     * @return HasMany<DilpGroupMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(DilpGroupMember::class);
    }
}
