<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DilpProject extends Model
{
    protected $fillable = [
        'dilp_group_id',
        'project_name',
        'description',
        'start_date',
        'end_date',
        'liquidation_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<DilpGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(DilpGroup::class, 'dilp_group_id');
    }
}
