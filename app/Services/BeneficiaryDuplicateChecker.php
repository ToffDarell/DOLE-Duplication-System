<?php

namespace App\Services;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BeneficiaryDuplicateChecker
{
    /**
     * Find potential duplicate beneficiaries using name tokenization and SQL SOUNDEX().
     * Does not rely on exact DOB, ID number, or municipality matches.
     *
     * @return Collection<int, Beneficiary>
     */
    public function findDuplicates(array $data, ?int $excludeId = null): Collection
    {
        $lastName = trim($data['last_name'] ?? '');
        $firstName = trim($data['first_name'] ?? '');

        if (empty($lastName) || empty($firstName)) {
            return new Collection;
        }

        if (DB::getDriverName() === 'sqlite') {
            $pdo = DB::connection()->getPdo();
            if (method_exists($pdo, 'sqliteCreateFunction')) {
                $pdo->sqliteCreateFunction('SOUNDEX', 'soundex');
            }
        }

        $firstToken = explode(' ', $firstName)[0] ?? '';

        $query = Beneficiary::with(['beneficiaryPrograms.program'])
            ->where('last_name', 'LIKE', $lastName)
            ->where(function ($query) use ($firstName, $firstToken) {
                $query->where('first_name', 'LIKE', "%{$firstToken}%")
                    ->orWhereRaw('SOUNDEX(first_name) = SOUNDEX(?)', [$firstName]);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }
}
