<?php

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\DilpGroup;
use App\Models\Program;
use App\Models\User;
use App\Services\EligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@dole-bukidnon.gov.ph')->first();
    $this->actingAs($this->admin);
    $this->eligibilityService = app(EligibilityService::class);
});

// TASK 1: Global Government Employee Block
test('government employees are blocked across ALL programs (TUPAD, SPES, DILP, GIP)', function (string $programCode) {
    $payload = [
        'first_name' => 'JUAN',
        'last_name' => 'CRUZ',
        'date_of_birth' => '1995-05-15',
        'program_code' => $programCode,
        'availment_year' => (int) date('Y'),
        'is_government_employee' => true,
        'is_student' => true, // satisfy SPES student requirement
    ];

    $errors = $this->eligibilityService->validateEligibility($payload);

    expect($errors)->toHaveKey('is_government_employee');
    expect($errors['is_government_employee'])->toContain('Government employees are not eligible for DOLE assistance programs');
})->with(['TUPAD', 'SPES', 'DILP', 'GIP']);

// TASK 2: Calamity Override Feature
test('annual limit blocks re-application without calamity override but allows it with calamity override', function () {
    $beneficiary = Beneficiary::create([
        'full_name' => 'EMERGENCY BENEFICIARY TEST',
        'first_name' => 'EMERGENCY',
        'last_name' => 'BENEFICIARY',
        'date_of_birth' => '1992-06-10',
        'sex' => 'Female',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
    ]);

    $tupad = Program::where('code', 'TUPAD')->first();
    $currentYear = (int) date('Y');

    // First availment this year
    $beneficiary->beneficiaryPrograms()->create([
        'program_id' => $tupad->id,
        'availment_year' => $currentYear,
        'status' => 'approved',
    ]);

    // Attempt re-application without calamity override -> must fail
    $standardPayload = [
        'first_name' => 'EMERGENCY',
        'last_name' => 'BENEFICIARY',
        'date_of_birth' => '1992-06-10',
        'program_code' => 'TUPAD',
        'availment_year' => $currentYear,
        'is_calamity_override' => false,
    ];

    $errors = $this->eligibilityService->validateEligibility($standardPayload, $beneficiary);
    expect($errors)->toHaveKey('availment_year');
    expect($errors['availment_year'])->toContain('restrict availment to once per year');

    // Attempt re-application WITH calamity override -> must pass and log audit
    $calamityPayload = [
        'first_name' => 'EMERGENCY',
        'last_name' => 'BENEFICIARY',
        'date_of_birth' => '1992-06-10',
        'program_code' => 'TUPAD',
        'availment_year' => $currentYear,
        'is_calamity_override' => true,
        'calamity_remarks' => 'Typhoon Relief Operation 2026',
    ];

    $calamityErrors = $this->eligibilityService->validateEligibility($calamityPayload, $beneficiary);
    expect($calamityErrors)->not->toHaveKey('availment_year');

    // Verify AuditLog record
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'calamity_override',
        'model_type' => Beneficiary::class,
        'model_id' => $beneficiary->id,
    ]);
});

// TASK 3: Strict SPES & GIP Duration Rules
test('SPES requires student status or out-of-school youth status', function () {
    // Non-student, non-OSY -> reject
    $payloadNonStudent = [
        'first_name' => 'SPES',
        'last_name' => 'CANDIDATE',
        'date_of_birth' => '2005-01-01',
        'program_code' => 'SPES',
        'availment_year' => (int) date('Y'),
        'is_student' => false,
        'is_out_of_school_youth' => false,
    ];

    $errors = $this->eligibilityService->validateEligibility($payloadNonStudent);
    expect($errors)->toHaveKey('is_student');
    expect($errors['is_student'])->toContain('SPES applicants must be currently enrolled students, registered out-of-school youth, or graduating students');

    // Student -> passes
    $payloadStudent = array_merge($payloadNonStudent, ['is_student' => true]);
    $errorsStudent = $this->eligibilityService->validateEligibility($payloadStudent);
    expect($errorsStudent)->not->toHaveKey('is_student');

    // Out of School Youth -> passes
    $payloadOsy = array_merge($payloadNonStudent, ['is_out_of_school_youth' => true]);
    $errorsOsy = $this->eligibilityService->validateEligibility($payloadOsy);
    expect($errorsOsy)->not->toHaveKey('is_student');
});

