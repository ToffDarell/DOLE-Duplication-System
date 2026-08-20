<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = ['code', 'name'];

    /**
     * @return HasMany<BeneficiaryProgram, $this>
     */
    public function beneficiaryPrograms(): HasMany
    {
        return $this->hasMany(BeneficiaryProgram::class);
    }
}
