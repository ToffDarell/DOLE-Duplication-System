<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DilpProject extends Model
{
    public const TYPE_GROUP = 'group';

    public const TYPE_INDIVIDUAL = 'individual';

    public const MODE_DIRECT_ADMIN = 'DA';

    public const MODE_ACP = 'ACP';

    protected $fillable = [
        'project_type',
        'dilp_group_id',
        'beneficiary_id',
        'beneficiary_name',
        'project_name',
        'description',
        'grant_amount',
        'implementation_mode',
        'acp_name',
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
            'grant_amount' => 'decimal:2',
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

    /**
     * @return BelongsTo<Beneficiary, $this>
     */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class, 'beneficiary_id');
    }

    public function getGranteeDisplayNameAttribute(): string
    {
        if ($this->project_type === self::TYPE_INDIVIDUAL) {
            return $this->beneficiary?->full_name
                ?? $this->beneficiary_name
                ?? 'Individual Grantee';
        }

        return $this->group?->group_name ?? 'Unassigned Association';
    }

    public function getImplementationModeLabelAttribute(): string
    {
        return match ($this->implementation_mode) {
            self::MODE_DIRECT_ADMIN => 'Direct Admin (DA)',
            self::MODE_ACP => $this->acp_name ? "ACP — {$this->acp_name}" : 'Accredited Co-Partner (ACP)',
            default => '—',
        };
    }
}
