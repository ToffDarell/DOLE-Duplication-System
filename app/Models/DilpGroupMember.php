<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DilpGroupMember extends Model
{
    protected $fillable = [
        'dilp_group_id',
        'member_name',
        'contact_no',
        'designation',
    ];

    /**
     * @return BelongsTo<DilpGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(DilpGroup::class, 'dilp_group_id');
    }
}
