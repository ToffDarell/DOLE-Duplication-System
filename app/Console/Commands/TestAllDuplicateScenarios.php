<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Services\DuplicateDetectionService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('test:duplicates-all')]
#[Description('Execute test suite across all 8 primary duplicate detection and household scenarios')]
class TestAllDuplicateScenarios extends Command
{
    public function __construct(
        protected DuplicateDetectionService $duplicateService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('========================================================================================');
        $this->info('              DOLE BENEFICIARY DUPLICATE DETECTION SUITE - 8 SCENARIOS                 ');
        $this->info('========================================================================================');
        $this->newLine();

        $scenarios = $this->getTestScenarios();
        $tableRows = [];
        $passedCount = 0;

        foreach ($scenarios as $index => $scenario) {
            $num = $index + 1;
            $existing = $scenario['existing'];
            $input = $scenario['input'];

            // Run check
            if ($scenario['type'] === 'household') {
                $result = $this->duplicateService->checkHouseholdDuplicates($input, null, collect([$existing]));
                $actualScore = $result['has_household_flags'] ? ($result['flags'][0]['match_score'] ?? 50) : 0;
                $isFlagged = $result['has_household_flags'];
                $matchType = $result['has_household_flags'] ? 'Household Review' : 'Clean';
                $actualFlagText = $isFlagged ? "Flagged ({$actualScore}%, {$matchType})" : 'Clean (Not Flagged)';
            } else {
                $result = $this->duplicateService->checkDuplicates($input, null, collect([$existing]));
                $actualScore = $result['max_score'] ?? 0;
                $isFlagged = $result['has_duplicates'];
                $topFlag = $result['flags'][0] ?? null;
                $matchType = $topFlag ? strtoupper($topFlag['match_type'] ?? 'LOW') : 'CLEAN';
                $actualFlagText = $isFlagged ? "Flagged ({$actualScore}%, {$matchType})" : 'Clean (Not Flagged)';
            }

            // Evaluate Pass/Fail Condition
            $passed = $scenario['validator']($result, $actualScore, $isFlagged);
            if ($passed) {
                $passedCount++;
                $statusText = '<fg=green;options=bold>PASS</>';
            } else {
                $statusText = '<fg=red;options=bold>FAIL</>';
            }

            $tableRows[] = [
                '#' => $num,
                'Scenario' => $scenario['title'],
                'Input Name' => $scenario['input_display'],
                'Matched Name' => $scenario['existing_display'],
                'Score' => "{$actualScore}%",
                'Expected Flag' => $scenario['expected_display'],
                'Actual Flag' => $actualFlagText,
                'Status' => $statusText,
            ];
        }

        $this->table(
            ['#', 'Test Scenario', 'Input Name', 'Matched Name', 'Match Score', 'Expected Flag', 'Actual Flag', 'Status'],
            $tableRows
        );

        $this->newLine();
        if ($passedCount === count($scenarios)) {
            $this->info("✓ ALL {$passedCount} OF ".count($scenarios).' DUPLICATE SCENARIOS PASSED SUCCESSFULLY (100%)');
            $this->newLine();

            return Command::SUCCESS;
        }

        $this->error("✗ {$passedCount} OF ".count($scenarios).' SCENARIOS PASSED. PLEASE REVIEW FAILED TESTS.');
        $this->newLine();

        return Command::FAILURE;
    }