test('GIP enforces education duration caps for High School vs College graduates', function () {
    // High School graduate attempting 1-year (12 months) -> reject (cap is 6 months)
    $payloadHsLong = [
        'first_name' => 'INTERN',
        'last_name' => 'HS',
        'date_of_birth' => '2003-05-10',
        'program_code' => 'GIP',
        'availment_year' => (int) date('Y'),
        'educational_attainment' => 'High School Graduate',
        'internship_duration' => '1_year',
    ];

    $errorsHsLong = $this->eligibilityService->validateEligibility($payloadHsLong);
    expect($errorsHsLong)->toHaveKey('internship_duration');
    expect($errorsHsLong['internship_duration'])->toContain('High School graduates are capped at a maximum of 6 months');

    // High School graduate with 6 months -> passes
    $payloadHsValid = array_merge($payloadHsLong, ['internship_duration' => '6_months']);
    $errorsHsValid = $this->eligibilityService->validateEligibility($payloadHsValid);
    expect($errorsHsValid)->not->toHaveKey('internship_duration');

    // College graduate with 1 year (12 months) -> passes
    $payloadCollegeValid = [
        'first_name' => 'INTERN',
        'last_name' => 'COLLEGE',
        'date_of_birth' => '2001-08-15',
        'program_code' => 'GIP',
        'availment_year' => (int) date('Y'),
        'educational_attainment' => 'College Graduate (BS IT)',
        'internship_duration' => '1_year',
    ];
    $errorsCollegeValid = $this->eligibilityService->validateEligibility($payloadCollegeValid);
    expect($errorsCollegeValid)->not->toHaveKey('internship_duration');

    // College graduate attempting > 12 months (e.g. 18 months) -> reject
    $payloadCollegeExcess = array_merge($payloadCollegeValid, [
        'internship_duration_months' => 18,
    ]);
    $errorsCollegeExcess = $this->eligibilityService->validateEligibility($payloadCollegeExcess);
    expect($errorsCollegeExcess)->toHaveKey('internship_duration');
    expect($errorsCollegeExcess['internship_duration'])->toContain('cannot exceed 12 months');
});

// TASK 4: DILP Co-Partner Member Batch Upload
test('DILP group member batch upload processes CSV files and creates member records', function () {
    $group = DilpGroup::create([
        'group_name' => 'Bukidnon Agrarian Reform Beneficiaries Coop',
        'co_partner_name' => 'DAR Bukidnon',
        'co_partner_contact' => '09171239999',
    ]);

    $csvContent = "member_name,contact_no,designation\n";
    $csvContent .= "Pedro Penduko,09171110001,Chairman\n";
    $csvContent .= "Maria Makiling,09171110002,Treasurer\n";
    $csvContent .= "Bernardo Carpio,09171110003,Auditor\n";

    $file = UploadedFile::fake()->createWithContent('members_roster.csv', $csvContent);

    $response = $this->post(route('dilp.groups.import-members', $group), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('dilp.groups.show', $group));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('dilp_group_members', [
        'dilp_group_id' => $group->id,
        'member_name' => 'Pedro Penduko',
        'contact_no' => '09171110001',
        'designation' => 'Chairman',
    ]);

    $this->assertDatabaseHas('dilp_group_members', [
        'dilp_group_id' => $group->id,
        'member_name' => 'Maria Makiling',
        'contact_no' => '09171110002',
        'designation' => 'Treasurer',
    ]);

    $this->assertDatabaseHas('dilp_group_members', [
        'dilp_group_id' => $group->id,
        'member_name' => 'Bernardo Carpio',
    ]);

    expect($group->members()->count())->toBe(3);

    // Verify audit trail
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'import',
        'model_type' => DilpGroup::class,
        'model_id' => $group->id,
    ]);

    // Verify show view loads members
    $showResponse = $this->get(route('dilp.groups.show', $group));
    $showResponse->assertOk();
    $showResponse->assertSee('Pedro Penduko');
    $showResponse->assertSee('Chairman');
});
