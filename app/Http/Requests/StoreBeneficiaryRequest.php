<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Encoder']);
    }

    public function rules(): array
    {
        return [
            // Core Beneficiary Fields
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'sex' => ['required', 'in:Male,Female'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'government_id_type' => ['nullable', 'string', 'max:50'],
            'government_id_number' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'address' => ['nullable', 'string'],
            'barangay' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'],
            'is_senior_citizen' => ['nullable', 'boolean'],
            'is_pwd' => ['nullable', 'boolean'],
            'is_student' => ['nullable', 'boolean'],
            'is_government_employee' => ['nullable', 'boolean'],
            'is_graduating_college' => ['nullable', 'boolean'],

            // Program Selection
            'program_code' => ['required', 'string', 'in:TUPAD,SPES,DILP,GIP'],
            'availment_year' => ['required', 'integer', 'min:2020', 'max:'.(date('Y') + 1)],

            // Program specific fields
            'enrollment_type' => ['nullable', 'required_if:program_code,DILP', 'in:individual,group'],
            'dilp_group_id' => ['nullable', 'required_if:enrollment_type,group', 'exists:dilp_groups,id'],
            'internship_duration' => ['nullable', 'required_if:program_code,GIP', 'in:6_months,1_year'],

            // TUPAD Annex D Profile fields
            'project_location_barangay' => ['nullable', 'string', 'max:100'],
            'project_location_municipality' => ['nullable', 'string', 'max:100'],
            'project_location_province' => ['nullable', 'string', 'max:100'],
            'project_location_district' => ['nullable', 'string', 'max:20'],
            'epayment_account_no' => ['nullable', 'string', 'max:100'],
            'beneficiary_type' => ['nullable', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'average_monthly_income' => ['nullable', 'string', 'max:50'],
            'dependent_name' => ['nullable', 'string', 'max:150'],
            'dependent_relationship' => ['nullable', 'string', 'max:50'],
            'interested_in_employment' => ['nullable', 'boolean'],
            'employment_interest_detail' => ['nullable', 'string', 'max:150'],
            'skills_training_needed' => ['nullable', 'string', 'max:255'],

            // Override flag parameters
            'confirm_override' => ['nullable', 'boolean'],
            'override_remarks' => ['nullable', 'required_if:confirm_override,1', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_number.regex' => 'Contact number must be a valid PH mobile number (e.g., 09171234567 or +639171234567).',
            'date_of_birth.before' => 'Date of birth cannot be in the future.',
            'override_remarks.required_if' => 'Remarks are required when approving/saving a flagged duplicate.',
        ];
    }
}