    /**
     * Get the definitions of the 8 primary duplicate test scenarios.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getTestScenarios(): array
    {
        $currentYear = (int) date('Y');

        return [
            // 1. Exact Name Collision (Different Details)
            [
                'title' => 'Exact Name Collision (Different Details)',
                'type' => 'identity',
                'input_display' => 'GONZALES, MARIA CLARA . (1995-01-01)',
                'existing_display' => 'Gonzales, Maria Clara (2000-05-10)',
                'expected_display' => 'Min 75%, Flagged (same_name_alert)',
                'existing' => $this->makeBeneficiary([
                    'id' => 101,
                    'last_name' => 'Gonzales',
                    'first_name' => 'Maria Clara',
                    'full_name' => 'Gonzales, Maria Clara',
                    'date_of_birth' => '2000-05-10',
                    'municipality' => 'Malaybalay City',
                ]),
                'input' => [
                    'last_name' => 'GONZALES',
                    'first_name' => 'MARIA CLARA .',
                    'date_of_birth' => '1995-01-01',
                    'municipality' => 'Valencia City',
                ],
                'validator' => function (array $result, int $score, bool $isFlagged): bool {
                    $hasAlert = isset($result['flags'][0]['matched_fields']['same_name_alert']);

                    return $isFlagged && $score >= 75 && $hasAlert;
                },
            ],

            // 2. Tokenized First Name + Near-Date DOB Typo
            [
                'title' => 'Token First Name + Near DOB Typo',
                'type' => 'identity',
                'input_display' => 'Vergara, Toffe Baron (2004-10-10, Maramag)',
                'existing_display' => 'Vergara, Toff (2004-10-13, Maramag)',
                'expected_display' => '85% Score, Flagged (HIGH RISK)',
                'existing' => $this->makeBeneficiary([
                    'id' => 102,
                    'last_name' => 'Vergara',
                    'first_name' => 'Toff',
                    'full_name' => 'Vergara, Toff',
                    'date_of_birth' => '2004-10-13',
                    'municipality' => 'Maramag',
                    'barangay' => 'Poblacion',
                ]),
                'input' => [
                    'last_name' => 'Vergara',
                    'first_name' => 'Toffe Baron',
                    'date_of_birth' => '2004-10-10',
                    'municipality' => 'Maramag',
                    'barangay' => 'North Poblacion',
                ],
                'validator' => function (array $result, int $score, bool $isFlagged): bool {
                    return $isFlagged && $score >= 80;
                },
            ],

            // 3. Government ID Match with Misspelled Name
            [
                'title' => 'Government ID Match (Misspelled Name)',
                'type' => 'identity',
                'input_display' => 'Dela Cruz, Jhon (ID: 1234-5678-9012)',
                'existing_display' => 'De La Cruz, John (ID: 1234-5678-9012)',
                'expected_display' => '100% Score, Flagged (EXACT MATCH)',
                'existing' => $this->makeBeneficiary([
                    'id' => 103,
                    'last_name' => 'De La Cruz',
                    'first_name' => 'John',
                    'full_name' => 'De La Cruz, John',
                    'government_id_number' => '1234-5678-9012',
                ]),
                'input' => [
                    'last_name' => 'Dela Cruz',
                    'first_name' => 'Jhon',
                    'government_id_number' => '1234-5678-9012',
                ],
                'validator' => function (array $result, int $score, bool $isFlagged): bool {
                    return $isFlagged && $score === 100 && ($result['flags'][0]['match_type'] ?? '') === 'exact';
                },
            ],

            // 4. Transposed First and Last Name + Same DOB
            [
                'title' => 'Transposed Name + Same DOB',
                'type' => 'identity',
                'input_display' => 'Cruz, Juan Dela (1998-06-12)',
                'existing_display' => 'Juan, Cruz Dela (1998-06-12)',
                'expected_display' => '>= 75% Score, Flagged (Transposed)',
                'existing' => $this->makeBeneficiary([
                    'id' => 104,
                    'last_name' => 'Juan',
                    'first_name' => 'Cruz Dela',
                    'full_name' => 'Juan, Cruz Dela',
                    'date_of_birth' => '1998-06-12',
                ]),
                'input' => [
                    'last_name' => 'Cruz',
                    'first_name' => 'Juan Dela',
                    'date_of_birth' => '1998-06-12',
                ],
                'validator' => function (array $result, int $score, bool $isFlagged): bool {
                    return $isFlagged && $score >= 75;
                },
            ],

            // 5. Middle Name Typo Variation
            [
                'title' => 'Middle Name Typo Variation',
                'type' => 'identity',
                'input_display' => 'Zamora, Jimson Pabaloy',
                'existing_display' => 'Zamora, Jimson Pabalay',
                'expected_display' => '>= 75% Score, Flagged (Middle Name Match)',
                'existing' => $this->makeBeneficiary([
                    'id' => 105,
                    'last_name' => 'Zamora',
                    'first_name' => 'Jimson',
                    'middle_name' => 'Pabalay',
                    'full_name' => 'Zamora, Jimson Pabalay',
                ]),
                'input' => [
                    'last_name' => 'Zamora',
                    'first_name' => 'Jimson',
                    'middle_name' => 'Pabaloy',
                ],
                'validator' => function (array $result, int $score, bool $isFlagged): bool {
                    return $isFlagged && $score >= 75;
                },
            ],

            // 6. Same Surname & Address (Household Co-Availment)
            [
                'title' => 'Household Co-Availment (Same Barangay/Family)',
                'type' => 'household',
                'input_display' => 'Sinahon, Milagros (Brgy. Mt. Nebo)',
                'existing_display' => 'Sinahon, Marvin (Brgy. Mt. Nebo)',
                'expected_display' => 'Flagged (Household Co-Availment Review)',
                'existing' => $this->makeHouseholdBeneficiary([
                    'id' => 106,
                    'last_name' => 'Sinahon',
                    'first_name' => 'Marvin',
                    'full_name' => 'Sinahon, Marvin',
                    'barangay' => 'Mt. Nebo',
                    'municipality' => 'Valencia City',
                ], $currentYear),
                'input' => [
                    'last_name' => 'Sinahon',
                    'first_name' => 'Milagros',
                    'barangay' => 'Mt. Nebo',
                    'municipality' => 'Valencia City',
                    'program_code' => 'TUPAD',
                    'availment_year' => $currentYear,
                ],
                'validator' => function (array $result, int $score, bool $isFlagged): bool {
                    return $isFlagged && ($result['has_household_flags'] ?? false);
                },
            ],

            // 7. Surname as Middle Name (Valid Distinct Individuals)
            [
                'title' => 'Surname as Middle Name (Distinct Persons)',
                'type' => 'identity',
                'input_display' => 'Bendanillo, John Vergara',
                'existing_display' => 'Vergara, Toff',
                'expected_display' => '0% Score, Clean (Not Flagged)',
                'existing' => $this->makeBeneficiary([
                    'id' => 107,
                    'last_name' => 'Vergara',
                    'first_name' => 'Toff',
                    'full_name' => 'Vergara, Toff',
                ]),
                'input' => [
                    'last_name' => 'Bendanillo',
                    'first_name' => 'John',
                    'middle_name' => 'Vergara',
                ],
                'validator' => function (array $result, int $score, bool $isFlagged): bool {
                    return ! $isFlagged && $score === 0;
                },
            ],

            // 8. Shared Birthdate Only (Coincidental Birthday)
            [
                'title' => 'Shared Birthdate Only (Coincidental DOB)',
                'type' => 'identity',
                'input_display' => 'Reyes, Maria (DOB: 1998-06-12)',
                'existing_display' => 'Cruz, Juan (DOB: 1998-06-12)',
                'expected_display' => '30% Score, Clean (Not Flagged)',
                'existing' => $this->makeBeneficiary([
                    'id' => 108,
                    'last_name' => 'Cruz',
                    'first_name' => 'Juan',
                    'full_name' => 'Cruz, Juan',
                    'date_of_birth' => '1998-06-12',
                ]),
                'input' => [
                    'last_name' => 'Reyes',
                    'first_name' => 'Maria',
                    'date_of_birth' => '1998-06-12',
                ],
                'validator' => function (array $result, int $score, bool $isFlagged): bool {
                    return ! $isFlagged && $score === 30;
                },
            ],
        ];
    }

    /**
     * Helper to instantiate an in-memory Beneficiary model with default relations.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeBeneficiary(array $attributes): Beneficiary
    {
        if (isset($attributes['date_of_birth']) && is_string($attributes['date_of_birth'])) {
            $attributes['date_of_birth'] = Carbon::parse($attributes['date_of_birth']);
        }

        $beneficiary = new Beneficiary($attributes);
        if (isset($attributes['id'])) {
            $beneficiary->id = $attributes['id'];
        }
        $beneficiary->setRelation('beneficiaryPrograms', collect());

        return $beneficiary;
    }

    /**
     * Helper to instantiate a Beneficiary with an active TUPAD enrollment for household testing.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeHouseholdBeneficiary(array $attributes, int $year): Beneficiary
    {
        $beneficiary = $this->makeBeneficiary($attributes);

        $program = new Program([
            'id' => 1,
            'code' => 'TUPAD',
            'name' => 'TUPAD',
        ]);

        $enrollment = new BeneficiaryProgram([
            'id' => 1,
            'beneficiary_id' => $beneficiary->id,
            'program_id' => $program->id,
            'availment_year' => $year,
            'status' => 'approved',
        ]);
        $enrollment->setRelation('program', $program);

        $beneficiary->setRelation('beneficiaryPrograms', collect([$enrollment]));

        return $beneficiary;
    }
}
