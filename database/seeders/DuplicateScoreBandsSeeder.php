<?php

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DuplicateFlag;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;

class DuplicateScoreBandsSeeder extends Seeder
{
    /**
     * Seed 20 realistic Bukidnon flagged beneficiaries spanning all DOLE percentage bands:
     * - 100% (Exact Match)
     * - 95%, 90%, 87%, 85% (Wrong Spelling / Human Error)
     * - 70%–84% (Missing Suffix / Different Middle Name / Namesake)
     */
    public function run(): void
    {
        $creatorId = User::value('id');
        $programs = Program::pluck('id', 'code');
        $year = (int) date('Y');

        $scenarios = [
            // --- 100% EXACT MATCHES (4) ---
            [
                'score' => 100, 'type' => 'exact', 'program' => 'TUPAD',
                'muni' => 'Valencia City', 'brgy' => 'Poblacion', 'sex' => 'Male',
                'master' => ['first' => 'ROBERTO', 'mid' => 'SANTOS', 'last' => 'MAGBANUA', 'suf' => null, 'dob' => '1988-03-14', 'addr' => 'Purok 2A', 'gov' => 'PSN-1001-8803'],
                'cand' => ['first' => 'ROBERTO', 'mid' => 'SANTOS', 'last' => 'MAGBANUA', 'suf' => null, 'dob' => '1988-03-14', 'addr' => 'Purok 2A', 'gov' => 'PSN-1001-8803'],
                'fields' => ['exact' => 'Exact match across Full Name, DOB, Address, and PhilSys ID (100 pts)'],
            ],
            [
                'score' => 100, 'type' => 'exact', 'program' => 'TUPAD',
                'muni' => 'Malaybalay City', 'brgy' => 'Casisang', 'sex' => 'Female',
                'master' => ['first' => 'MARITES', 'mid' => 'RIVERA', 'last' => 'BAUTISTA', 'suf' => null, 'dob' => '1984-07-21', 'addr' => 'Zone 4', 'gov' => 'PSN-1002-8407'],
                'cand' => ['first' => 'MARITES', 'mid' => 'RIVERA', 'last' => 'BAUTISTA', 'suf' => null, 'dob' => '1984-07-21', 'addr' => 'Zone 4', 'gov' => 'PSN-1002-8407'],
                'fields' => ['exact' => 'Exact match across Full Name, DOB, Address, and PhilSys ID (100 pts)'],
            ],
            [
                'score' => 100, 'type' => 'exact', 'program' => 'SPES',
                'muni' => 'Manolo Fortich', 'brgy' => 'Tankulan', 'sex' => 'Female',
                'master' => ['first' => 'KATRINA', 'mid' => 'DIZON', 'last' => 'VILLARREAL', 'suf' => null, 'dob' => '2005-02-11', 'addr' => 'Purok 1', 'gov' => 'SPES-2026-1003'],
                'cand' => ['first' => 'KATRINA', 'mid' => 'DIZON', 'last' => 'VILLARREAL', 'suf' => null, 'dob' => '2005-02-11', 'addr' => 'Purok 1', 'gov' => 'SPES-2026-1003'],
                'fields' => ['exact' => 'Exact match across Full Name, DOB, and Student ID (100 pts)'],
            ],
            [
                'score' => 100, 'type' => 'exact', 'program' => 'DILP',
                'muni' => 'Maramag', 'brgy' => 'North Poblacion', 'sex' => 'Male',
                'master' => ['first' => 'EDGARDO', 'mid' => 'CRUZ', 'last' => 'SALVADOR', 'suf' => null, 'dob' => '1979-11-05', 'addr' => 'Purok 5', 'gov' => 'PSN-1004-7911'],
                'cand' => ['first' => 'EDGARDO', 'mid' => 'CRUZ', 'last' => 'SALVADOR', 'suf' => null, 'dob' => '1979-11-05', 'addr' => 'Purok 5', 'gov' => 'PSN-1004-7911'],
                'fields' => ['exact' => 'Exact match across Full Name, DOB, Address, and PhilSys ID (100 pts)'],
            ],

            // --- 95% & 90% WRONG SPELLING / HUMAN ERROR (6) ---
            [
                'score' => 95, 'type' => 'high', 'program' => 'TUPAD',
                'muni' => 'Valencia City', 'brgy' => 'Lumbo', 'sex' => 'Male',
                'master' => ['first' => 'DANILO', 'mid' => 'TORRES', 'last' => 'DELA CRUZ', 'suf' => null, 'dob' => '1991-06-18', 'addr' => 'Purok 3', 'gov' => 'PSN-9501-9106'],
                'cand' => ['first' => 'DANILO', 'mid' => 'TORRES', 'last' => 'DELA CURZ', 'suf' => null, 'dob' => '1991-06-18', 'addr' => 'Purok 3', 'gov' => null],
                'fields' => ['name' => 'Misspelled surname typo ("DELA CRUZ" vs "DELA CURZ")', 'dob' => 'Exact Date of Birth match (30 pts)'],
            ],
            [
                'score' => 95, 'type' => 'high', 'program' => 'TUPAD',
                'muni' => 'Malaybalay City', 'brgy' => 'Sumpong', 'sex' => 'Female',
                'master' => ['first' => 'JENNIFER', 'mid' => 'AQUINO', 'last' => 'MENDOZA', 'suf' => null, 'dob' => '1993-09-22', 'addr' => 'Zone 2', 'gov' => 'PSN-9502-9309'],
                'cand' => ['first' => 'JENIFER', 'mid' => 'AQUINO', 'last' => 'MENDOZA', 'suf' => null, 'dob' => '1993-09-22', 'addr' => 'Zone 2', 'gov' => null],
                'fields' => ['name' => 'Single-letter first name typo ("JENNIFER" vs "JENIFER")', 'dob' => 'Exact Date of Birth match (30 pts)'],
            ],
            [
                'score' => 95, 'type' => 'high', 'program' => 'GIP',
                'muni' => 'Quezon', 'brgy' => 'Libertad', 'sex' => 'Male',
                'master' => ['first' => 'CHRISTIAN', 'mid' => 'REYES', 'last' => 'FERNANDEZ', 'suf' => null, 'dob' => '2001-04-08', 'addr' => 'Purok 6', 'gov' => 'PSN-9503-0104'],
                'cand' => ['first' => 'CRISTIAN', 'mid' => 'REYES', 'last' => 'FERNANDEZ', 'suf' => null, 'dob' => '2001-04-08', 'addr' => 'Purok 6', 'gov' => null],
                'fields' => ['name' => 'Phonetic spelling variation ("CHRISTIAN" vs "CRISTIAN")', 'dob' => 'Exact Date of Birth match (30 pts)'],
            ],
            [
                'score' => 90, 'type' => 'high', 'program' => 'TUPAD',
                'muni' => 'Lantapan', 'brgy' => 'Balila', 'sex' => 'Female',
                'master' => ['first' => 'ANALYN', 'mid' => 'FLORES', 'last' => 'CASTILLO', 'suf' => null, 'dob' => '1986-08-14', 'addr' => 'Purok 4', 'gov' => 'PSN-9001-8608'],
                'cand' => ['first' => 'ANALYN', 'mid' => 'FLORES', 'last' => 'CASTILLO', 'suf' => null, 'dob' => '1986-08-15', 'addr' => 'Purok 4', 'gov' => null],
                'fields' => ['name' => 'Exact name match (40 pts)', 'dob' => 'Single-digit birthdate encoding typo (1986-08-14 vs 1986-08-15)'],
            ],
            [
                'score' => 90, 'type' => 'high', 'program' => 'DILP',
                'muni' => 'Pangantucan', 'brgy' => 'Mendis', 'sex' => 'Male',
                'master' => ['first' => 'ARNEL', 'mid' => 'RAMOS', 'last' => 'SORIANO', 'suf' => null, 'dob' => '1982-12-03', 'addr' => 'Purok 1B', 'gov' => 'PSN-9002-8212'],
                'cand' => ['first' => 'ARNEL', 'mid' => 'RAMOS', 'last' => 'SURIANO', 'suf' => null, 'dob' => '1982-12-03', 'addr' => 'Purok 1B', 'gov' => null],
                'fields' => ['name' => 'Phonetic vowel typo ("SORIANO" vs "SURIANO")', 'dob' => 'Exact Date of Birth match (30 pts)'],
            ],
            [
                'score' => 90, 'type' => 'high', 'program' => 'SPES',
                'muni' => 'Don Carlos', 'brgy' => 'Poblacion Norte', 'sex' => 'Female',
                'master' => ['first' => 'MICHELLE', 'mid' => 'GOMEZ', 'last' => 'NAVARRO', 'suf' => null, 'dob' => '2004-10-19', 'addr' => 'Purok 7', 'gov' => 'SPES-9003-0410'],
                'cand' => ['first' => 'MICHELE', 'mid' => 'GOMEZ', 'last' => 'NAVARO', 'suf' => null, 'dob' => '2004-10-19', 'addr' => 'Purok 7', 'gov' => null],
                'fields' => ['name' => 'Double-letter typo in First & Last Name', 'dob' => 'Exact Date of Birth match (30 pts)'],
            ],

            // --- 87% & 85% HUMAN ERROR / ENCODING TYPOS (4) ---
            [
                'score' => 87, 'type' => 'high', 'program' => 'TUPAD',
                'muni' => 'Manolo Fortich', 'brgy' => 'Alae', 'sex' => 'Male',
                'master' => ['first' => 'JONATHAN', 'mid' => 'PEREZ', 'last' => 'DOMINGO', 'suf' => null, 'dob' => '1990-01-25', 'addr' => 'Purok 2', 'gov' => 'PSN-8701-9001'],
                'cand' => ['first' => 'JONATAN', 'mid' => 'PEREZ', 'last' => 'DOMINGO', 'suf' => null, 'dob' => '1990-01-26', 'addr' => 'Purok 2', 'gov' => null],
                'fields' => ['name' => 'Spelling typo ("JONATHAN" vs "JONATAN")', 'dob' => 'Single-digit birthdate typo (25 vs 26)'],
            ],
            [
                'score' => 87, 'type' => 'high', 'program' => 'TUPAD',
                'muni' => 'Impasugong', 'brgy' => 'Impalutao', 'sex' => 'Female',
                'master' => ['first' => 'ROSALINDA', 'mid' => 'VELASCO', 'last' => 'PANGANIBAN', 'suf' => null, 'dob' => '1980-05-09', 'addr' => 'Purok 3', 'gov' => 'PSN-8702-8005'],
                'cand' => ['first' => 'ROSALYNDA', 'mid' => 'VELASCO', 'last' => 'PANGANIBAN', 'suf' => null, 'dob' => '1980-05-08', 'addr' => 'Purok 3', 'gov' => null],
                'fields' => ['name' => 'Phonetic spelling variation ("ROSALINDA" vs "ROSALYNDA")', 'dob' => 'Single-digit birthdate typo'],
            ],
            [
                'score' => 85, 'type' => 'high', 'program' => 'TUPAD',
                'muni' => 'Talakag', 'brgy' => 'San Isidro', 'sex' => 'Male',
                'master' => ['first' => 'REYNALDO', 'mid' => 'AGUILAR', 'last' => 'CABRERA', 'suf' => null, 'dob' => '1977-03-17', 'addr' => 'Purok 8', 'gov' => 'PSN-8501-7703'],
                'cand' => ['first' => 'RAYNALDO', 'mid' => 'AGUILAR', 'last' => 'CABRERRA', 'suf' => null, 'dob' => '1977-03-17', 'addr' => 'Purok 8', 'gov' => null],
                'fields' => ['name' => 'Misspelled name encoding error ("REYNALDO CABRERA" vs "RAYNALDO CABRERRA")', 'dob' => 'Exact Date of Birth match'],
            ],
            [
                'score' => 85, 'type' => 'high', 'program' => 'DILP',
                'muni' => 'Valencia City', 'brgy' => 'Bagontaas', 'sex' => 'Female',
                'master' => ['first' => 'TERESITA', 'mid' => 'LOZANO', 'last' => 'ENRIQUEZ', 'suf' => null, 'dob' => '1983-06-30', 'addr' => 'Purok 9', 'gov' => 'PSN-8502-8306'],
                'cand' => ['first' => 'TERESITA', 'mid' => 'LOZANO', 'last' => 'ENRIQUES', 'suf' => null, 'dob' => '1983-06-29', 'addr' => 'Purok 9', 'gov' => null],
                'fields' => ['name' => 'Surname spelling typo ("ENRIQUEZ" vs "ENRIQUES")', 'dob' => '1-day birthdate encoding typo'],
            ],

            // --- 70%–84% MISSING SUFFIX / DIFFERENT MIDDLE NAME / NAMESAKE (6) ---
            [
                'score' => 82, 'type' => 'medium', 'program' => 'TUPAD',
                'muni' => 'Valencia City', 'brgy' => 'Nabag-o', 'sex' => 'Male',
                'master' => ['first' => 'RICARDO', 'mid' => 'MENDEZ', 'last' => 'VILLANUEVA', 'suf' => 'Jr.', 'dob' => '1994-04-12', 'addr' => 'Purok 1', 'gov' => 'PSN-8201-9404'],
                'cand' => ['first' => 'RICARDO', 'mid' => 'MENDEZ', 'last' => 'VILLANUEVA', 'suf' => null, 'dob' => '1994-04-12', 'addr' => 'Purok 1', 'gov' => null],
                'fields' => ['suffix_discrepancy' => 'Missing suffix ("Jr." vs blank) — categorized in 70%–84% DOLE band'],
            ],
            [
                'score' => 80, 'type' => 'medium', 'program' => 'TUPAD',
                'muni' => 'Malaybalay City', 'brgy' => 'Aglayan', 'sex' => 'Male',
                'master' => ['first' => 'EDUARDO', 'mid' => 'TAN', 'last' => 'OCAMPO', 'suf' => 'Sr.', 'dob' => '1970-08-03', 'addr' => 'Purok 4', 'gov' => 'PSN-8001-7008'],
                'cand' => ['first' => 'EDUARDO', 'mid' => 'TAN', 'last' => 'OCAMPO', 'suf' => 'Jr.', 'dob' => '1996-08-03', 'addr' => 'Purok 4', 'gov' => null],
                'fields' => ['suffix_discrepancy' => 'Different suffix ("Sr." vs "Jr.") — Father & Son check in 70%–84% DOLE band'],
            ],
            [
                'score' => 78, 'type' => 'medium', 'program' => 'TUPAD',
                'muni' => 'Maramag', 'brgy' => 'Dologon', 'sex' => 'Female',
                'master' => ['first' => 'MARIA', 'mid' => 'SANTOS', 'last' => 'PASCUA', 'suf' => null, 'dob' => '1989-11-20', 'addr' => 'Purok 2', 'gov' => 'PSN-7801-8911'],
                'cand' => ['first' => 'MARIA', 'mid' => 'REYES', 'last' => 'PASCUA', 'suf' => null, 'dob' => '1989-11-20', 'addr' => 'Purok 2', 'gov' => null],
                'fields' => ['middle_name_discrepancy' => 'Same first & last name with different middle name ("SANTOS" vs "REYES")'],
            ],
            [
                'score' => 75, 'type' => 'medium', 'program' => 'SPES',
                'muni' => 'Quezon', 'brgy' => 'Butong', 'sex' => 'Male',
                'master' => ['first' => 'MARK', 'mid' => 'LIM', 'last' => 'TOLENTINO', 'suf' => null, 'dob' => '2003-01-15', 'addr' => 'Purok 5', 'gov' => 'SPES-7501-0301'],
                'cand' => ['first' => 'MARK', 'mid' => 'GARCIA', 'last' => 'TOLENTINO', 'suf' => null, 'dob' => '2004-07-02', 'addr' => 'Purok 6', 'gov' => null],
                'fields' => ['middle_name_discrepancy' => 'Same name with different middle name ("LIM" vs "GARCIA") and different DOB'],
            ],
            [
                'score' => 72, 'type' => 'medium', 'program' => 'DILP',
                'muni' => 'Kibawe', 'brgy' => 'West Kibawe', 'sex' => 'Female',
                'master' => ['first' => 'ELIZABETH', 'mid' => 'RAMIREZ', 'last' => 'SANTIAGO', 'suf' => null, 'dob' => '1981-02-28', 'addr' => 'Purok 3', 'gov' => 'PSN-7201-8102'],
                'cand' => ['first' => 'ELIZABETH', 'mid' => 'MORALES', 'last' => 'SANTIAGO', 'suf' => null, 'dob' => '1985-09-10', 'addr' => 'Purok 7', 'gov' => null],
                'fields' => ['middle_name_discrepancy' => 'Namesake with different middle name ("RAMIREZ" vs "MORALES")'],
            ],
            [
                'score' => 70, 'type' => 'medium', 'program' => 'GIP',
                'muni' => 'Libona', 'brgy' => 'Crossing', 'sex' => 'Male',
                'master' => ['first' => 'JOSHUA', 'mid' => 'CORTEZ', 'last' => 'ALVAREZ', 'suf' => 'III', 'dob' => '2000-12-01', 'addr' => 'Purok 2', 'gov' => 'PSN-7001-0012'],
                'cand' => ['first' => 'JOSHUA', 'mid' => 'CORTEZ', 'last' => 'ALVAREZ', 'suf' => null, 'dob' => '2002-05-19', 'addr' => 'Purok 4', 'gov' => null],
                'fields' => ['suffix_discrepancy' => 'Missing suffix ("III" vs blank) and different DOB — 70% DOLE cutoff'],
            ],
        ];

        foreach ($scenarios as $s) {
            $programId = $programs[$s['program']] ?? $programs->first();

            $m = $s['master'];
            $master = Beneficiary::create([
                'full_name' => Beneficiary::buildFullName($m['first'], $m['mid'], $m['last'], $m['suf']),
                'first_name' => $m['first'],
                'middle_name' => $m['mid'],
                'last_name' => $m['last'],
                'suffix' => $m['suf'],
                'date_of_birth' => $m['dob'],
                'sex' => $s['sex'],
                'civil_status' => 'Single',
                'government_id_type' => 'PhilSys ID',
                'government_id_number' => $m['gov'],
                'contact_number' => '0917'.random_int(1000000, 9999999),
                'address' => $m['addr'],
                'barangay' => $s['brgy'],
                'municipality' => $s['muni'],
                'province' => 'Bukidnon',
                'created_by' => $creatorId,
            ]);

            BeneficiaryProgram::create([
                'beneficiary_id' => $master->id,
                'program_id' => $programId,
                'availment_year' => $year,
                'status' => 'approved',
            ]);

            $c = $s['cand'];
            $candidate = Beneficiary::create([
                'full_name' => Beneficiary::buildFullName($c['first'], $c['mid'], $c['last'], $c['suf']),
                'first_name' => $c['first'],
                'middle_name' => $c['mid'],
                'last_name' => $c['last'],
                'suffix' => $c['suf'],
                'date_of_birth' => $c['dob'],
                'sex' => $s['sex'],
                'civil_status' => 'Single',
                'government_id_type' => $c['gov'] ? 'PhilSys ID' : 'N/A',
                'government_id_number' => $c['gov'] ?? 'N/A',
                'contact_number' => '0918'.random_int(1000000, 9999999),
                'address' => $c['addr'],
                'barangay' => $s['brgy'],
                'municipality' => $s['muni'],
                'province' => 'Bukidnon',
                'created_by' => $creatorId,
            ]);

            BeneficiaryProgram::create([
                'beneficiary_id' => $candidate->id,
                'program_id' => $programId,
                'availment_year' => $year,
                'status' => 'pending',
            ]);

            DuplicateFlag::create([
                'beneficiary_id' => $candidate->id,
                'matched_beneficiary_id' => $master->id,
                'match_score' => $s['score'],
                'match_type' => $s['type'],
                'household_match_flag' => false,
                'matched_fields' => $s['fields'],
                'status' => 'pending',
            ]);
        }
    }
}
