<?php

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Services\DuplicateDetectionService;
use Illuminate\Database\Seeder;

class TestDuplicateSeeder extends Seeder
{
    public function run(): void
    {
        $dup = app(DuplicateDetectionService::class);

        $b1 = Beneficiary::create([
            'full_name' => 'JUAN MARCOS DELA CRUZ',
            'first_name' => 'JUAN',
            'middle_name' => 'MARCOS',
            'last_name' => 'DELA CRUZ',
            'date_of_birth' => '1995-05-15',
            'sex' => 'Male',
            'municipality' => 'Malaybalay City',
            'barangay' => 'Casisang',
            'contact_number' => '09171234567',
            'created_by' => 1,
        ]);

        $payload = [
            'first_name' => 'JONAH',
            'middle_name' => 'MARCOS',
            'last_name' => 'DELA CRUZ',
            'date_of_birth' => '1995-05-15',
            'sex' => 'Male',
            'municipality' => 'Malaybalay City',
            'barangay' => 'Casisang',
            'contact_number' => '09171234567',
        ];

        $check = $dup->checkDuplicates($payload);

        $b2 = Beneficiary::create([
            'full_name' => 'JONAH MARCOS DELA CRUZ',
            'first_name' => 'JONAH',
            'middle_name' => 'MARCOS',
            'last_name' => 'DELA CRUZ',
            'date_of_birth' => '1995-05-15',
            'sex' => 'Male',
            'municipality' => 'Malaybalay City',
            'barangay' => 'Casisang',
            'contact_number' => '09171234567',
            'created_by' => 1,
        ]);

        if ($check['has_duplicates']) {
            $dup->recordDuplicateFlags($b2, $check['flags']);
        }
    }
}
