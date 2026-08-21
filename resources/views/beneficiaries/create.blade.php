@extends('layouts.app')
@section('title', 'Register Beneficiary')
@section('page-title', 'Beneficiary Registration')
@section('page-subtitle', 'Single registration portal with real-time duplicate engine validation & eligibility check')

@section('content')
<div class="mx-auto max-w-4xl" x-data="beneficiaryForm()">
    <form @submit.prevent="submitRegistration($event)" action="{{ route('beneficiaries.store') }}" method="POST" id="registration-form" class="space-y-6">
        @csrf

        {{-- Hidden Override & Linking Signals --}}
        <input type="hidden" name="confirm_override" x-model="confirmOverride">
        <input type="hidden" name="override_duplicate" x-model="confirmOverride">
        <input type="hidden" name="override_remarks" x-model="overrideRemarks">
        <input type="hidden" name="existing_beneficiary_id" x-model="existingBeneficiaryId">

        {{-- Dynamic In-Page Validation Errors Banner (Zero Page Reload) --}}
        <div x-show="formErrors.length > 0" x-transition.duration.300ms class="rounded-2xl border-2 border-rose-400 bg-rose-50 p-5 shadow-lg" id="error-banner" style="display: none;">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white font-extrabold text-lg shadow-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-rose-950">Please fix the following validation errors:</h4>
                        <ul class="mt-2 space-y-1 text-xs font-bold text-rose-800">
                            <template x-for="(err, idx) in formErrors" :key="idx">
                                <li class="flex items-center gap-2">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-rose-600 shrink-0"></span>
                                    <span x-text="err"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
                <button @click="formErrors = []" type="button" class="rounded-lg p-1 text-rose-500 hover:bg-rose-100 hover:text-rose-800 text-lg font-black transition cursor-pointer">
                    &times;
                </button>
            </div>
        </div>

        {{-- Step 1: Program Selection --}}
        <div class="gov-card p-6">
            <div class="mb-5 flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-700 font-extrabold text-white text-base shadow-md">
                        1
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-blue-900 uppercase tracking-wider">Select DOLE Program</h3>
                        <p class="text-xs font-medium text-slate-600">Choose the target program for this beneficiary enrollment</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($programs as $prog)
                    <label class="group relative flex cursor-pointer flex-col rounded-2xl border-2 p-4 transition-all duration-200"
                           :class="{
                               'border-blue-700 bg-blue-50/90 shadow-md ring-2 ring-blue-500/30': selectedProgram === 'TUPAD' && '{{ $prog->code }}' === 'TUPAD',
                               'border-emerald-600 bg-emerald-50/90 shadow-md ring-2 ring-emerald-500/30': selectedProgram === 'SPES' && '{{ $prog->code }}' === 'SPES',
                               'border-amber-600 bg-amber-50/90 shadow-md ring-2 ring-amber-500/30': selectedProgram === 'DILP' && '{{ $prog->code }}' === 'DILP',
                               'border-purple-700 bg-purple-50/90 shadow-md ring-2 ring-purple-500/30': selectedProgram === 'GIP' && '{{ $prog->code }}' === 'GIP',
                               'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50': selectedProgram !== '{{ $prog->code }}'
                           }">
                        <input type="radio" name="program_code" value="{{ $prog->code }}" x-model="selectedProgram" @change="onProgramChange" class="sr-only" required>

                        <div class="flex items-center justify-between">
                            <span class="text-lg font-black tracking-tight"
                                  :class="{
                                      'text-blue-900': selectedProgram === 'TUPAD' && '{{ $prog->code }}' === 'TUPAD',
                                      'text-emerald-900': selectedProgram === 'SPES' && '{{ $prog->code }}' === 'SPES',
                                      'text-amber-900': selectedProgram === 'DILP' && '{{ $prog->code }}' === 'DILP',
                                      'text-purple-900': selectedProgram === 'GIP' && '{{ $prog->code }}' === 'GIP',
                                      'text-slate-800': selectedProgram !== '{{ $prog->code }}'
                                  }">
                                {{ $prog->code }}
                            </span>

                            <div class="h-6 w-6 rounded-full border-2 flex items-center justify-center transition-all duration-200"
                                 :class="{
                                     'border-blue-700 bg-blue-700 text-white shadow-xs': selectedProgram === 'TUPAD' && '{{ $prog->code }}' === 'TUPAD',
                                     'border-emerald-600 bg-emerald-600 text-white shadow-xs': selectedProgram === 'SPES' && '{{ $prog->code }}' === 'SPES',
                                     'border-amber-600 bg-amber-600 text-white shadow-xs': selectedProgram === 'DILP' && '{{ $prog->code }}' === 'DILP',
                                     'border-purple-700 bg-purple-700 text-white shadow-xs': selectedProgram === 'GIP' && '{{ $prog->code }}' === 'GIP',
                                     'border-slate-300 bg-white': selectedProgram !== '{{ $prog->code }}'
                                 }">
                                <template x-if="selectedProgram === '{{ $prog->code }}'">
                                    <svg class="h-4 w-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </template>
                            </div>
                        </div>
                        <span class="mt-2 text-xs font-semibold leading-relaxed"
                              :class="selectedProgram === '{{ $prog->code }}' ? 'text-slate-800 font-bold' : 'text-slate-500'">
                            {{ $prog->name }}
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-800">Availment Year *</label>
                    <input type="number" name="availment_year" value="{{ date('Y') }}" required
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-extrabold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
            </div>

            @hasrole('Admin')
            <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50/70 p-4">
                <div class="flex items-center gap-3">
                    <input type="checkbox" id="is_calamity_override" name="is_calamity_override" value="1" x-model="isCalamityOverride" class="h-4 w-4 rounded border-rose-300 text-rose-700 focus:ring-rose-500">
                    <label for="is_calamity_override" class="cursor-pointer text-xs font-black uppercase tracking-wider text-rose-900">
                        Calamity / Natural Disaster Emergency Override (Admin)
                    </label>
                </div>
                <p class="mt-1 text-xs text-rose-700 font-medium">Bypasses once-a-year program limits for re-applications during officially declared states of calamity or emergency.</p>
                <div x-show="isCalamityOverride" class="mt-3">
                    <label class="mb-1 block text-xs font-bold text-rose-900">Calamity Override Remarks / Resolution No. *</label>
                    <input type="text" name="calamity_remarks" x-model="calamityRemarks" :required="isCalamityOverride" placeholder="e.g. State of Calamity Resolution No. 2026-XX / Typhoone Emergency Relief"
                           class="w-full rounded-xl border border-rose-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-900 focus:border-rose-700 focus:outline-none">
                </div>
            </div>
            @endhasrole
        </div>

        {{-- Step 2: Personal Information --}}
        <div class="gov-card p-6">
            <div class="mb-5 flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-700 font-extrabold text-white text-base shadow-md">
                        2
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-indigo-900 uppercase tracking-wider">Personal Identity</h3>
                        <p class="text-xs font-medium text-slate-600">Full legal name and basic demographic details</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">First Name *</label>
                    <input type="text" name="first_name" x-model="firstName" required placeholder="First Name"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Middle Name</label>
                    <input type="text" name="middle_name" x-model="middleName" placeholder="Middle Name"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Last Name *</label>
                    <input type="text" name="last_name" x-model="lastName" required placeholder="Last Name"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Suffix</label>
                    <input type="text" name="suffix" placeholder="Jr., Sr., III"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Date of Birth *</label>
                    <input type="date" name="date_of_birth" x-model="dob" @change="calculateAge" required
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                    <span x-show="calculatedAge !== null" class="mt-1 block text-xs font-bold"
                          :class="calculatedAge >= 18 ? 'text-emerald-700' : 'text-amber-700'">
                        Age: <span x-text="calculatedAge"></span> years old
                    </span>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Sex *</label>
                    <select name="sex" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Civil Status</label>
                    <select name="civil_status" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                    </select>
                </div>
            </div>

            {{-- Auto Highlights --}}
            <div class="mt-4 flex flex-wrap gap-2">
                <span x-show="calculatedAge >= 60" x-transition class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-extrabold text-amber-900 border border-amber-300 shadow-2xs">
                    Senior Citizen Highlight (60+)
                </span>
                <span x-show="selectedProgram === 'SPES' && isGraduating" x-transition class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-extrabold text-emerald-900 border border-emerald-300 shadow-2xs">
                    Graduating College Student Highlight
                </span>
            </div>
        </div>

        {{-- Step 3: Address & Contact Details --}}
        <div class="gov-card p-6">
            <div class="mb-5 flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-700 font-extrabold text-white text-base shadow-md">
                        3
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-teal-900 uppercase tracking-wider">Address & Contact</h3>
                        <p class="text-xs font-medium text-slate-600">Bukidnon location dropdowns, phone number, and government IDs</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Municipality (Bukidnon) *</label>
                    <select name="municipality" x-model="municipality" @change="onMunicipalityChange" required
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Municipality</option>
                        @foreach($municipalities as $muni)
                            <option value="{{ $muni }}">{{ $muni }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Barangay *</label>
                    <select x-model="selectedBarangay" @change="onBarangaySelect" required
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Barangay</option>
                        <template x-for="brgy in availableBarangays" :key="brgy">
                            <option :value="brgy" x-text="brgy"></option>
                        </template>
                        <option value="__OTHER__">+ Other / Custom Barangay...</option>
                    </select>

                    <input type="hidden" name="barangay" :value="customBarangayMode ? customBarangayInput : selectedBarangay">

                    {{-- Manual Barangay Input if Other selected --}}
                    <div x-show="customBarangayMode" class="mt-2">
                        <input type="text" x-model="customBarangayInput" placeholder="Enter custom barangay name..."
                               class="w-full rounded-xl border border-blue-400 bg-blue-50/50 px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Purok / Sitio / Address *</label>
                    <select x-model="selectedPurok" @change="onPurokSelect" required
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Purok / Sitio</option>
                        <option value="Purok 1">Purok 1</option>
                        <option value="Purok 2">Purok 2</option>
                        <option value="Purok 3">Purok 3</option>
                        <option value="Purok 4">Purok 4</option>
                        <option value="Purok 5">Purok 5</option>
                        <option value="Purok 6">Purok 6</option>
                        <option value="Purok 7">Purok 7</option>
                        <option value="Purok 8">Purok 8</option>
                        <option value="Purok 9">Purok 9</option>
                        <option value="Purok 10">Purok 10</option>
                        <option value="Purok 11">Purok 11</option>
                        <option value="Purok 12">Purok 12</option>
                        <option value="Purok 1A">Purok 1A</option>
                        <option value="Purok 1B">Purok 1B</option>
                        <option value="Purok 2A">Purok 2A</option>
                        <option value="Purok 2B">Purok 2B</option>
                        <option value="Purok 3A">Purok 3A</option>
                        <option value="Purok 3B">Purok 3B</option>
                        <option value="Purok Centro">Purok Centro / Poblacion</option>
                        <option value="Zone 1">Zone 1</option>
                        <option value="Zone 2">Zone 2</option>
                        <option value="Zone 3">Zone 3</option>
                        <option value="Zone 4">Zone 4</option>
                        <option value="Zone 5">Zone 5</option>
                        <option value="Zone 6">Zone 6</option>
                        <option value="__OTHER__">+ Custom Sitio / Street Address...</option>
                    </select>

                    <input type="hidden" name="address" :value="customAddressMode ? customAddressInput : selectedPurok">

                    <div x-show="customAddressMode" class="mt-2">
                        <input type="text" x-model="customAddressInput" placeholder="Enter custom Sitio name or House No..."
                               class="w-full rounded-xl border border-blue-400 bg-blue-50/50 px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Contact Number (PH format)</label>
                    <input type="text" name="contact_number" x-model="contactNumber" @input="validateContact" placeholder="09171234567"
                           class="w-full rounded-xl border px-4 py-2.5 text-sm font-semibold text-slate-900 transition-all duration-200 focus:outline-none"
                           :class="contactError ? 'border-rose-500 bg-rose-50/40 text-rose-900 focus:border-rose-600 focus:ring-2 focus:ring-rose-200' : 'border-slate-300 focus:border-blue-700 focus:ring-2 focus:ring-blue-100'">
                    <p x-show="contactError" x-text="contactError" class="mt-1.5 text-xs font-bold text-rose-600 flex items-center gap-1" style="display: none;">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span x-text="contactError"></span>
                    </p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Government ID Type</label>
                    <select name="government_id_type" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Government ID Type</option>
                        <option value="PhilSys ID" {{ old('government_id_type') == 'PhilSys ID' ? 'selected' : '' }}>Philippine National ID (PhilSys)</option>
                        <option value="UMID" {{ old('government_id_type') == 'UMID' ? 'selected' : '' }}>Unified Multi-Purpose ID (UMID)</option>
                        <option value="SSS ID" {{ old('government_id_type') == 'SSS ID' ? 'selected' : '' }}>Social Security System (SSS) ID</option>
                        <option value="GSIS ID" {{ old('government_id_type') == 'GSIS ID' ? 'selected' : '' }}>GSIS eCard / ID</option>
                        <option value="TIN Card" {{ old('government_id_type') == 'TIN Card' ? 'selected' : '' }}>Tax Identification Number (TIN) Card</option>
                        <option value="Pag-IBIG ID" {{ old('government_id_type') == 'Pag-IBIG ID' ? 'selected' : '' }}>Pag-IBIG (HDMF) ID / Loyalty Card</option>
                        <option value="PhilHealth ID" {{ old('government_id_type') == 'PhilHealth ID' ? 'selected' : '' }}>PhilHealth Healthpass / ID</option>
                        <option value="Voter's ID" {{ old('government_id_type') == "Voter's ID" ? 'selected' : '' }}>Voter's ID / Voter Certification</option>
                        <option value="Driver's License" {{ old('government_id_type') == "Driver's License" ? 'selected' : '' }}>Driver's License (LTO)</option>
                        <option value="Passport" {{ old('government_id_type') == 'Passport' ? 'selected' : '' }}>Philippine Passport (DFA)</option>
                        <option value="Senior Citizen ID" {{ old('government_id_type') == 'Senior Citizen ID' ? 'selected' : '' }}>Senior Citizen ID</option>
                        <option value="PWD ID" {{ old('government_id_type') == 'PWD ID' ? 'selected' : '' }}>Person with Disability (PWD) ID</option>
                        <option value="Postal ID" {{ old('government_id_type') == 'Postal ID' ? 'selected' : '' }}>Postal ID</option>
                        <option value="Barangay ID" {{ old('government_id_type') == 'Barangay ID' ? 'selected' : '' }}>Barangay ID / Barangay Clearance</option>
                        <option value="PRC ID" {{ old('government_id_type') == 'PRC ID' ? 'selected' : '' }}>Professional Regulation Commission (PRC) ID</option>
                        <option value="OWWA ID" {{ old('government_id_type') == 'OWWA ID' ? 'selected' : '' }}>OWWA / E-Card ID</option>
                        <option value="Solo Parent ID" {{ old('government_id_type') == 'Solo Parent ID' ? 'selected' : '' }}>Solo Parent ID</option>
                        <option value="NBI Clearance" {{ old('government_id_type') == 'NBI Clearance' ? 'selected' : '' }}>NBI Clearance</option>
                        <option value="Police Clearance" {{ old('government_id_type') == 'Police Clearance' ? 'selected' : '' }}>Police Clearance</option>
                        <option value="Student ID" {{ old('government_id_type') == 'Student ID' ? 'selected' : '' }}>Student / School ID</option>
                        <option value="Other ID" {{ old('government_id_type') == 'Other ID' ? 'selected' : '' }}>Other Government Valid ID</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Government ID Number</label>
                    <input type="text" name="government_id_number" x-model="govIdNumber" placeholder="ID Number"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-6 border-t border-slate-200 pt-4">
                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
                    <input type="checkbox" name="is_pwd" value="1" class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                    Person with Disability (PWD)
                </label>
                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
                    <input type="checkbox" name="is_student" value="1" x-model="isStudent" class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                    Currently Enrolled Student
                </label>
                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
                    <input type="checkbox" name="is_government_employee" value="1" x-model="isGovEmp" class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                    Government Employee
                </label>
                <label x-show="selectedProgram === 'SPES'" class="flex items-center gap-2.5 text-xs font-bold text-emerald-900 cursor-pointer bg-emerald-50 px-2 py-1 rounded border border-emerald-200">
                    <input type="checkbox" name="is_graduating_student" value="1" x-model="isGraduating" @change="if(isGraduating) { isStudent = true; }" class="rounded border-emerald-500 text-emerald-600 focus:ring-emerald-500">
                    Graduating College Student
                </label>
            </div>
        </div>

        {{-- Step 4: Program Sub-Sections --}}
        {{-- TUPAD Sub-section --}}
        <div x-show="selectedProgram === 'TUPAD'"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="gov-card border-l-8 border-l-blue-700 p-6">
            <h3 class="mb-4 text-sm font-extrabold text-blue-900 uppercase tracking-wider flex items-center gap-2">
                <span class="rounded bg-blue-100 px-2 py-0.5 text-blue-800">TUPAD</span>
                Annex D Additional Details
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">E-Payment Account No.</label>
                    <input type="text" name="epayment_account_no" placeholder="GCash / Maya / Card" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">Beneficiary Sector/Type</label>
                    <select name="beneficiary_type" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Sector/Type</option>
                        <option value="Underemployed" {{ old('beneficiary_type') == 'Underemployed' ? 'selected' : '' }}>Underemployed Worker</option>
                        <option value="Unemployed (Displaced)" {{ old('beneficiary_type') == 'Unemployed (Displaced)' ? 'selected' : '' }}>Unemployed / Displaced Worker</option>
                        <option value="Informal Sector Worker" {{ old('beneficiary_type') == 'Informal Sector Worker' ? 'selected' : '' }}>Informal Sector Worker</option>
                        <option value="Indigenous People (IP)" {{ old('beneficiary_type') == 'Indigenous People (IP)' ? 'selected' : '' }}>Indigenous People (IP)</option>
                        <option value="Senior Citizen" {{ old('beneficiary_type') == 'Senior Citizen' ? 'selected' : '' }}>Senior Citizen (60+)</option>
                        <option value="Person with Disability (PWD)" {{ old('beneficiary_type') == 'Person with Disability (PWD)' ? 'selected' : '' }}>Person with Disability (PWD)</option>
                        <option value="Youth / Student" {{ old('beneficiary_type') == 'Youth / Student' ? 'selected' : '' }}>Youth / Student</option>
                        <option value="Solo Parent" {{ old('beneficiary_type') == 'Solo Parent' ? 'selected' : '' }}>Solo Parent</option>
                        <option value="Farmer / Fisherfolk" {{ old('beneficiary_type') == 'Farmer / Fisherfolk' ? 'selected' : '' }}>Farmer / Fisherfolk</option>
                        <option value="OFW Returnee" {{ old('beneficiary_type') == 'OFW Returnee' ? 'selected' : '' }}>OFW Returnee</option>
                        <option value="Victim of Calamity / Disaster" {{ old('beneficiary_type') == 'Victim of Calamity / Disaster' ? 'selected' : '' }}>Victim of Calamity / Disaster</option>
                        <option value="Other Sector" {{ old('beneficiary_type') == 'Other Sector' ? 'selected' : '' }}>Other Sector</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">Average Monthly Income</label>
                    <select name="average_monthly_income" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Monthly Income Bracket</option>
                        <option value="No Regular Income" {{ old('average_monthly_income') == 'No Regular Income' ? 'selected' : '' }}>No Regular Income / Sub-poverty</option>
                        <option value="Below ₱5,000" {{ old('average_monthly_income') == 'Below ₱5,000' ? 'selected' : '' }}>Below ₱5,000</option>
                        <option value="₱5,000 - ₱10,000" {{ old('average_monthly_income') == '₱5,000 - ₱10,000' ? 'selected' : '' }}>₱5,000 - ₱10,000</option>
                        <option value="₱10,001 - ₱15,000" {{ old('average_monthly_income') == '₱10,001 - ₱15,000' ? 'selected' : '' }}>₱10,001 - ₱15,000</option>
                        <option value="₱15,001 - ₱20,000" {{ old('average_monthly_income') == '₱15,001 - ₱20,000' ? 'selected' : '' }}>₱15,001 - ₱20,000</option>
                        <option value="Above ₱20,000" {{ old('average_monthly_income') == 'Above ₱20,000' ? 'selected' : '' }}>Above ₱20,000</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- SPES Sub-section --}}
        <div x-show="selectedProgram === 'SPES'"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="gov-card border-l-8 border-l-emerald-600 p-6">
            <h3 class="mb-4 text-sm font-extrabold text-emerald-900 uppercase tracking-wider flex items-center gap-2">
                <span class="rounded bg-emerald-100 px-2 py-0.5 text-emerald-800">SPES</span>
                Special Program for Employment of Students
            </h3>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-medium text-emerald-900">
                <p class="font-bold mb-1">SPES Eligibility Guidelines:</p>
                <p>Applicant must be between 15 and 30 years old. Students are automatically flagged as enrolled students.</p>
            </div>
        </div>

        {{-- DILP Sub-section --}}
        <div x-show="selectedProgram === 'DILP'"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="gov-card border-l-8 border-l-amber-600 p-6">
            <h3 class="mb-4 text-sm font-extrabold text-amber-900 uppercase tracking-wider flex items-center gap-2">
                <span class="rounded bg-amber-100 px-2 py-0.5 text-amber-800">DILP</span>
                Livelihood Program Details
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">Enrollment Type *</label>
                    <select name="enrollment_type" x-model="dilpType" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="individual">Individual Beneficiary</option>
                        <option value="group">Group / Cooperative</option>
                    </select>
                </div>
                <div x-show="dilpType === 'group'">
                    <label class="mb-1 block text-xs font-bold text-slate-800">DILP Group *</label>
                    <select name="dilp_group_id" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select DILP Group</option>
                        @foreach($dilpGroups as $grp)
                            <option value="{{ $grp->id }}">{{ $grp->group_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- GIP Sub-section --}}
        <div x-show="selectedProgram === 'GIP'"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="gov-card border-l-8 border-l-purple-700 p-6">
            <h3 class="mb-4 text-sm font-extrabold text-purple-900 uppercase tracking-wider flex items-center gap-2">
                <span class="rounded bg-purple-100 px-2 py-0.5 text-purple-800">GIP</span>
                Government Internship Program Details
            </h3>
            <div>
                <label class="mb-1 block text-xs font-bold text-slate-800">Internship Duration *</label>
                <select name="internship_duration" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                    <option value="6_months">6 Months (High School / Technical)</option>
                    <option value="1_year">1 Year (College Graduate)</option>
                </select>
            </div>
        </div>

        {{-- Action Bar --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('beneficiaries.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-100">
                Cancel
            </a>
            <button type="submit" :disabled="checking || submitting"
                    class="flex items-center gap-2 rounded-xl bg-blue-700 hover:bg-blue-800 px-6 py-3 text-sm font-extrabold text-white shadow-md transition disabled:opacity-50 cursor-pointer">
                <span x-show="!checking && !submitting">+ Register Beneficiary</span>
                <span x-show="checking" class="flex items-center gap-2" style="display: none;">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Evaluating Duplicates...
                </span>
                <span x-show="submitting" class="flex items-center gap-2" style="display: none;">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Saving Beneficiary...
                </span>
            </button>
        </div>
    </form>

    {{-- Duplicate Flag Modal --}}
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 sm:p-6 backdrop-blur-md overflow-hidden" style="display: none;">
        
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="w-full max-w-2xl rounded-2xl border bg-white shadow-2xl flex flex-col max-h-[88vh] overflow-hidden"
             :class="isReturningBeneficiary ? 'border-blue-300' : 'border-amber-300'">
            
            {{-- Modal Header (Fixed at top) --}}
            <div class="flex items-center justify-between border-b border-slate-200 p-4 sm:p-5 bg-white shrink-0">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl font-black text-white text-lg shadow-md"
                         :class="isReturningBeneficiary ? 'bg-blue-700' : ((isSameYearConflict || isHouseholdLimit || isEligibilityConflict) ? 'bg-rose-600' : 'bg-amber-500')">
                        <template x-if="isReturningBeneficiary">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </template>
                        <template x-if="!isReturningBeneficiary && (isSameYearConflict || isHouseholdLimit || isEligibilityConflict)">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </template>
                        <template x-if="!isReturningBeneficiary && !isSameYearConflict && !isHouseholdLimit && !isEligibilityConflict">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        </template>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900" x-text="isReturningBeneficiary ? 'Existing Master Profile Found' : (isHouseholdLimit ? 'Household Limit Warning' : (isSameYearConflict ? 'Annual Limit / Duplicate Availment Detected' : (isEligibilityConflict ? 'Eligibility Restriction Detected' : 'Potential Duplicate Detected!')))"></h3>
                        <p class="text-xs font-semibold text-slate-600 line-clamp-1">
                            <template x-if="isReturningBeneficiary">
                                <span>Beneficiary record already exists. You can link this new availment.</span>
                            </template>
                            <template x-if="!isReturningBeneficiary && isHouseholdLimit">
                                <span>Another resident at this exact address and barangay has already availed.</span>
                            </template>
                            <template x-if="!isReturningBeneficiary && isSameYearConflict && !isHouseholdLimit">
                                <span>Beneficiary has already availed of this program for the selected calendar year.</span>
                            </template>
                            <template x-if="!isReturningBeneficiary && isEligibilityConflict && !isSameYearConflict && !isHouseholdLimit">
                                <span>The entered applicant details violate DOLE program eligibility guidelines.</span>
                            </template>
                            <template x-if="!isReturningBeneficiary && !isSameYearConflict && !isHouseholdLimit && !isEligibilityConflict">
                                <span>Potential duplicate record flagged. Please verify identity details.</span>
                            </template>
                        </p>
                    </div>
                </div>
                <button @click="showModal = false" type="button" class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer" title="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body (Scrollable) --}}
            <div class="overflow-y-auto p-4 sm:p-5 space-y-3.5 flex-1 min-h-0">
                {{-- Returning Beneficiary Banner --}}
                <template x-if="isReturningBeneficiary">
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-2xs">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-white font-extrabold shadow-sm">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-blue-950 uppercase tracking-wide">
                                    Existing Profile Found (<span class="text-blue-700" x-text="'Last Availed: ' + (lastAvailedText || 'Previous Year')"></span>)
                                </h4>
                                <p class="mt-0.5 text-xs font-medium text-blue-800">
                                    This returning beneficiary is eligible to re-apply for <span class="font-extrabold" x-text="targetAvailmentYear"></span>. Attach this availment directly to their master profile.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Household Limit Warning Banner --}}
                <template x-if="isHouseholdLimit && !isReturningBeneficiary">
                    <div class="rounded-xl border border-rose-300 bg-rose-50 p-4 shadow-2xs">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white font-extrabold text-base shadow-sm">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-rose-950 uppercase tracking-wide">
                                    Household Limit Warning
                                </h4>
                                <p class="mt-1 text-xs font-semibold text-rose-900 leading-relaxed">
                                    A relative or resident with the same physical address in this Barangay is already enrolled in this TUPAD cycle. Under DOLE guidelines, TUPAD assistance is limited to 1 worker per household per cycle.
                                </p>
                                <p class="mt-1 text-xs font-medium text-rose-800">
                                    If this applicant represents a separate, independent family unit living in the same compound, an Admin or Validator may verify and approve with remarks below.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Same Year Conflict Banner --}}
                <template x-if="isSameYearConflict && !isReturningBeneficiary && !isHouseholdLimit">
                    <div class="rounded-xl border border-rose-300 bg-rose-50 p-4 shadow-2xs">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white font-extrabold text-base shadow-sm">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-rose-950 uppercase tracking-wide">
                                    Annual Limit: Already Availed for <span x-text="targetAvailmentYear"></span>
                                </h4>
                                <p class="mt-1 text-xs font-semibold text-rose-900 leading-relaxed">
                                    This beneficiary has already availed of <span class="font-extrabold" x-text="selectedProgram"></span> for calendar year <span class="font-extrabold" x-text="targetAvailmentYear"></span>. Under standard DOLE guidelines, assistance is limited to once per calendar year unless applying under an active Calamity/Emergency Override.
                                </p>
                                <div class="mt-3 flex items-center gap-2">
                                    <input type="checkbox" id="modal_calamity_check" x-model="isCalamityOverride" class="h-4 w-4 rounded text-blue-700 focus:ring-blue-500">
                                    <label for="modal_calamity_check" class="text-xs font-extrabold text-rose-950 cursor-pointer">
                                        Apply Calamity / Disaster Emergency Override
                                    </label>
                                </div>
                                <div x-show="isCalamityOverride" class="mt-2">
                                    <input type="text" x-model="calamityRemarks" placeholder="Enter Calamity / Disaster declaration remarks (e.g. Typhoon / Flood relief)..."
                                           class="w-full rounded-xl border border-rose-300 bg-white p-2.5 text-xs font-bold text-slate-900 focus:outline-none">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Eligibility Rule Restriction Banner --}}
                <template x-if="isEligibilityConflict && !isReturningBeneficiary && !isHouseholdLimit && !isSameYearConflict">
                    <div class="rounded-xl border border-rose-300 bg-rose-50 p-4 shadow-2xs">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white font-extrabold text-base shadow-sm">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-rose-950 uppercase tracking-wide">
                                    Program Eligibility Restriction
                                </h4>
                                <p class="mt-1 text-xs font-semibold text-rose-900 leading-relaxed">
                                    This registration does not meet DOLE program statutory guidelines:
                                </p>
                                <ul class="mt-2 space-y-1 text-xs font-bold text-rose-800">
                                    <template x-for="(err, idx) in eligibilityErrorsList" :key="idx">
                                        <li class="flex items-center gap-2">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-rose-600 shrink-0"></span>
                                            <span x-text="err"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Same Name Record Detected Banner --}}
                <template x-if="isSameNameDiffIdentity && !isReturningBeneficiary && !isSameYearConflict && !isHouseholdLimit">
                    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 shadow-2xs">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-600 text-white font-extrabold text-base shadow-sm">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-amber-950 uppercase tracking-wide">
                                    Same Name Record Detected
                                </h4>
                                <p class="mt-1 text-xs font-semibold text-amber-900 leading-relaxed">
                                    An existing beneficiary named "<strong class="font-black text-amber-950" x-text="existingBeneficiaryName || (duplicateMatches[0] ? duplicateMatches[0].matched_beneficiary_name : '')"></strong>" already exists in the system (DOB: <span class="font-mono font-bold text-amber-950" x-text="(existingDob || (duplicateMatches[0] ? duplicateMatches[0].existing_dob : 'N/A')).split('T')[0]"></span> vs Entered: <span class="font-mono font-bold text-amber-950" x-text="(inputDob || dob || 'N/A').split('T')[0]"></span>). Please verify if this is an encoding typo or two distinct individuals.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="space-y-3">
                    <template x-for="match in duplicateMatches" :key="match.matched_beneficiary_id">
                        <div class="rounded-xl border p-4 shadow-2xs"
                             :class="match.is_returning_beneficiary ? 'border-blue-200 bg-blue-50/70' : ((isSameYearConflict || isHouseholdLimit) ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="font-extrabold text-slate-900 text-sm" x-text="match.matched_beneficiary ? match.matched_beneficiary.full_name : (match.matched_beneficiary_name || 'Matched Beneficiary')"></h4>
                                    <p class="mt-0.5 text-xs font-semibold text-slate-700">
                                        DOB: <span x-text="(match.matched_beneficiary ? (match.matched_beneficiary.date_of_birth || match.matched_beneficiary.dob) : (match.existing_dob || 'N/A')).split('T')[0]"></span> |
                                        Brgy. <span x-text="match.matched_beneficiary ? match.matched_beneficiary.barangay : ''"></span>, <span x-text="match.matched_beneficiary ? match.matched_beneficiary.municipality : ''"></span>
                                    </p>
                                    <template x-if="match.last_availment">
                                        <p class="mt-1 text-[11px] font-bold"
                                           :class="(isSameYearConflict || isHouseholdLimit) ? 'text-rose-800' : 'text-blue-800'">
                                            Availment History: <span x-text="match.last_availment"></span>
                                        </p>
                                    </template>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-extrabold text-white shadow-2xs shrink-0"
                                      :class="match.is_returning_beneficiary ? 'bg-blue-700' : ((isSameYearConflict || isHouseholdLimit) ? 'bg-rose-600' : 'bg-amber-600')"
                                      x-text="match.match_score + '% Match'"></span>
                            </div>
                        </div>
                    </template>
                </div>

                @hasanyrole('Admin|Validator')
                <div x-show="!isReturningBeneficiary" class="pt-1">
                    <label class="mb-1.5 block text-xs font-bold text-slate-900">
                        <span x-text="isHouseholdLimit ? 'Validator Household Verification Remarks (Required) *' : (isSameNameDiffIdentity ? 'Validator Verification Remarks (e.g., Verified distinct individual / sibling via ID) *' : (isSameYearConflict ? 'Validator / Calamity Override Remarks *' : 'Validator Remarks (Required to Approve) *'))"></span>
                    </label>
                    <textarea x-model="overrideRemarks" rows="2"
                              :placeholder="isHouseholdLimit ? 'e.g., Verified separate family unit residing in separate structure in the same compound...' : (isSameNameDiffIdentity ? 'e.g., Verified distinct individual or sibling living in separate purok/home...' : 'State reason for approving/saving despite duplicate flag...')"
                              class="w-full rounded-xl border border-slate-300 p-3 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none"></textarea>
                </div>
                @endhasanyrole
            </div>

            {{-- Modal Footer (Fixed at bottom) --}}
            <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-slate-200 p-4 bg-slate-50 shrink-0">
                <button @click="showModal = false" type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                    Review & Cancel
                </button>

                <template x-if="existingBeneficiaryId">
                    <a :href="'/beneficiaries/' + existingBeneficiaryId" target="_blank"
                       class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer flex items-center gap-1.5 shadow-2xs">
                        <svg class="h-3.5 w-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        View Master Profile
                    </a>
                </template>

                <template x-if="isSameNameDiffIdentity && existingBeneficiaryId">
                    <a :href="'/beneficiaries/' + existingBeneficiaryId + '/edit'" target="_blank"
                       class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer flex items-center gap-1.5 shadow-2xs">
                        <svg class="h-3.5 w-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        Correct Existing Record
                    </a>
                </template>

                <template x-if="isReturningBeneficiary">
                    <button @click="attachToExisting" type="button" class="rounded-xl bg-blue-700 hover:bg-blue-800 px-4 py-2 text-xs font-extrabold text-white shadow-md transition cursor-pointer flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                        Attach <span x-text="targetAvailmentYear"></span> Availment
                    </button>
                </template>

                {{-- Option for Encoders & Validators to save and log to Duplicate Resolution Console --}}
                <template x-if="!isReturningBeneficiary">
                    <button @click="saveForReview" type="button" class="rounded-xl border border-blue-600 bg-blue-50 hover:bg-blue-100 text-blue-800 px-4 py-2 text-xs font-extrabold shadow-sm transition cursor-pointer flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        Save & Send to Duplicate Console
                    </button>
                </template>

                @hasanyrole('Admin|Validator')
                <template x-if="!isReturningBeneficiary">
                    <button @click="forceSave" type="button" class="rounded-xl bg-amber-600 px-4 py-2 text-xs font-extrabold text-white shadow-md hover:bg-amber-700 transition cursor-pointer">
                        <span x-text="isHouseholdLimit ? 'Verify Separate Household Unit & Approve' : (isSameNameDiffIdentity ? 'Verify as Distinct Person & Save' : 'Override & Proceed Registration')"></span>
                    </button>
                </template>
                @endhasanyrole
            </div>
        </div>
    </div>
