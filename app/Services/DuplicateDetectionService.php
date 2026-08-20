<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\DuplicateFlag;
use App\Models\SystemSetting;

class DuplicateDetectionService
{
    /**
     * Cross-program conflict matrix.
     * Key = program being applied for, Value = array of programs that conflict with it.
     *
     * @var array<string, array<string>>
     */
    private const CROSS_PROGRAM_CONFLICTS = [
        'TUPAD' => ['SPES', 'GIP'],
        'SPES' => ['TUPAD', 'GIP'],
        'GIP' => ['TUPAD', 'SPES'],
        'DILP' => [],
    ];

    /**
     * Check a proposed beneficiary payload against existing records in the database.
     * Detects both identity duplicates AND cross-program eligibility conflicts.
     *
     * @param  array  $data  Beneficiary input data (must include 'program_code' and 'availment_year' for cross-program checks)
     * @param  int|null  $excludeId  ID of beneficiary to exclude (for update checks)
     * @return array{has_duplicates: bool, flags: array, is_exact: bool, max_score: int, cross_program_conflicts: array}
     */
    public function checkDuplicates(array $data, ?int $excludeId = null): array
    {
        $query = Beneficiary::with(['beneficiaryPrograms.program']);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $candidates = $query->get();
        $matches = [];
        $maxScore = 0;
        $hasExact = false;
        $crossProgramConflicts = [];

        $inputLastName = mb_strtolower(trim($data['last_name'] ?? ''));
        $inputFirstName = mb_strtolower(trim($data['first_name'] ?? ''));
        $inputDob = isset($data['date_of_birth']) ? date('Y-m-d', strtotime($data['date_of_birth'])) : null;
        $inputGovId = ! empty($data['government_id_number']) ? preg_replace('/[^a-zA-Z0-9]/', '', $data['government_id_number']) : null;
        $inputContact = ! empty($data['contact_number']) ? preg_replace('/[^0-9]/', '', $data['contact_number']) : null;
        $inputMuni = mb_strtolower(trim($data['municipality'] ?? ''));
        $inputBrgy = mb_strtolower(trim($data['barangay'] ?? ''));

        $inputProgramCode = strtoupper($data['program_code'] ?? '');
        $inputYear = (int) ($data['availment_year'] ?? date('Y'));

        $threshold = (int) SystemSetting::get('duplicate_threshold', 75);
        $enableDobCheck = SystemSetting::get('enable_exact_dob_check', '1') === '1';
        $enableGovIdCheck = SystemSetting::get('enable_gov_id_check', '1') === '1';

        foreach ($candidates as $candidate) {
            $score = 0;
            $matchedFields = [];

            // 1. Name Match (Up to 40 pts)
            $candLastName = mb_strtolower(trim($candidate->last_name));
            $candFirstName = mb_strtolower(trim($candidate->first_name));

            $cleanInputLast = preg_replace('/[^a-z0-9]/', '', $inputLastName);
            $cleanCandLast = preg_replace('/[^a-z0-9]/', '', $candLastName);
            $cleanInputFirst = preg_replace('/[^a-z0-9]/', '', $inputFirstName);
            $cleanCandFirst = preg_replace('/[^a-z0-9]/', '', $candFirstName);

            $nameScore = 0;
            if ($inputLastName === $candLastName && $inputFirstName === $candFirstName) {
                $nameScore = 40;
                $matchedFields['name'] = 'Exact name match (40 pts)';
            } elseif (! empty($cleanInputLast) && $cleanInputLast === $cleanCandLast && ! empty($cleanInputFirst) && $cleanInputFirst === $cleanCandFirst) {
                $nameScore = 40;
                $matchedFields['name'] = 'Normalized name match (e.g. De la Cruz vs Dela Cruz) (40 pts)';
            } else {
                // Enhanced Phonetic (Metaphone/Soundex) & Levenshtein matching for misspelled names
                $lastLev = (! empty($cleanInputLast) && ! empty($cleanCandLast)) ? levenshtein($cleanInputLast, $cleanCandLast) : 99;
                $lastMetaMatch = (! empty($cleanInputLast) && ! empty($cleanCandLast)) &&
                    (metaphone($cleanInputLast) === metaphone($cleanCandLast) || soundex($cleanInputLast) === soundex($cleanCandLast));

                $firstTokensInput = array_filter(explode(' ', $inputFirstName));
                $firstTokensCand = array_filter(explode(' ', $candFirstName));

                $firstMetaMatch = false;
                $firstLevMatch = false;
                foreach ($firstTokensInput as $tIn) {
                    $cIn = preg_replace('/[^a-z0-9]/', '', $tIn);
                    if (empty($cIn)) {
                        continue;
                    }
                    foreach ($firstTokensCand as $tCand) {
                        $cCand = preg_replace('/[^a-z0-9]/', '', $tCand);
                        if (empty($cCand)) {
                            continue;
                        }
                        if (metaphone($cIn) === metaphone($cCand) || soundex($cIn) === soundex($cCand)) {
                            $firstMetaMatch = true;
                        }
                        if (levenshtein($cIn, $cCand) <= 2) {
                            $firstLevMatch = true;
                        }
                    }
                }

                similar_text($cleanInputLast.' '.$cleanInputFirst, $cleanCandLast.' '.$cleanCandFirst, $percent);

                if (($lastLev <= 2 || $lastMetaMatch) && ($firstLevMatch || $firstMetaMatch)) {
                    $nameScore = 35;
                    $matchedFields['name'] = 'Phonetic & Fuzzy name match (misspelled name detected) (35 pts)';
                } elseif ($percent >= 70) {
                    $nameScore = round(($percent / 100) * 40);
                    $matchedFields['name'] = sprintf('Fuzzy name match (%.0f%% similarity, %d pts)', $percent, $nameScore);
                }
            }
            $score += $nameScore;

            // 2. Date of Birth Match (30 pts)
            $candDob = $candidate->date_of_birth ? $candidate->date_of_birth->format('Y-m-d') : null;
            if ($enableDobCheck && $inputDob && $candDob && $inputDob === $candDob) {
                $score += 30;
                $matchedFields['dob'] = 'Same Date of Birth (30 pts)';
            }

            // 3. Government ID Check (35 pts)
            $candGovId = ! empty($candidate->government_id_number) ? preg_replace('/[^a-zA-Z0-9]/', '', $candidate->government_id_number) : null;
            if ($enableGovIdCheck && $inputGovId && $candGovId && $inputGovId === $candGovId) {
                $score += 35;
                $matchedFields['gov_id'] = 'Same Government ID Number (35 pts)';
            }

            // 4. Address Match (15 pts)
            $candMuni = mb_strtolower(trim($candidate->municipality ?? ''));
            $candBrgy = mb_strtolower(trim($candidate->barangay ?? ''));
            if ($inputMuni && $candMuni && $inputMuni === $candMuni && $inputBrgy && $candBrgy && $inputBrgy === $candBrgy) {
                $score += 15;
                $matchedFields['address'] = 'Same Municipality & Barangay (15 pts)';
            }

            // 5. Contact Match (15 pts)
            $candContact = ! empty($candidate->contact_number) ? preg_replace('/[^0-9]/', '', $candidate->contact_number) : null;
            if ($inputContact && $candContact && $inputContact === $candContact) {
                $score += 15;
                $matchedFields['contact'] = 'Same Contact Number (15 pts)';
            }

            // 6. Check Exact Match Criteria: full name + DOB + government ID / contact number
            $isExactMatch = false;
            if ($inputLastName === $candLastName && $inputFirstName === $candFirstName && $inputDob === $candDob) {
                if (($inputGovId && $candGovId && $inputGovId === $candGovId) || ($inputContact && $candContact && $inputContact === $candContact)) {
                    $isExactMatch = true;
                    $score = 100;
                    $matchedFields['exact'] = 'Exact match across Name, DOB, and Gov ID / Contact (100 pts)';
                }
            }

            if ($score >= $threshold || $isExactMatch) {
                $matchType = 'low';
                if ($isExactMatch || $score >= 90) {
                    $matchType = 'exact';
                } elseif ($score >= 80) {
                    $matchType = 'high';
                } else {
                    $matchType = 'medium';
                }

                if ($isExactMatch) {
                    $hasExact = true;
                }

                if ($score > $maxScore) {
                    $maxScore = $score;
                }

                // 7. Cross-Program Conflict Detection
                $conflicts = $this->detectCrossProgramConflicts(
                    $candidate,
                    $inputProgramCode,
                    $inputYear
                );

                if (! empty($conflicts)) {
                    $matchedFields['cross_program'] = implode(' | ', $conflicts);
                    $crossProgramConflicts[] = [
                        'beneficiary_id' => $candidate->id,
                        'beneficiary_name' => $candidate->full_name,
                        'conflicts' => $conflicts,
                    ];
                }

                // Also check same-program same-year re-enrollment
                $sameProgramSameYear = $this->detectSameProgramSameYear(
                    $candidate,
                    $inputProgramCode,
                    $inputYear
                );

                if ($sameProgramSameYear) {
                    $matchedFields['same_program'] = $sameProgramSameYear;
                }

                $matches[] = [
                    'matched_beneficiary' => $candidate,
                    'matched_beneficiary_id' => $candidate->id,
                    'match_score' => $score,
                    'match_type' => $matchType,
                    'matched_fields' => $matchedFields,
                ];
            }
        }

        // Sort matches by score descending
        usort($matches, fn ($a, $b) => $b['match_score'] <=> $a['match_score']);

        return [
            'has_duplicates' => count($matches) > 0,
            'flags' => $matches,
            'is_exact' => $hasExact,
            'max_score' => $maxScore,
            'cross_program_conflicts' => $crossProgramConflicts,
        ];
    }

