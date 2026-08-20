<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'user_id',
        'filename',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'error_log',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'error_log' => 'array',
            'total_rows' => 'integer',
            'imported_rows' => 'integer',
            'failed_rows' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