</div>

<script>
    function beneficiaryForm() {
        return {
            selectedProgram: '{{ old("program_code", "TUPAD") }}',
            firstName: '{{ old("first_name", "") }}',
            middleName: '{{ old("middle_name", "") }}',
            lastName: '{{ old("last_name", "") }}',
            dob: '{{ old("date_of_birth", "") }}',
            calculatedAge: null,
            municipality: '{{ old("municipality", "") }}',
            selectedBarangay: '{{ old("barangay", "") }}',
            customBarangayInput: '',
            customBarangayMode: false,
            availableBarangays: [],
            selectedPurok: '{{ old("address", "") }}',
            customAddressInput: '',
            customAddressMode: false,
            contactNumber: '{{ old("contact_number", "") }}',
            contactError: '',
            formErrors: [],
            govIdNumber: '{{ old("government_id_number", "") }}',
            isStudent: {{ old('is_student') ? 'true' : 'false' }},
            isGovEmp: {{ old('is_government_employee') ? 'true' : 'false' }},
            isGraduating: {{ old('is_graduating_student', old('is_graduating_college')) ? 'true' : 'false' }},
            isCalamityOverride: {{ old('is_calamity_override') ? 'true' : 'false' }},
            calamityRemarks: '{{ old("calamity_remarks", "") }}',
            dilpType: '{{ old("enrollment_type", "individual") }}',
            checking: false,
            submitting: false,
            showModal: false,
            duplicateMatches: [],
            confirmOverride: 0,
            overrideRemarks: '',
            existingBeneficiaryId: null,
            existingBeneficiaryName: '',
            existingDob: '',
            inputDob: '',
            isSameNameDiffIdentity: false,
            isSameYearConflict: false,
            isHouseholdLimit: false,
            isEligibilityConflict: false,
            eligibilityErrorsList: [],
            isReturningBeneficiary: false,
            lastAvailedText: '',
            targetAvailmentYear: new Date().getFullYear(),

            barangaysByMunicipality: {
                'Malaybalay City': ['Aglayan', 'Bangcud', 'Cabangahan', 'Can-ayan', 'Casisang', 'Dalwangan', 'Imbatug', 'Indalasa', 'Kalasungay', 'Laguitas', 'Linabo', 'Mabalaw', 'Mailag', 'Managok', 'Mapayag', 'Poblacion 1', 'Poblacion 2', 'Poblacion 3', 'Poblacion 4', 'Poblacion 5', 'Poblacion 6', 'Poblacion 7', 'Poblacion 8', 'Poblacion 9', 'Poblacion 10', 'Poblacion 11', 'Saint Peter', 'San Jose', 'San Martin', 'Santo Niño', 'Simaya', 'Sinanglanan', 'Sumpong', 'Zamboanguita'],
                'Valencia City': ['Bagontaas', 'Batangan', 'Catumbalon', 'Colonia', 'Concepcion', 'Dagat-Kidapawan', 'Guinoyoran', 'Kahaponan', 'Laligan', 'Lilingayon', 'Lourma', 'Lumbo', 'Lurugan', 'Maapag', 'Mabuhay', 'Macapari', 'Mailag', 'Maligaya', 'Mt. Nebo', 'Pinatilan', 'Poblacion', 'San Carlos', 'San Isidro', 'Sugod', 'Tongantongan', 'Tugaya', 'Vintar'],
                'Manolo Fortich': ['Agusan Canyon', 'Dahilayan', 'Damilag', 'Dicklum', 'Guilang-guilang', 'Kalugmanan', 'Lindaban', 'Lingion', 'Lunocan', 'Maluko', 'Mantibugao', 'Minsuro', 'Sankanan', 'Santiago', 'Santo Niño', 'Tankulan', 'Ticala'],
                'Maramag': ['Anoling', 'Base Camp', 'Bayabason', 'Camp 1', 'Danggawan', 'Dibatulat', 'Kuya', 'La Asuncion', 'Panadtalan', 'Poblacion', 'San Miguel', 'Tubigon'],
                'Don Carlos': ['Bocboc', 'Buyot', 'Cabadiangan', 'Calangahan', 'Don Carlos Norte', 'Embayao', 'Kalunsican', 'Kasimcan', 'Kawawasan', 'Kiabo', 'Kibatang', 'Mahayahay', 'Manat', 'Maraymaray', 'New Bataan', 'Old Don Carlos', 'Poblacion', 'Pualas', 'San Antonio East', 'San Antonio West', 'San Nicolas', 'San Roque', 'Sinangguyan', 'Sulangon'],
                'Quezon': ['Butong', 'Cawayan', 'Cebole', 'Delapa', 'Freedom', 'Kapaalong', 'Kiburiao', 'Lamin', 'Libertad', 'Magsaysay', 'Merangeran', 'Minapan', 'Paitan', 'Palacapao', 'Pinamula', 'Poblacion', 'Puntian', 'Salawagan', 'San Jose', 'Santa Cruz', 'Santa Filomena'],
                'Lantapan': ['Alanib', 'Baccsay', 'Balila', 'Bantuanon', 'Basak', 'Capitan Juan', 'Cawayan', 'Ka-atan', 'Kibangay', 'Kulasihan', 'Poblacion', 'Songco', 'Victory'],
                'Impasugong': ['Capitan Bayong', 'Cawayan', 'Dumalaguing', 'Guihean', 'Hagpa', 'Kalabugao', 'Kibenton', 'La Fortuna', 'Poblacion', 'Sayawan'],
                'Sumilao': ['Kisolon', 'Poblacion', 'Puntian', 'Sanico', 'Vista Villa'],
                'Pangantucan': ['Adtuyon', 'Bacusanon', 'Bangahan', 'Barandias', 'Concepcion', 'Ganduz', 'Kimini', 'Langait', 'Mabuhay', 'New Eden', 'Payaket', 'Pigtauranan', 'Poblacion', 'Portulin', 'San Isidro', 'San Jose', 'San Vicente'],
                'Kibawe': ['Balintawak', 'Cagawasan', 'East Kibawe', 'Gutapol', 'Labuagon', 'Magsaysay', 'Marapangi', 'Masimu', 'Natulungan', 'New Kidapawan', 'Old Kibawe', 'Palma', 'Pinamula', 'Poblacion', 'Romagook', 'Sampaguita', 'San Pedro', 'Talahiron', 'West Kibawe'],
                'Dangcagan': ['Barongcot', 'Bugwak', 'Dolorosa', 'Kapalaran', 'Kianggat', 'Lourdes', 'Macrtan', 'Miaray', 'Osmeña', 'Poblacion', 'Sagbayan', 'San Vicente', 'Santo Niño'],
                'Kitaotao': ['Balangvie', 'Bobong', 'Bolochoc', 'Cabalantian', 'Calapatagan', 'Digongan', 'Kiis', 'Kitaiho', 'Kitobo', 'Magsaysay', 'Malobalo', 'Metocad', 'Panganan', 'Poblacion', 'San Isidro', 'San Lorenzo', 'San Luis', 'Sinuda', 'White Kulaman'],
                'Damulog': ['Aliputos', 'Ang-ang', 'Macapari', 'Migcawayan', 'New Opon', 'Omagling', 'Poblacion', 'Pocopoco', 'Sampagar', 'San Isidro', 'Splendido', 'Tangkulan'],
                'Kadingilan': ['Bagongsilang', 'Bagor', 'Balaoro', 'Baroy', 'Cabuaya', 'Central', 'Husayan', 'Kibangay', 'Kibalabag', 'Mabuhay', 'Matangad', 'Pay-as', 'Poblacion', 'Salvacion', 'San Martin', 'Sibonga'],
                'Baungon': ['Danatag', 'Imbatug', 'Kalilangan', 'Lacolac', 'Lancasiran', 'Liboran', 'Lingating', 'Mabuhay', 'Nicdao', 'Pualas', 'Salimbalan', 'San Vicente'],
                'Talakag': ['Dagumbaan', 'Indulang', 'Lapok', 'Liguron', 'Lingi-on', 'Miarayon', 'Poblacion', 'Salucot', 'San Antonio', 'San Isidro', 'San Miguel', 'Santo Niño', 'Tagbalogo'],
                'Libona': ['Capihan', 'Crossing', 'Kiliog', 'Laturan', 'Maating', 'Palabucan', 'Poblacion', 'Pongol', 'San Jose', 'Santa Cruz'],
                'Cabanglasan': ['Anlogan', 'Capimpayan', 'Jasaan', 'Kabulohan', 'Mandahican', 'Mawag', 'Paradise', 'Poblacion', 'Sayawan'],
                'San Fernando': ['Bonacao', 'Cabuling', 'Capanawasan', 'Halapitan', 'Iglugsad', 'Katipunan', 'Kawayan', 'Little Baguio', 'Mabuhay', 'Matupe', 'Namnam', 'Palacpacan', 'Poblacion', 'Sacred Heart', 'Santo Niño', 'Tugop']
            },

            init() {
                if (this.isGraduating) {
                    this.isStudent = true;
                }

                this.$watch('isGraduating', (value) => {
                    if (value) {
                        this.isStudent = true;
                    }
                });

                if (this.dob) {
                    this.calculateAge();
                }
                if (this.municipality) {
                    this.onMunicipalityChange();
                    if ('{{ old("barangay", "") }}') {
                        this.selectedBarangay = '{{ old("barangay", "") }}';
                    }
                }
                if (this.contactNumber) {
                    this.validateContact();
                }

                @if(session('duplicate_flags'))
                    this.duplicateMatches = @json(session('duplicate_flags'));
                    if (this.duplicateMatches.length > 0) {
                        const top = this.duplicateMatches[0];
                        this.existingBeneficiaryId = top.matched_beneficiary_id || null;
                        this.existingBeneficiaryName = top.matched_beneficiary ? top.matched_beneficiary.full_name : (top.matched_beneficiary_name || '');
                        this.existingDob = top.existing_dob || (top.matched_beneficiary ? top.matched_beneficiary.date_of_birth : '');
                        this.inputDob = top.input_dob || this.dob;
                        this.isSameNameDiffIdentity = Boolean(top.is_same_name_diff_identity);
                        this.isSameYearConflict = Boolean(top.is_same_year_conflict || top.same_program_current_year);
                        this.isHouseholdLimit = Boolean(top.is_household_limit || top.household_match_flag || top.is_household_match);
                        this.isReturningBeneficiary = Boolean(top.is_returning_beneficiary);
                        this.lastAvailedText = top.last_availment || '';
                    }
                    this.showModal = true;
                @endif

                @if($errors->any())
                    this.formErrors = @json($errors->all());
                    this.scrollToError();
                @endif
            },

            validateContact() {
                if (!this.contactNumber || this.contactNumber.trim() === '') {
                    this.contactError = '';
                    return true;
                }
                const trimmed = this.contactNumber.trim();
                const phRegex = /^(09|\+639)\d{9}$/;
                if (!phRegex.test(trimmed)) {
                    this.contactError = 'Contact number must be a valid PH mobile number (e.g., 09171234567 or +639171234567).';
                    return false;
                }
                this.contactError = '';
                return true;
            },

            scrollToError() {
                this.$nextTick(() => {
                    const errorBanner = document.getElementById('error-banner');
                    if (errorBanner) {
                        errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
            },

            onProgramChange() {
                if (this.selectedProgram === 'SPES') {
                    this.isStudent = true;
                }
            },

            onMunicipalityChange() {
                if (this.municipality && this.barangaysByMunicipality[this.municipality]) {
                    this.availableBarangays = this.barangaysByMunicipality[this.municipality];
                } else {
                    this.availableBarangays = [];
                }
                this.selectedBarangay = '';
                this.customBarangayMode = false;
                this.customBarangayInput = '';
            },

            onBarangaySelect() {
                if (this.selectedBarangay === '__OTHER__') {
                    this.customBarangayMode = true;
                } else {
                    this.customBarangayMode = false;
                }
            },

            onPurokSelect() {
                if (this.selectedPurok === '__OTHER__') {
                    this.customAddressMode = true;
                } else {
                    this.customAddressMode = false;
                }
            },

            calculateAge() {
                if (!this.dob) return;
                const birth = new Date(this.dob);
                const ageDifMs = Date.now() - birth.getTime();
                const ageDate = new Date(ageDifMs);
                this.calculatedAge = Math.abs(ageDate.getUTCFullYear() - 1970);
            },

            async submitRegistration(e, options = {}) {
                if (e && typeof e.preventDefault === 'function') {
                    e.preventDefault();
                }

                this.formErrors = [];

                // 1. Instant client-side contact validation
                if (!this.validateContact()) {
                    this.formErrors = [this.contactError];
                    this.scrollToError();
                    return;
                }

                const form = document.getElementById('registration-form');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const finalBrgy = this.customBarangayMode ? this.customBarangayInput : this.selectedBarangay;
                if (!finalBrgy) {
                    this.formErrors = ['Please select or specify a Barangay.'];
                    this.scrollToError();
                    return;
                }

                const finalAddress = this.customAddressMode ? this.customAddressInput : this.selectedPurok;

                // 2. If not confirmed override, not linking, and not logging for review, run pre-save duplicate check first
                if (!options.isOverride && !options.isLinking && !options.isLogForReview) {
                    this.checking = true;
                    try {
                        const response = await fetch('{{ route("beneficiaries.check-duplicate") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                first_name: this.firstName,
                                middle_name: this.middleName,
                                last_name: this.lastName,
                                date_of_birth: this.dob,
                                municipality: this.municipality,
                                barangay: finalBrgy,
                                address: finalAddress,
                                contact_number: this.contactNumber,
                                government_id_number: this.govIdNumber,
                                is_student: (this.isStudent || this.isGraduating) ? 1 : 0,
                                is_enrolled: (this.isStudent || this.isGraduating) ? 1 : 0,
                                is_graduating_student: this.isGraduating ? 1 : 0,
                                is_graduating_college: this.isGraduating ? 1 : 0,
                                is_government_employee: this.isGovEmp ? 1 : 0,
                                program_code: this.selectedProgram,
                                availment_year: document.querySelector('input[name="availment_year"]')?.value || new Date().getFullYear(),
                                is_calamity_override: this.isCalamityOverride ? 1 : 0,
                                calamity_remarks: this.calamityRemarks
                            })
                        });

                        const res = await response.json().catch(() => ({}));
                        this.checking = false;

                        if (response.status === 409 || res.has_duplicates || (res.flags && res.flags.length > 0) || res.status === 'same_year_conflict' || res.is_same_year_conflict || res.status === 'household_limit_detected' || res.is_household_limit || res.status === 'eligibility_restriction' || res.is_eligibility_conflict) {
                            this.duplicateMatches = res.flags || res.duplicates || [];
                            this.isReturningBeneficiary = Boolean(res.is_returning_beneficiary);
                            this.isSameYearConflict = Boolean(res.is_same_year_conflict || res.status === 'same_year_conflict' || (this.duplicateMatches[0] && (this.duplicateMatches[0].is_same_year_conflict || this.duplicateMatches[0].same_program_current_year)));
                            this.isHouseholdLimit = Boolean(res.is_household_limit || res.status === 'household_limit_detected' || (this.duplicateMatches[0] && this.duplicateMatches[0].is_household_match));
                            this.isEligibilityConflict = Boolean(res.is_eligibility_conflict || res.status === 'eligibility_restriction');
                            this.eligibilityErrorsList = res.eligibility_errors || (res.errors ? Object.values(res.errors).flat() : (res.message ? [res.message] : []));
                            this.isSameNameDiffIdentity = Boolean(res.is_same_name_diff_identity || (this.duplicateMatches[0] && this.duplicateMatches[0].is_same_name_diff_identity));
                            this.existingBeneficiaryId = res.existing_beneficiary_id || (this.duplicateMatches[0] ? this.duplicateMatches[0].matched_beneficiary_id : null);
                            this.existingBeneficiaryName = res.existing_beneficiary_name || (this.duplicateMatches[0] ? (this.duplicateMatches[0].matched_beneficiary_name || (this.duplicateMatches[0].matched_beneficiary ? this.duplicateMatches[0].matched_beneficiary.full_name : '')) : '');
                            this.existingDob = res.existing_dob || (this.duplicateMatches[0] ? (this.duplicateMatches[0].existing_dob || (this.duplicateMatches[0].matched_beneficiary ? this.duplicateMatches[0].matched_beneficiary.date_of_birth : '')) : '');
                            this.inputDob = res.input_dob || this.dob;
                            this.lastAvailedText = res.last_availment || (this.duplicateMatches[0] ? this.duplicateMatches[0].last_availment : '');
                            this.targetAvailmentYear = document.querySelector('input[name="availment_year"]')?.value || new Date().getFullYear();
                            this.showModal = true;
                            return;
                        }

                        if (response.status === 422 && !res.has_duplicates && !res.flags && !res.is_household_limit && !res.is_eligibility_conflict) {
                            this.formErrors = res.message ? [res.message] : Object.values(res.errors || {}).flat();
                            this.scrollToError();
                            return;
                        }
                    } catch (err) {
                        this.checking = false;
                        console.error('Duplicate check exception:', err);
                        this.formErrors = ['Network connection error while validating duplicate status. Please check your connection.'];
                        this.scrollToError();
                        return;
                    }
                }

                // 3. Submit Form Data via AJAX (Never refreshes page, all data preserved!)
                this.submitting = true;
                try {
                    const formData = new FormData(form);
                    if (this.isGraduating) {
                        formData.set('is_graduating_student', '1');
                        formData.set('is_graduating_college', '1');
                        formData.set('is_student', '1');
                        formData.set('is_enrolled', '1');
                    }
                    if (options.isLinking && this.existingBeneficiaryId) {
                        formData.set('existing_beneficiary_id', this.existingBeneficiaryId);
                        formData.set('confirm_override', '0');
                        formData.set('log_for_review', '0');
                    } else if (options.isOverride) {
                        formData.delete('existing_beneficiary_id');
                        formData.set('confirm_override', '1');
                        formData.set('override_duplicate', '1');
                        formData.set('override_remarks', this.overrideRemarks);
                        formData.set('log_for_review', '0');
                    } else if (options.isLogForReview) {
                        formData.delete('existing_beneficiary_id');
                        formData.set('confirm_override', '0');
                        formData.set('override_duplicate', '0');
                        formData.set('log_for_review', '1');
                        formData.set('override_remarks', this.overrideRemarks || 'Queued for Validator Review in Duplicate Console');
                    }

                    const storeRes = await fetch('{{ route("beneficiaries.store") }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });

                    const storeData = await storeRes.json().catch(() => ({}));
                    this.submitting = false;
                    this.checking = false;

                    if (!storeRes.ok) {
                        if (storeRes.status === 409 || storeData.status === 'same_year_conflict' || storeData.is_same_year_conflict || storeData.status === 'household_limit_detected' || storeData.is_household_limit || storeData.status === 'eligibility_restriction' || storeData.is_eligibility_conflict) {
                            this.duplicateMatches = storeData.flags || storeData.duplicates || [];
                            this.isReturningBeneficiary = Boolean(storeData.is_returning_beneficiary);
                            this.isSameYearConflict = Boolean(storeData.is_same_year_conflict || storeData.status === 'same_year_conflict' || (this.duplicateMatches[0] && (this.duplicateMatches[0].is_same_year_conflict || this.duplicateMatches[0].same_program_current_year)));
                            this.isHouseholdLimit = Boolean(storeData.is_household_limit || storeData.status === 'household_limit_detected' || (this.duplicateMatches[0] && this.duplicateMatches[0].is_household_match));
                            this.isEligibilityConflict = Boolean(storeData.is_eligibility_conflict || storeData.status === 'eligibility_restriction');
                            this.eligibilityErrorsList = storeData.eligibility_errors || (storeData.errors ? Object.values(storeData.errors).flat() : (storeData.message ? [storeData.message] : []));
                            this.isSameNameDiffIdentity = Boolean(storeData.is_same_name_diff_identity || (this.duplicateMatches[0] && this.duplicateMatches[0].is_same_name_diff_identity));
                            this.existingBeneficiaryId = storeData.existing_beneficiary_id || (this.duplicateMatches[0] ? this.duplicateMatches[0].matched_beneficiary_id : null);
                            this.existingBeneficiaryName = storeData.existing_beneficiary_name || (this.duplicateMatches[0] ? (this.duplicateMatches[0].matched_beneficiary_name || (this.duplicateMatches[0].matched_beneficiary ? this.duplicateMatches[0].matched_beneficiary.full_name : '')) : '');
                            this.existingDob = storeData.existing_dob || (this.duplicateMatches[0] ? (this.duplicateMatches[0].existing_dob || (this.duplicateMatches[0].matched_beneficiary ? this.duplicateMatches[0].matched_beneficiary.date_of_birth : '')) : '');
                            this.inputDob = storeData.input_dob || this.dob;
                            this.lastAvailedText = storeData.last_availment || (this.duplicateMatches[0] ? this.duplicateMatches[0].last_availment : '');
                            this.targetAvailmentYear = document.querySelector('input[name="availment_year"]')?.value || new Date().getFullYear();
                            this.showModal = true;
                            return;
                        }

                        let errors = [];
                        if (storeData.errors) {
                            errors = Object.values(storeData.errors).flat();
                        } else if (storeData.message) {
                            errors = [storeData.message];
                        } else {
                            errors = [`Server returned error code ${storeRes.status}`];
                        }
                        this.formErrors = errors;
                        if (storeData.errors?.contact_number) {
                            this.contactError = storeData.errors.contact_number[0];
                        }
                        this.scrollToError();
                        return;
                    }

                    // Success! Redirect to show page or duplicate console
                    if (storeData.redirect_url) {
                        window.location.href = storeData.redirect_url;
                    } else if (storeData.beneficiary_id) {
                        window.location.href = `/beneficiaries/${storeData.beneficiary_id}`;
                    } else {
                        window.location.href = '{{ route("beneficiaries.index") }}';
                    }
                } catch (err) {
                    this.submitting = false;
                    this.checking = false;
                    console.error('Store exception:', err);
                    this.formErrors = ['An unexpected error occurred while saving beneficiary. Your entered details have been preserved.'];
                    this.scrollToError();
                }
            },

            attachToExisting() {
                if (!this.existingBeneficiaryId && this.duplicateMatches.length > 0) {
                    this.existingBeneficiaryId = this.duplicateMatches[0].matched_beneficiary_id;
                }
                this.confirmOverride = 0;
                this.showModal = false;
                this.submitRegistration(null, { isLinking: true });
            },

            saveForReview() {
                this.existingBeneficiaryId = null;
                this.showModal = false;
                this.submitRegistration(null, { isLogForReview: true });
            },

            forceSave() {
                if (!this.overrideRemarks.trim()) {
                    alert('Please enter Validator remarks before saving as a new profile or overriding duplicate.');
                    return;
                }
                this.existingBeneficiaryId = null;
                this.confirmOverride = 1;
                this.showModal = false;
                this.submitRegistration(null, { isOverride: true });
            }
        }
    }
</script>
@endsection
