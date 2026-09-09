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
                    @php
                        $oldCivil = old('civil_status', '');
                        $stdCivils = ['Single', 'Married', 'Widowed', 'Separated'];
                        $isCivilMatched = false;
                    @endphp
                    <select name="civil_status" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Status</option>
                        @foreach($stdCivils as $st)
                            @php
                                $sel = strcasecmp($oldCivil, $st) === 0 || ($st === 'Widowed' && strcasecmp($oldCivil, 'Widow') === 0);
                                if ($sel) { $isCivilMatched = true; }
                            @endphp
                            <option value="{{ $st }}" {{ $sel ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                        @if(!empty($oldCivil) && !$isCivilMatched)
                            <option value="{{ $oldCivil }}" selected>{{ $oldCivil }}</option>
                        @endif
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
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Province</label>
                    <input type="text" name="province" x-model="province" placeholder="Bukidnon"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>

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
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">District</label>
                    <select name="district" x-model="district"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select District</option>
                        <option value="District 1">District 1 (I)</option>
                        <option value="District 2">District 2 (II)</option>
                        <option value="District 3">District 3 (III)</option>
                        <option value="District 4">District 4 (IV)</option>
                        <option value="I">District I</option>
                        <option value="II">District II</option>
                        <option value="III">District III</option>
                        <option value="IV">District IV</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                    @php
                        $oldGid = old('government_id_type', '');
                        $standardIdTypes = [
                            'PhilSys ID' => 'Philippine National ID (PhilSys)',
                            'UMID' => 'Unified Multi-Purpose ID (UMID)',
                            'SSS ID' => 'Social Security System (SSS) ID',
                            'GSIS ID' => 'GSIS eCard / ID',
                            'TIN Card' => 'Tax Identification Number (TIN) Card',
                            'Pag-IBIG ID' => 'Pag-IBIG (HDMF) ID / Loyalty Card',
                            'PhilHealth ID' => 'PhilHealth Healthpass / ID',
                            "Voter's ID" => "Voter's ID / Voter Certification",
                            "Driver's License" => "Driver's License (LTO)",
                            'Passport' => 'Philippine Passport (DFA)',
                            'Senior Citizen ID' => 'Senior Citizen ID',
                            'PWD ID' => 'Person with Disability (PWD) ID',
                            'Postal ID' => 'Postal ID',
                            'Barangay ID' => 'Barangay ID / Barangay Clearance',
                            'PRC ID' => 'Professional Regulation Commission (PRC) ID',
                            'OWWA ID' => 'OWWA / E-Card ID',
                            'Solo Parent ID' => 'Solo Parent ID',
                            'NBI Clearance' => 'NBI Clearance',
                            'Police Clearance' => 'Police Clearance',
                            'Student ID' => 'Student / School ID',
                            'Other ID' => 'Other Government Valid ID',
                        ];
                        $isGidMatched = false;
                    @endphp
                    <select name="government_id_type" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Government ID Type</option>
                        @foreach($standardIdTypes as $idKey => $idLabel)
                            @php
                                $selected = strcasecmp($oldGid, $idKey) === 0 || strcasecmp($oldGid, $idLabel) === 0 || ($idKey === 'SSS ID' && strcasecmp($oldGid, 'SSS') === 0);
                                if ($selected) { $isGidMatched = true; }
                            @endphp
                            <option value="{{ $idKey }}" {{ $selected ? 'selected' : '' }}>{{ $idLabel }}</option>
                        @endforeach
                        @if(!empty($oldGid) && !$isGidMatched)
                            <option value="{{ $oldGid }}" selected>{{ $oldGid }}</option>
                        @endif
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
             class="gov-card p-6">
            <div class="mb-5 flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-700 font-extrabold text-white text-base shadow-md">
                        4
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Additional Details</h3>
                        <p class="text-xs font-medium text-slate-600">Sector classification, income, occupation, and dependent information</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">E-Payment Account No.</label>
                    <input type="text" name="epayment_account_no" placeholder="GCash / Maya / Card" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">Beneficiary Sector/Type</label>
                    @php
                        $oldBenType = old('beneficiary_type', '');
                        $stdSectors = [
                            'Underemployed' => 'Underemployed Worker',
                            'Displaced Worker' => 'Displaced Worker',
                            'Self-Employed' => 'Self-Employed / Informal Sector Worker',
                            'Seasonal Worker' => 'Seasonal Worker',
                            'Indigenous People' => 'Indigenous People (IP)',
                            'Solo Parent' => 'Solo Parent',
                            'PWD' => 'Person with Disability (PWD)',
                            'Senior Citizen' => 'Senior Citizen (60+)',
                            'Youth / Student' => 'Youth / Student',
                            'Farmer / Fisherfolk' => 'Farmer / Fisherfolk',
                            'Returning OFW' => 'OFW Returnee',
                            'Victim of Calamity / Disaster' => 'Victim of Calamity / Disaster',
                            'Other' => 'Other Sector',
                        ];
                        $isSectorMatched = false;
                    @endphp
                    <select name="beneficiary_type" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Sector/Type</option>
                        @foreach($stdSectors as $secKey => $secLabel)
                            @php
                                $selected = strcasecmp($oldBenType, $secKey) === 0 
                                    || strcasecmp($oldBenType, $secLabel) === 0 
                                    || ($secKey === 'Indigenous People' && (strcasecmp($oldBenType, 'IP') === 0 || str_contains(strtolower($oldBenType), 'indigenous')))
                                    || ($secKey === 'Self-Employed' && str_contains(strtolower($oldBenType), 'self-employed'))
                                    || ($secKey === 'Underemployed' && str_contains(strtolower($oldBenType), 'underemployed'))
                                    || ($secKey === 'Displaced Worker' && (str_contains(strtolower($oldBenType), 'displaced') || str_contains(strtolower($oldBenType), 'unemployed')))
                                    || ($secKey === 'PWD' && str_contains(strtolower($oldBenType), 'disability'))
                                    || ($secKey === 'Returning OFW' && (str_contains(strtolower($oldBenType), 'ofw') || str_contains(strtolower($oldBenType), 'returning')));
                                if ($selected) { $isSectorMatched = true; }
                            @endphp
                            <option value="{{ $secKey }}" {{ $selected ? 'selected' : '' }}>{{ $secLabel }}</option>
                        @endforeach
                        @if(!empty($oldBenType) && !$isSectorMatched)
                            <option value="{{ $oldBenType }}" selected>{{ $oldBenType }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">Average Monthly Income</label>
                    @php
                        $oldIncome = old('average_monthly_income', '');
                        $stdIncomes = [
                            'Below ₱5,000' => 'Below ₱5,000',
                            '₱5,000 - ₱10,000' => '₱5,000 - ₱10,000',
                            '₱10,001 - ₱15,000' => '₱10,001 - ₱15,000',
                            '₱15,001 - ₱20,000' => '₱15,001 - ₱20,000',
                            'Above ₱20,000' => 'Above ₱20,000',
                            'No Regular Income' => 'No Regular Income / Sub-poverty',
                        ];
                        $isIncomeMatched = false;
                    @endphp
                    <select name="average_monthly_income" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Monthly Income Bracket</option>
                        @foreach($stdIncomes as $incKey => $incLabel)
                            @php
                                $selected = strcasecmp($oldIncome, $incKey) === 0 
                                    || strcasecmp($oldIncome, $incLabel) === 0
                                    || ($incKey === '₱5,000 - ₱10,000' && ($oldIncome === '5000-10000' || $oldIncome === '5,000 - 10,000'))
                                    || ($incKey === 'Below ₱5,000' && ($oldIncome === 'Below 5,000' || $oldIncome === 'Below 5000'))
                                    || ($incKey === '₱10,001 - ₱15,000' && ($oldIncome === '10001-15000' || $oldIncome === '10,001 - 15,000'))
                                    || ($incKey === '₱15,001 - ₱20,000' && ($oldIncome === '15001-20000' || $oldIncome === '15,001 - 20,000'))
                                    || ($incKey === 'Above ₱20,000' && ($oldIncome === 'Above 20000' || $oldIncome === 'Above 20,000'));
                                if ($selected) { $isIncomeMatched = true; }
                            @endphp
                            <option value="{{ $incKey }}" {{ $selected ? 'selected' : '' }}>{{ $incLabel }}</option>
                        @endforeach
                        @if(!empty($oldIncome) && !$isIncomeMatched)
                            <option value="{{ $oldIncome }}" selected>{{ $oldIncome }}</option>
                        @endif
                    </select>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">Occupation</label>
                    <input type="text" name="occupation" value="{{ old('occupation') }}"
                           placeholder="e.g. Laborer, Crop Grower, Homebased..."
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">Dependent (Name)</label>
                    <input type="text" name="dependent" value="{{ old('dependent') }}"
                           placeholder="Full name of dependent..."
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">Relationship to Dependent</label>
                    @php
                        $oldRel = old('relationship_dependent', '');
                        $stdRels = ['Spouse', 'Husband', 'Wife', 'Son', 'Daughter', 'Child', 'Mother', 'Father', 'Parent', 'Brother', 'Sister', 'Sibling', 'Live-in Partner', 'Grandchild', 'Other'];
                        $isRelMatched = false;
                    @endphp
                    <select name="relationship_dependent" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Relationship</option>
                        @foreach($stdRels as $relOpt)
                            @php
                                $selected = strcasecmp($oldRel, $relOpt) === 0;
                                if ($selected) { $isRelMatched = true; }
                            @endphp
                            <option value="{{ $relOpt }}" {{ $selected ? 'selected' : '' }}>{{ $relOpt }}</option>
                        @endforeach
                        @if(!empty($oldRel) && !$isRelMatched)
                            <option value="{{ $oldRel }}" selected>{{ $oldRel }}</option>
                        @endif
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
    @include('beneficiaries.partials.duplicate-modal')
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
            province: '{{ old("province", "Bukidnon") }}',
            district: '{{ old("district", "") }}',
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

            districtByMunicipality: {
                'Baungon': 'District 1',
                'Libona': 'District 1',
                'Malitbog': 'District 1',
                'Manolo Fortich': 'District 1',
                'Sumilao': 'District 1',
                'Talakag': 'District 1',
                'Cabanglasan': 'District 2',
                'Impasugong': 'District 2',
                'Malaybalay City': 'District 2',
                'San Fernando': 'District 2',
                'Damulog': 'District 3',
                'Dangcagan': 'District 3',
                'Don Carlos': 'District 3',
                'Kadingilan': 'District 3',
                'Kibawe': 'District 3',
                'Kitaotao': 'District 3',
                'Maramag': 'District 3',
                'Quezon': 'District 3',
                'Kalilangan': 'District 4',
                'Pangantucan': 'District 4',
                'Valencia City': 'District 4'
            },

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
                'Pangantucan': ['Adtuyon', 'Bacusanon', 'Bangahan', 'Barandias', 'Concepcion', 'Ganduz', 'Kimini', 'Langait', 'Mabuhay', 'Mendis', 'New Eden', 'Payaket', 'Pigtauranan', 'Poblacion', 'Portulin', 'San Isidro', 'San Jose', 'San Vicente'],
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
                    const found = Object.keys(this.barangaysByMunicipality).find(
                        m => m.toLowerCase() === this.municipality.trim().toLowerCase()
                    );
                    if (found) {
                        this.municipality = found;
                    }
                    this.availableBarangays = this.barangaysByMunicipality[this.municipality] || [];
                    if (!this.district && this.districtByMunicipality[this.municipality]) {
                        this.district = this.districtByMunicipality[this.municipality];
                    }
                    const initialBrgy = '{{ old("barangay", "") }}';
                    if (initialBrgy) {
                        const foundBrgy = this.availableBarangays.find(
                            b => b.toLowerCase() === initialBrgy.trim().toLowerCase()
                        );
                        if (foundBrgy) {
                            this.selectedBarangay = foundBrgy;
                        } else {
                            this.selectedBarangay = '__OTHER__';
                            this.customBarangayInput = initialBrgy;
                            this.customBarangayMode = true;
                        }
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
                        this.existingBeneficiaryName = top.matched_beneficiary_name || (top.matched_beneficiary ? (top.matched_beneficiary.full_name || (top.matched_beneficiary.last_name + ', ' + top.matched_beneficiary.first_name)) : '') || top.full_name || '';
                        this.existingDob = top.existing_dob || (top.matched_beneficiary ? (top.matched_beneficiary.date_of_birth || '') : '') || top.date_of_birth || '';
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
                const upper = trimmed.toUpperCase();
                if (['N/A', 'NA', 'NONE', '-', 'NULL', 'N.A.', 'NOT APPLICABLE'].includes(upper)) {
                    this.contactError = '';
                    return true;
                }
                const digits = trimmed.replace(/\D/g, '');
                if (digits.length > 0 && digits.length < 10 && !trimmed.startsWith('+')) {
                    this.contactError = 'Contact number should be a valid mobile number (e.g., 09171234567) or N/A.';
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
                if (this.municipality) {
                    const found = Object.keys(this.barangaysByMunicipality).find(
                        m => m.toLowerCase() === this.municipality.trim().toLowerCase()
                    );
                    if (found) {
                        this.municipality = found;
                    }
                }
                if (this.municipality && this.barangaysByMunicipality[this.municipality]) {
                    this.availableBarangays = this.barangaysByMunicipality[this.municipality];
                    if (!this.district && this.districtByMunicipality[this.municipality]) {
                        this.district = this.districtByMunicipality[this.municipality];
                    }
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
                            this.existingBeneficiaryName = res.existing_beneficiary_name || (this.duplicateMatches[0] ? (this.duplicateMatches[0].matched_beneficiary_name || (this.duplicateMatches[0].matched_beneficiary ? (this.duplicateMatches[0].matched_beneficiary.full_name || (this.duplicateMatches[0].matched_beneficiary.last_name + ', ' + this.duplicateMatches[0].matched_beneficiary.first_name)) : '') || this.duplicateMatches[0].full_name || '') : '');
                            this.existingDob = res.existing_dob || (this.duplicateMatches[0] ? (this.duplicateMatches[0].existing_dob || (this.duplicateMatches[0].matched_beneficiary ? this.duplicateMatches[0].matched_beneficiary.date_of_birth : '') || this.duplicateMatches[0].date_of_birth || '') : '');
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
                    formData.set('is_student', (this.isStudent || this.isGraduating) ? '1' : '0');
                    formData.set('is_enrolled', (this.isStudent || this.isGraduating) ? '1' : '0');
                    formData.set('is_graduating_student', this.isGraduating ? '1' : '0');
                    formData.set('is_graduating_college', this.isGraduating ? '1' : '0');
                    formData.set('is_government_employee', this.isGovEmp ? '1' : '0');
                    formData.set('is_pwd', this.isPwd ? '1' : '0');
                    formData.set('is_senior_citizen', this.isSenior ? '1' : '0');
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
                            this.existingBeneficiaryName = storeData.existing_beneficiary_name || (this.duplicateMatches[0] ? (this.duplicateMatches[0].matched_beneficiary_name || (this.duplicateMatches[0].matched_beneficiary ? (this.duplicateMatches[0].matched_beneficiary.full_name || (this.duplicateMatches[0].matched_beneficiary.last_name + ', ' + this.duplicateMatches[0].matched_beneficiary.first_name)) : '') || this.duplicateMatches[0].full_name || '') : '');
                            this.existingDob = storeData.existing_dob || (this.duplicateMatches[0] ? (this.duplicateMatches[0].existing_dob || (this.duplicateMatches[0].matched_beneficiary ? this.duplicateMatches[0].matched_beneficiary.date_of_birth : '') || this.duplicateMatches[0].date_of_birth || '') : '');
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
