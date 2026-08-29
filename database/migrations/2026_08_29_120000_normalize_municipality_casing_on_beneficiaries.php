<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Canonical Bukidnon municipality spellings — kept as a local snapshot so
     * this one-off cleanup keeps working regardless of future model changes.
     *
     * @var list<string>
     */
    private array $canonical = [
        'Baungon', 'Cabanglasan', 'Damulog', 'Dangcagan', 'Don Carlos',
        'Impasugong', 'Kadingilan', 'Kibawe', 'Kitaotao', 'Lantapan',
        'Libona', 'Malaybalay City', 'Maramag', 'Manolo Fortich',
        'Pangantucan', 'Quezon', 'San Fernando', 'Sumilao',
        'Talakag', 'Valencia City',
    ];

    /**
     * Collapse existing mixed-case / padded municipality values onto a single
     * canonical spelling so dashboard GROUP BY charts stop rendering duplicate
     * bars (e.g. "VALENCIA CITY" vs "Valencia City").
     */
    public function up(): void
    {
        DB::table('beneficiaries')
            ->whereNotNull('municipality')
            ->where('municipality', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $normalised = $this->normalise($row->municipality);

                    if ($normalised !== null && $normalised !== $row->municipality) {
                        DB::table('beneficiaries')
                            ->where('id', $row->id)
                            ->update(['municipality' => $normalised]);
                    }
                }
            });
    }

    /**
     * Casing normalisation is a lossy, forward-only cleanup — the original
     * spellings are not retained, so there is nothing to roll back.
     */
    public function down(): void
    {
        //
    }

    private function normalise(?string $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        if ($trimmed === '') {
            return null;
        }

        $key = preg_replace('/\s+/', ' ', mb_strtolower($trimmed));

        foreach ($this->canonical as $name) {
            $nameKey = mb_strtolower($name);

            if ($key === $nameKey
                || str_replace(' city', '', $key) === str_replace(' city', '', $nameKey)) {
                return $name;
            }
        }

        return Str::title($key);
    }
};
