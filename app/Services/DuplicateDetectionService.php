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
        'TUPAD' => ['SPES', 'GIP', 'DILP'],
        'SPES' => ['TUPAD', 'GIP'],
        'GIP' => ['TUPAD', 'SPES'],
        'DILP' => ['TUPAD'],
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

            $isExactNameMatch = false;
            $isTransposedNameMatch = false;
            $isCompoundSurnameMatch = false;
            $nameScore = 0;

            if ($inputLastName === $candLastName && $inputFirstName === $candFirstName) {
                $isExactNameMatch = true;
                $nameScore = 40;
                $matchedFields['name'] = 'Exact name match (40 pts)';
            } elseif (! empty($cleanInputLast) && $cleanInputLast === $cleanCandLast && ! empty($cleanInputFirst) && $cleanInputFirst === $cleanCandFirst) {
                $isExactNameMatch = true;
                $nameScore = 40;
                $matchedFields['name'] = 'Normalized name match (e.g. De la Cruz vs Dela Cruz) (40 pts)';
            } elseif (
                // Transposed First & Last Names (e.g., Cruz, Juan vs Juan, Cruz)
                (! empty($cleanInputLast) && ! empty($cleanCandFirst) && $cleanInputLast === $cleanCandFirst) &&
                (! empty($cleanInputFirst) && ! empty($cleanCandLast) && $cleanInputFirst === $cleanCandLast)
            ) {
                $isTransposedNameMatch = true;
                $nameScore = 38;
                $matchedFields['name'] = 'Transposed name match (Swapped First and Last Name) (38 pts)';
            } else {
                // Check for Compound / Maiden Name Variation (e.g., Santos vs Santos-Cruz, Santos Cruz, Cruz-Santos)
                $inputLastTokens = preg_split('/[\s\-]+/', $inputLastName, -1, PREG_SPLIT_NO_EMPTY);
                $candLastTokens = preg_split('/[\s\-]+/', $candLastName, -1, PREG_SPLIT_NO_EMPTY);

                $hasCompoundMatch = false;
                if ($inputFirstName === $candFirstName || $cleanInputFirst === $cleanCandFirst) {
                    if (
                        (! empty($cleanInputLast) && ! empty($cleanCandLast) && (str_contains($cleanInputLast, $cleanCandLast) || str_contains($cleanCandLast, $cleanInputLast))) ||
                        ! empty(array_intersect($inputLastTokens, $candLastTokens))
                    ) {
                        $hasCompoundMatch = true;
                    }
                }

                if ($hasCompoundMatch) {
                    $isCompoundSurnameMatch = true;
                    $nameScore = 35;
                    $matchedFields['name'] = 'Compound / Maiden name variation match (e.g. Santos vs Santos-Cruz) (35 pts)';
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
            }
            $score += $nameScore;

            // 2. Date of Birth Match (30 pts)
            $candDob = $candidate->date_of_birth ? $candidate->date_of_birth->format('Y-m-d') : null;
            $isSameDob = ($enableDobCheck && $inputDob && $candDob && $inputDob === $candDob);
            if ($isSameDob) {
                $score += 30;
                $matchedFields['dob'] = 'Same Date of Birth (30 pts)';
            }

            // 3. Government ID Check (35 pts)
            $candGovId = ! empty($candidate->government_id_number) ? preg_replace('/[^a-zA-Z0-9]/', '', $candidate->government_id_number) : null;
            $isSameGovId = ($enableGovIdCheck && $inputGovId && $candGovId && $inputGovId === $candGovId);
            if ($isSameGovId) {
                $score += 35;
                $matchedFields['gov_id'] = 'Same Government ID Number (35 pts)';
            }

            // 4. Address Match (15 pts)
            $candMuni = mb_strtolower(trim($candidate->municipality ?? ''));
            $candBrgy = mb_strtolower(trim($candidate->barangay ?? ''));
            $isSameAddress = ($inputMuni && $candMuni && $inputMuni === $candMuni && $inputBrgy && $candBrgy && $inputBrgy === $candBrgy);
            if ($isSameAddress) {
                $score += 15;
                $matchedFields['address'] = 'Same Municipality & Barangay (15 pts)';
            }

            // 5. Contact Match (15 pts)
            $candContact = ! empty($candidate->contact_number) ? preg_replace('/[^0-9]/', '', $candidate->contact_number) : null;
            $isSameContact = ($inputContact && $candContact && $inputContact === $candContact);
            if ($isSameContact) {
                $score += 15;
                $matchedFields['contact'] = 'Same Contact Number (15 pts)';
            }

            // 6. Name-First Priority & Permutation Override Rules
            // Exact Name Rule:
            $isSameNameDifferentIdentity = false;
            if ($isExactNameMatch) {
                if ($score < 75) {
                    $score = 75;
                }
                if (! $isSameDob || ! $isSameGovId) {
                    $isSameNameDifferentIdentity = true;
                    $matchedFields['same_name_alert'] = sprintf(
                        'Exact Name Match with different profile details (Existing DOB: %s vs Entered: %s)',
                        $candDob ?? 'N/A',
                        $inputDob ?? 'N/A'
                    );
                }
            }

            // Transposed Name Rule (Swapped First and Last Names with matching DOB):
            if ($isTransposedNameMatch && $isSameDob) {
                if ($score < 75) {
                    $score = 75;
                }
                $matchedFields['transposed_alert'] = sprintf(
                    'Transposed Name Match (Swapped First and Last Name) with matching Date of Birth (%s)',
                    $candDob ?? 'N/A'
                );
            }

            // Compound / Maiden Name Rule (with matching DOB):
            if ($isCompoundSurnameMatch && $isSameDob) {
                if ($score < 75) {
                    $score = 75;
                }
                $matchedFields['compound_alert'] = sprintf(
                    'Compound / Maiden Name Match (%s vs %s) with matching Date of Birth (%s)',
                    $candidate->last_name,
                    $data['last_name'] ?? '',
                    $candDob ?? 'N/A'
                );
            }

            // Fuzzy Token & SOUNDEX First-Name Rule:
            // e.g. "Caliao, Atheo" vs "Caliao, Atheo Jessar" (same last name, matching primary first name or SOUNDEX)
            $inputFirstToken = explode(' ', $inputFirstName)[0] ?? '';
            $candFirstToken = explode(' ', $candFirstName)[0] ?? '';

            if (! empty($cleanInputLast) && $cleanInputLast === $cleanCandLast) {
                $isTokenMatch = (! empty($inputFirstToken) && ! empty($candFirstToken)) &&
                    ($inputFirstToken === $candFirstToken || str_contains($candFirstName, $inputFirstToken) || str_contains($inputFirstName, $candFirstToken));
                $isSoundexMatch = (! empty($cleanInputFirst) && ! empty($cleanCandFirst)) &&
                    soundex($cleanInputFirst) === soundex($cleanCandFirst);

                if ($isTokenMatch || $isSoundexMatch) {
                    if ($score < 75) {
                        $score = 75;
                    }
                    if (! $isSameDob || ! $isSameGovId) {
                        $isSameNameDifferentIdentity = true;
                        $matchedFields['fuzzy_token_alert'] = sprintf(
                            'Fuzzy Name / Token Variation Match ("%s, %s" vs "%s, %s")',
                            $candidate->last_name,
                            $candidate->first_name,
                            $data['last_name'] ?? '',
                            $data['first_name'] ?? ''
                        );
                    }
                }
            }

            // 7. Check Exact Match Criteria: full name + DOB + government ID / contact number
            $isExactMatch = false;
            if ($isExactNameMatch && $isSameDob && ($isSameGovId || $isSameContact)) {
                $isExactMatch = true;
                $score = 100;
                $matchedFields['exact'] = 'Exact match across Name, DOB, and Gov ID / Contact (100 pts)';
            }

            // Purok / Address & Sibling Comparison insight
            $rawInputAddr = trim($data['address'] ?? '');
            $rawCandAddr = trim($candidate->address ?? '');
            if ($rawInputAddr !== '' && $rawCandAddr !== '') {
                if (mb_strtolower($rawInputAddr) === mb_strtolower($rawCandAddr)) {
                    $matchedFields['purok_insight'] = "Identical Address / Purok: \"{$candidate->address}\" in {$candidate->barangay} — likely same household.";
                } else {
                    $matchedFields['purok_insight'] = "Different Address / Purok: New=\"{$rawInputAddr}\" vs Existing=\"{$rawCandAddr}\" in {$candidate->barangay} — potential siblings or relatives in separate houses.";
                }
            } elseif ($rawCandAddr !== '') {
                $matchedFields['purok_insight'] = "Existing Address: \"{$rawCandAddr}\" in {$candidate->barangay} — verify if new applicant lives in a separate home.";
            }

            if ($score >= $threshold || $isExactMatch || $isExactNameMatch) {
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

                // Cross-Program Conflict Detection
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

                // Check for previous availment history and returning beneficiary status
                $latestEnrollment = $candidate->beneficiaryPrograms->sortByDesc('availment_year')->first();
                $lastAvailedInfo = null;
                if ($latestEnrollment && $latestEnrollment->program) {
                    $lastAvailedInfo = "{$latestEnrollment->program->code} {$latestEnrollment->availment_year}";
                }

                $alreadyEnrolledCurrentYear = $candidate->beneficiaryPrograms
                    ->filter(fn ($bp) => $bp->program?->code === $inputProgramCode && $bp->availment_year == $inputYear)
                    ->filter(fn ($bp) => in_array($bp->status, ['pending', 'approved']))
                    ->isNotEmpty();

                $isReturning = false;
                if (($isExactMatch || $score >= 80) && ! $alreadyEnrolledCurrentYear && $candidate->beneficiaryPrograms->isNotEmpty()) {
                    $isReturning = true;
                }

                $matches[] = [
                    'matched_beneficiary' => $candidate,
                    'matched_beneficiary_id' => $candidate->id,
                    'matched_beneficiary_name' => $candidate->full_name,
                    'existing_dob' => $candDob,
                    'input_dob' => $inputDob,
                    'match_score' => $score,
                    'match_type' => $matchType,
                    'matched_fields' => $matchedFields,
                    'is_exact_name_match' => $isExactNameMatch,
                    'is_transposed_name_match' => $isTransposedNameMatch,
                    'is_compound_surname_match' => $isCompoundSurnameMatch,
                    'is_same_name_diff_dob' => $isExactNameMatch && ! $isSameDob,
                    'is_same_name_diff_identity' => $isSameNameDifferentIdentity,
                    'is_returning_beneficiary' => $isReturning,
                    'last_availment' => $lastAvailedInfo,
                    'latest_program_code' => $latestEnrollment?->program?->code,
                    'latest_availment_year' => $latestEnrollment?->availment_year,
                    'same_program_current_year' => $alreadyEnrolledCurrentYear,
                ];
            }
        }

        // Sort matches by score descending
        usort($matches, fn ($a, $b) => $b['match_score'] <=> $a['match_score']);

        $topMatch = $matches[0] ?? null;
        $hasReturningMatch = $topMatch['is_returning_beneficiary'] ?? false;
        $hasSameNameDiffIdentity = $topMatch['is_same_name_diff_identity'] ?? false;

        return [
            'has_duplicates' => count($matches) > 0,
            'flags' => $matches,
            'duplicates' => $matches,
            'is_exact' => $hasExact,
            'max_score' => $maxScore,
            'cross_program_conflicts' => $crossProgramConflicts,
            'is_returning_beneficiary' => $hasReturningMatch,
            'is_same_name_diff_identity' => $hasSameNameDiffIdentity,
            'returning_match' => $hasReturningMatch ? $topMatch : null,
            'top_match' => $topMatch,
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
    public function recordDuplicateFlags(Beneficiary $beneficiary, array $flags, string $status = 'pending', ?string $remarks = null, ?int $reviewedBy = null): void
    {
        foreach ($flags as $flag) {
            $flagMatchedId = $flag['matched_beneficiary_id'] ?? ($flag['matched_beneficiary'] ? $flag['matched_beneficiary']->id : null);
            if (! $flagMatchedId || $flagMatchedId == $beneficiary->id) {
                continue;
            }

            DuplicateFlag::create([
                'beneficiary_id' => $beneficiary->id,
                'matched_beneficiary_id' => $flagMatchedId,
                'match_score' => $flag['match_score'] ?? 75,
                'match_type' => $flag['match_type'] ?? 'medium',
                'matched_fields' => $flag['matched_fields'] ?? [],
                'household_match_flag' => $flag['household_match_flag'] ?? false,
                'status' => $status,
                'remarks' => $remarks,
                'reviewed_by' => $reviewedBy,
            ]);
        }
    }
}
