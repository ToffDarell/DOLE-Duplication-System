@extends('layouts.app')
@section('title', 'Register Beneficiary')
@section('page-title', 'Beneficiary Registration')
@section('page-subtitle', 'Single registration portal with real-time duplicate engine validation & eligibility check')

@section('content')
<div class="mx-auto max-w-4xl" x-data="beneficiaryForm()">
    <form @submit.prevent="submitForm" action="{{ route('beneficiaries.store') }}" method="POST" id="registration-form" class="space-y-6">
        @csrf

        {{-- Hidden Override Signals --}}
        <input type="hidden" name="confirm_override" x-model="confirmOverride">
        <input type="hidden" name="override_remarks" x-model="overrideRemarks">

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
                    👵 Senior Citizen Highlight (60+)
                </span>
                <span x-show="selectedProgram === 'SPES' && isGraduating" x-transition class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-extrabold text-emerald-900 border border-emerald-300 shadow-2xs">
                    🎓 Graduating College Student Highlight
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

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
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
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Contact Number (PH format)</label>
                    <input type="text" name="contact_number" x-model="contactNumber" placeholder="09171234567"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Government ID Type</label>
                    <input type="text" name="government_id_type" placeholder="PhilSys, UMID, TIN, SSS"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
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
                    <input type="checkbox" name="is_graduating_college" value="1" x-model="isGraduating" class="rounded border-emerald-500 text-emerald-600 focus:ring-emerald-500">
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
                    <input type="text" name="beneficiary_type" placeholder="Underemployed, IP, Displaced" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-800">Average Monthly Income</label>
                    <input type="text" name="average_monthly_income" placeholder="e.g. 5000-10000" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
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
                <p class="font-bold mb-1">🎓 SPES Eligibility Guidelines:</p>
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
            <button type="submit" :disabled="checking"
                    class="flex items-center gap-2 rounded-xl bg-blue-700 hover:bg-blue-800 px-6 py-3 text-sm font-extrabold text-white shadow-md transition disabled:opacity-50 cursor-pointer">
                <span x-show="!checking">+ Register Beneficiary</span>
                <span x-show="checking" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Evaluating Duplicates...
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
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-md" style="display: none;">
        
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="w-full max-w-2xl rounded-2xl border border-amber-300 bg-white p-6 shadow-2xl">
            
            <div class="mb-5 flex items-center gap-3.5 border-b border-slate-200 pb-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500 font-black text-white text-xl shadow-md">
                    ⚠️
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Possible Duplicate Record Flagged!</h3>
                    <p class="text-xs font-semibold text-slate-600">The engine detected existing beneficiary records matching this applicant</p>
                </div>
            </div>

            <div class="mb-5 max-h-64 space-y-3 overflow-y-auto pr-1">
                <template x-for="match in duplicateMatches" :key="match.matched_beneficiary_id">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-2xs">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="font-extrabold text-slate-900 text-sm" x-text="match.matched_beneficiary.full_name"></h4>
                                <p class="mt-0.5 text-xs font-semibold text-slate-700">
                                    DOB: <span x-text="match.matched_beneficiary.date_of_birth"></span> |
                                    Brgy. <span x-text="match.matched_beneficiary.barangay"></span>, <span x-text="match.matched_beneficiary.municipality"></span>
                                </p>
                            </div>
                            <span class="rounded-full bg-amber-600 px-3 py-1 text-xs font-extrabold text-white shadow-2xs" x-text="match.match_score + '% Match'"></span>
                        </div>
                    </div>
                </template>
            </div>

            @hasanyrole('Admin|Validator')
            <div class="mb-5">
                <label class="mb-1.5 block text-xs font-bold text-slate-900">Validator Remarks (Required to Save) *</label>
                <textarea x-model="overrideRemarks" rows="2" placeholder="State reason for approving/saving despite duplicate flag..."
                          class="w-full rounded-xl border border-slate-300 p-3 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none"></textarea>
            </div>
            @endhasanyrole

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                <button @click="showModal = false" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">
                    Cancel & Edit Form
                </button>
                @hasanyrole('Admin|Validator')
                <button @click="forceSave" class="rounded-xl bg-amber-600 px-5 py-2 text-xs font-bold text-white shadow-md hover:bg-amber-700 transition">
                    Approve & Save Record
                </button>
                @else
                <p class="text-xs text-rose-600 font-bold self-center">Encoder role cannot override duplicates. Request a Validator/Admin to approve.</p>
                @endhasanyrole
            </div>
        </div>
    </div>
</div>

<script>
    function beneficiaryForm() {
        return {
            selectedProgram: 'TUPAD',
            firstName: '',
            middleName: '',
            lastName: '',
            dob: '',
            calculatedAge: null,
            municipality: '',
            selectedBarangay: '',
            customBarangayInput: '',
            customBarangayMode: false,
            availableBarangays: [],
            contactNumber: '',
            govIdNumber: '',
            isStudent: false,
            isGovEmp: false,
            isGraduating: false,
            dilpType: 'individual',
            checking: false,
            showModal: false,
            duplicateMatches: [],
            confirmOverride: 0,
            overrideRemarks: '',

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

            calculateAge() {
                if (!this.dob) return;
                const birth = new Date(this.dob);
                const ageDifMs = Date.now() - birth.getTime();
                const ageDate = new Date(ageDifMs);
                this.calculatedAge = Math.abs(ageDate.getUTCFullYear() - 1970);
            },

            async submitForm() {
                if (this.confirmOverride) {
                    document.getElementById('registration-form').submit();
                    return;
                }

                const finalBrgy = this.customBarangayMode ? this.customBarangayInput : this.selectedBarangay;

                this.checking = true;
                try {
                    const response = await fetch('{{ route("beneficiaries.check-duplicate") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            first_name: this.firstName,
                            middle_name: this.middleName,
                            last_name: this.lastName,
                            date_of_birth: this.dob,
                            municipality: this.municipality,
                            barangay: finalBrgy,
                            contact_number: this.contactNumber,
                            government_id_number: this.govIdNumber
                        })
                    });

                    const res = await response.json();
                    this.checking = false;

                    if (res.has_duplicates) {
                        this.duplicateMatches = res.flags;
                        this.showModal = true;
                    } else {
                        document.getElementById('registration-form').submit();
                    }
                } catch (e) {
                    this.checking = false;
                    document.getElementById('registration-form').submit();
                }
            },

            forceSave() {
                if (!this.overrideRemarks.trim()) {
                    alert('Please enter remarks before approving the duplicate record.');
                    return;
                }
                this.confirmOverride = 1;
                this.showModal = false;
                document.getElementById('registration-form').submit();
            }
        }
    }
</script>
@endsection