    /**
     * Detect cross-program conflicts for a matched candidate.
     * Returns human-readable conflict descriptions.
     *
     * @return array<string>
     */
    protected function detectCrossProgramConflicts(Beneficiary $candidate, string $newProgramCode, int $availmentYear): array
    {
        if (empty($newProgramCode)) {
            return [];
        }

        $conflictingPrograms = self::CROSS_PROGRAM_CONFLICTS[$newProgramCode] ?? [];
        if (empty($conflictingPrograms)) {
            return [];
        }

        $conflicts = [];
        $activeEnrollments = $candidate->beneficiaryPrograms
            ->filter(fn ($bp) => in_array($bp->status, ['pending', 'approved']))
            ->filter(fn ($bp) => $bp->availment_year == $availmentYear);

        foreach ($activeEnrollments as $enrollment) {
            $enrolledCode = $enrollment->program?->code;
            if ($enrolledCode && in_array($enrolledCode, $conflictingPrograms)) {
                $conflicts[] = "CROSS-PROGRAM CONFLICT: Already enrolled in {$enrolledCode} ({$availmentYear}) — cannot simultaneously avail {$newProgramCode}";
            }
        }

        // Same program, same year re-enrollment is handled separately
        return $conflicts;
    }

    /**
     * Detect same-program same-year re-enrollment.
     */
    protected function detectSameProgramSameYear(Beneficiary $candidate, string $programCode, int $availmentYear): ?string
    {
        if (empty($programCode)) {
            return null;
        }

        $alreadyEnrolled = $candidate->beneficiaryPrograms
            ->filter(fn ($bp) => $bp->program?->code === $programCode)
            ->filter(fn ($bp) => $bp->availment_year == $availmentYear)
            ->filter(fn ($bp) => in_array($bp->status, ['pending', 'approved']))
            ->isNotEmpty();

        if ($alreadyEnrolled) {
            $frequencyRule = in_array($programCode, ['TUPAD', 'SPES', 'DILP'])
                ? 'Maximum once per year'
                : 'Once in a lifetime';

            return "SAME PROGRAM: Already availed {$programCode} in {$availmentYear}. Rule: {$frequencyRule}.";
        }

        // GIP lifetime check
        if ($programCode === 'GIP') {
            $everEnrolled = $candidate->beneficiaryPrograms
                ->filter(fn ($bp) => $bp->program?->code === 'GIP')
                ->filter(fn ($bp) => in_array($bp->status, ['pending', 'approved']))
                ->isNotEmpty();

            if ($everEnrolled) {
                return 'GIP LIFETIME RULE: Beneficiary has already participated in GIP. This program is ONCE IN A LIFETIME only.';
            }
        }

        return null;
    }

