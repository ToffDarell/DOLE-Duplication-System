<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\DuplicateFlag;

class DuplicateDetectionService
{
    /**
     * Check a proposed beneficiary payload against existing records in the database.
     * Returns an array containing match details and highest match score.
     *
     * @param  array  $data  Beneficiary input data
     * @param  int|null  $excludeId  ID of beneficiary to exclude (for update checks)
     * @return array{has_duplicates: bool, flags: array, is_exact: bool, max_score: int}
     */
    public function checkDuplicates(array $data, ?int $excludeId = null): array
    {
        $query = Beneficiary::query();
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $candidates = $query->get();
        $matches = [];
        $maxScore = 0;
        $hasExact = false;

        $inputLastName = mb_strtolower(trim($data['last_name'] ?? ''));
        $inputFirstName = mb_strtolower(trim($data['first_name'] ?? ''));
        $inputDob = isset($data['date_of_birth']) ? date('Y-m-d', strtotime($data['date_of_birth'])) : null;
        $inputGovId = ! empty($data['government_id_number']) ? preg_replace('/[^a-zA-Z0-9]/', '', $data['government_id_number']) : null;
        $inputContact = ! empty($data['contact_number']) ? preg_replace('/[^0-9]/', '', $data['contact_number']) : null;
        $inputMuni = mb_strtolower(trim($data['municipality'] ?? ''));
        $inputBrgy = mb_strtolower(trim($data['barangay'] ?? ''));

        foreach ($candidates as $candidate) {
            $score = 0;
            $matchedFields = [];

            // 1. Name Match (Up to 40 pts)
            $candLastName = mb_strtolower(trim($candidate->last_name));
            $candFirstName = mb_strtolower(trim($candidate->first_name));

            $nameScore = 0;
            if ($inputLastName === $candLastName && $inputFirstName === $candFirstName) {
                $nameScore = 40;
                $matchedFields['name'] = 'Exact name match (40 pts)';
            } else {
                // Soundex & Levenshtein match
                $soundexLastInput = soundex($inputLastName);
                $soundexLastCand = soundex($candLastName);
                $soundexFirstInput = soundex($inputFirstName);
                $soundexFirstCand = soundex($candFirstName);

                similar_text($inputLastName.' '.$inputFirstName, $candLastName.' '.$candFirstName, $percent);

                if ($soundexLastInput === $soundexLastCand && $soundexFirstInput === $soundexFirstCand) {
                    $nameScore = 35;
                    $matchedFields['name'] = 'Phonetic (Soundex) name match (35 pts)';
                } elseif ($percent >= 80) {
                    $nameScore = round(($percent / 100) * 40);
                    $matchedFields['name'] = sprintf('Fuzzy name match (%.0f%% similarity, %d pts)', $percent, $nameScore);
                }
            }
            $score += $nameScore;

            // 2. Date of Birth Match (30 pts)
            $candDob = $candidate->date_of_birth ? $candidate->date_of_birth->format('Y-m-d') : null;
            if ($inputDob && $candDob && $inputDob === $candDob) {
                $score += 30;
                $matchedFields['dob'] = 'Same Date of Birth (30 pts)';
            }

            // 3. Address Match (15 pts)
            $candMuni = mb_strtolower(trim($candidate->municipality ?? ''));
            $candBrgy = mb_strtolower(trim($candidate->barangay ?? ''));
            if ($inputMuni && $candMuni && $inputMuni === $candMuni && $inputBrgy && $candBrgy && $inputBrgy === $candBrgy) {
                $score += 15;
                $matchedFields['address'] = 'Same Municipality & Barangay (15 pts)';
            }

            // 4. Contact Match (15 pts)
            $candContact = ! empty($candidate->contact_number) ? preg_replace('/[^0-9]/', '', $candidate->contact_number) : null;
            if ($inputContact && $candContact && $inputContact === $candContact) {
                $score += 15;
                $matchedFields['contact'] = 'Same Contact Number (15 pts)';
            }

            // 5. Check Exact Match Criteria: full name + DOB + government ID (if present) + contact number
            $candGovId = ! empty($candidate->government_id_number) ? preg_replace('/[^a-zA-Z0-9]/', '', $candidate->government_id_number) : null;
            $isExactMatch = false;
            if ($inputLastName === $candLastName && $inputFirstName === $candFirstName && $inputDob === $candDob) {
                if (($inputGovId && $candGovId && $inputGovId === $candGovId) || ($inputContact && $candContact && $inputContact === $candContact)) {
                    $isExactMatch = true;
                    $score = 100;
                    $matchedFields['exact'] = 'Exact match across Name, DOB, and Gov ID / Contact (100 pts)';
                }
            }

            if ($score >= 70 || $isExactMatch) {
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
                'status' => 'pending',
            ]);
        }
    }
}