    /**
     * Check for household-level duplicates (TUPAD only).
     *
     * Detects different individuals sharing the same surname in the same Barangay
     * who are both enrolled in TUPAD for the same calendar year. These are NOT
     * identity duplicates — they are separate people (e.g., siblings) who may or
     * may not reside in the same physical household.
     *
     * DOLE Policy: "1 beneficiary per household per calendar year" for TUPAD.
     * Individuals at different addresses (Sitio/Street/House #) are legally
     * separate households and ARE allowed to avail simultaneously.
     *
     * @param  array  $data  Beneficiary input data
     * @param  int|null  $excludeId  ID to exclude (for update checks)
     * @return array{has_household_flags: bool, flags: array}
     */
    public function checkHouseholdDuplicates(array $data, ?int $excludeId = null): array
    {
        $programCode = strtoupper($data['program_code'] ?? '');

        // Household check only applies to TUPAD
        if ($programCode !== 'TUPAD') {
            return ['has_household_flags' => false, 'flags' => []];
        }

        $inputLastName = mb_strtolower(trim($data['last_name'] ?? ''));
        $inputFirstName = mb_strtolower(trim($data['first_name'] ?? ''));
        $inputBrgy = mb_strtolower(trim($data['barangay'] ?? ''));
        $inputAddress = mb_strtolower(trim($data['address'] ?? ''));
        $inputYear = (int) ($data['availment_year'] ?? date('Y'));

        if (empty($inputLastName) || empty($inputBrgy)) {
            return ['has_household_flags' => false, 'flags' => []];
        }

        // Query candidates: same surname + same barangay + enrolled in TUPAD same year
        $query = Beneficiary::with(['beneficiaryPrograms.program'])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [$inputLastName])
            ->whereRaw('LOWER(TRIM(barangay)) = ?', [$inputBrgy]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Exclude records where the first name is identical (those are identity
        // duplicates handled by checkDuplicates, not household flags)
        $query->whereRaw('LOWER(TRIM(first_name)) != ?', [$inputFirstName]);

        $candidates = $query->get();
        $flags = [];

        foreach ($candidates as $candidate) {
            // Verify candidate has active TUPAD enrollment for the same year
            $hasTupadSameYear = $candidate->beneficiaryPrograms
                ->filter(fn ($bp) => $bp->program?->code === 'TUPAD')
                ->filter(fn ($bp) => $bp->availment_year == $inputYear)
                ->filter(fn ($bp) => in_array($bp->status, ['pending', 'approved']))
                ->isNotEmpty();

            if (! $hasTupadSameYear) {
                continue;
            }

            $candAddress = mb_strtolower(trim($candidate->address ?? ''));
            $addressesMatch = $inputAddress !== '' && $candAddress !== '' && $inputAddress === $candAddress;

            $matchedFields = [];
            $matchedFields['household_surname'] = "Same Surname: \"{$candidate->last_name}\" in Barangay \"{$candidate->barangay}\"";
            $matchedFields['household_program'] = "Both enrolled in TUPAD for calendar year {$inputYear}";

            if ($addressesMatch) {
                // Same physical address → likely same household → higher score
                $score = 65;
                $matchType = 'medium';
                $matchedFields['household_address'] = "SAME ADDRESS: Both records list identical address \"{$candidate->address}\" — likely SAME household.";
            } else {
                // Different address or unspecified → medium score (50%)
                $score = 50;
                $matchType = 'medium';

                if (empty($inputAddress) && empty($candAddress)) {
                    $matchedFields['household_address'] = 'No Sitio/House Address specified on both records — Validator must confirm if applicants live in separate physical houses.';
                } elseif (empty($inputAddress) || empty($candAddress)) {
                    $newAddr = ! empty($inputAddress) ? ($data['address'] ?? '') : '(not specified)';
                    $existAddr = ! empty($candAddress) ? $candidate->address : '(not specified)';
                    $matchedFields['household_address'] = "Incomplete address details: New=\"{$newAddr}\" vs Existing=\"{$existAddr}\" — Sitio/House No. needed to confirm separate households.";
                } else {
                    $rawNew = $data['address'] ?? '';
                    $matchedFields['household_address'] = "Different addresses detected: New=\"{$rawNew}\" vs Existing=\"{$candidate->address}\" — verify if truly separate households.";
                }
            }

            $matchedFields['household_action'] = 'HOUSEHOLD VERIFICATION NEEDED: Validator must confirm separate residency (e.g., Barangay Certificate, distinct Sitio/House Number).';

            $flags[] = [
                'matched_beneficiary' => $candidate,
                'matched_beneficiary_id' => $candidate->id,
                'match_score' => $score,
                'match_type' => $matchType,
                'matched_fields' => $matchedFields,
                'household_match_flag' => true,
            ];
        }

        // Sort by score descending
        usort($flags, fn ($a, $b) => $b['match_score'] <=> $a['match_score']);

        return [
            'has_household_flags' => count($flags) > 0,
            'flags' => $flags,
        ];
    }

    /**
     * Create DuplicateFlag records for a saved beneficiary.
     */
    public function recordDuplicateFlags(Beneficiary $beneficiary, array $flags): void
    {
        foreach ($flags as $flag) {
            DuplicateFlag::create([
                'beneficiary_id' => $beneficiary->id,
                'matched_beneficiary_id' => $flag['matched_beneficiary_id'],
                'match_score' => $flag['match_score'],
                'match_type' => $flag['match_type'],
                'matched_fields' => $flag['matched_fields'],
                'household_match_flag' => $flag['household_match_flag'] ?? false,
                'status' => 'pending',
            ]);
        }
    }
}
