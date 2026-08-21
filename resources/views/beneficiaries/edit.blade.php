@extends('layouts.app')
@section('title', 'Edit ' . $beneficiary->full_name)
@section('page-title', 'Edit Beneficiary Profile')
@section('page-subtitle', 'Update demographic information, location, identification details, and program availments')

@section('content')
<div class="mx-auto max-w-4xl space-y-6" x-data="editBeneficiaryForm()">
    <form action="{{ route('beneficiaries.update', $beneficiary) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Personal Details Card --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="mb-5 flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-700 font-extrabold text-white text-base shadow-md">
                        1
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Personal Identity</h3>
                        <p class="text-xs font-medium text-slate-600">Full legal name, birthdate, and civil status</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">First Name *</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $beneficiary->first_name) }}" required
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name', $beneficiary->middle_name) }}"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Last Name *</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $beneficiary->last_name) }}" required
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Suffix (Jr., Sr., III)</label>
                    <input type="text" name="suffix" value="{{ old('suffix', $beneficiary->suffix) }}"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Date of Birth *</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $beneficiary->date_of_birth?->format('Y-m-d')) }}" required
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Sex *</label>
                    <select name="sex" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="Male" {{ old('sex', $beneficiary->sex) == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('sex', $beneficiary->sex) == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Civil Status</label>
                    <select name="civil_status" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Status</option>
                        <option value="Single" {{ old('civil_status', $beneficiary->civil_status) == 'Single' ? 'selected' : '' }}>Single</option>
                        <option value="Married" {{ old('civil_status', $beneficiary->civil_status) == 'Married' ? 'selected' : '' }}>Married</option>
                        <option value="Widowed" {{ old('civil_status', $beneficiary->civil_status) == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                        <option value="Separated" {{ old('civil_status', $beneficiary->civil_status) == 'Separated' ? 'selected' : '' }}>Separated</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Address & Contact Card --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="mb-5 flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-700 font-extrabold text-white text-base shadow-md">
                        2
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-teal-900 uppercase tracking-wider">Address & Contact</h3>
                        <p class="text-xs font-medium text-slate-600">Bukidnon location, Purok/Sitio address, and government IDs</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Municipality (Bukidnon) *</label>
                    <select name="municipality" x-model="municipality" @change="onMunicipalityChange" required
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Municipality</option>
                        @foreach($municipalities as $muni)
                            <option value="{{ $muni }}">{{ $muni }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Barangay *</label>
                    <select x-model="selectedBarangay" @change="onBarangaySelect" required
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Barangay</option>
                        <template x-for="brgy in availableBarangays" :key="brgy">
                            <option :value="brgy" x-text="brgy"></option>
                        </template>
                        <option value="__OTHER__">+ Other / Custom Barangay...</option>
                    </select>
                    <input type="hidden" name="barangay" :value="customBarangayMode ? customBarangayInput : selectedBarangay">

                    <div x-show="customBarangayMode" class="mt-2">
                        <input type="text" x-model="customBarangayInput" placeholder="Enter custom barangay name..."
                               class="w-full rounded-xl border border-blue-400 bg-blue-50/50 px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Purok / Sitio / Address</label>
                    <select x-model="selectedPurok" @change="onPurokSelect"
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
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Contact Number</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number', $beneficiary->contact_number) }}" placeholder="09171234567"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Government ID Type</label>
                    <select name="government_id_type" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">Select Government ID Type</option>
                        @php $gid = old('government_id_type', $beneficiary->government_id_type); @endphp
                        <option value="PhilSys ID" {{ $gid == 'PhilSys ID' ? 'selected' : '' }}>Philippine National ID (PhilSys)</option>
                        <option value="UMID" {{ $gid == 'UMID' ? 'selected' : '' }}>Unified Multi-Purpose ID (UMID)</option>
                        <option value="SSS ID" {{ $gid == 'SSS ID' ? 'selected' : '' }}>Social Security System (SSS) ID</option>
                        <option value="GSIS ID" {{ $gid == 'GSIS ID' ? 'selected' : '' }}>GSIS eCard / ID</option>
                        <option value="TIN Card" {{ $gid == 'TIN Card' ? 'selected' : '' }}>Tax Identification Number (TIN) Card</option>
                        <option value="Pag-IBIG ID" {{ $gid == 'Pag-IBIG ID' ? 'selected' : '' }}>Pag-IBIG (HDMF) ID / Loyalty Card</option>
                        <option value="PhilHealth ID" {{ $gid == 'PhilHealth ID' ? 'selected' : '' }}>PhilHealth Healthpass / ID</option>
                        <option value="Voter's ID" {{ $gid == "Voter's ID" ? 'selected' : '' }}>Voter's ID / Voter Certification</option>
                        <option value="Driver's License" {{ $gid == "Driver's License" ? 'selected' : '' }}>Driver's License (LTO)</option>
                        <option value="Passport" {{ $gid == 'Passport' ? 'selected' : '' }}>Philippine Passport (DFA)</option>
                        <option value="Senior Citizen ID" {{ $gid == 'Senior Citizen ID' ? 'selected' : '' }}>Senior Citizen ID</option>
                        <option value="PWD ID" {{ $gid == 'PWD ID' ? 'selected' : '' }}>Person with Disability (PWD) ID</option>
                        <option value="Postal ID" {{ $gid == 'Postal ID' ? 'selected' : '' }}>Postal ID</option>
                        <option value="Barangay ID" {{ $gid == 'Barangay ID' ? 'selected' : '' }}>Barangay ID / Barangay Clearance</option>
                        <option value="PRC ID" {{ $gid == 'PRC ID' ? 'selected' : '' }}>Professional Regulation Commission (PRC) ID</option>
                        <option value="OWWA ID" {{ $gid == 'OWWA ID' ? 'selected' : '' }}>OWWA / E-Card ID</option>
                        <option value="Solo Parent ID" {{ $gid == 'Solo Parent ID' ? 'selected' : '' }}>Solo Parent ID</option>
                        <option value="NBI Clearance" {{ $gid == 'NBI Clearance' ? 'selected' : '' }}>NBI Clearance</option>
                        <option value="Police Clearance" {{ $gid == 'Police Clearance' ? 'selected' : '' }}>Police Clearance</option>
                        <option value="Student ID" {{ $gid == 'Student ID' ? 'selected' : '' }}>Student / School ID</option>
                        <option value="Other ID" {{ $gid == 'Other ID' ? 'selected' : '' }}>Other Government Valid ID</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Government ID Number</label>
                    <input type="text" name="government_id_number" value="{{ old('government_id_number', $beneficiary->government_id_number) }}" placeholder="ID Number"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-6 border-t border-slate-200 pt-4">
                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
                    <input type="checkbox" name="is_pwd" value="1" {{ old('is_pwd', $beneficiary->is_pwd) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                    Person with Disability (PWD)
                </label>
                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
                    <input type="checkbox" name="is_student" value="1" {{ old('is_student', $beneficiary->is_student) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                    Currently Enrolled Student
                </label>
                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
                    <input type="checkbox" name="is_government_employee" value="1" {{ old('is_government_employee', $beneficiary->is_government_employee) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                    Government Employee
                </label>
                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
                    <input type="checkbox" name="is_graduating_college" value="1" {{ old('is_graduating_college', $beneficiary->is_graduating_college) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                    Graduating College Student
                </label>
            </div>
        </div>

        {{-- Form Buttons --}}
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('beneficiaries.show', $beneficiary) }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 transition">
                Cancel
            </a>
            <button type="submit" class="rounded-xl bg-blue-800 px-6 py-2.5 text-sm font-extrabold text-white shadow-md hover:bg-blue-900 transition cursor-pointer">
                Save Profile Changes
            </button>
        </div>
    </form>

    {{-- SECTION 3: PROGRAM AVAILMENT HISTORY --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs mt-8">
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 pb-4 gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-700 font-extrabold text-white text-base shadow-md">
                    3
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Program Availment History</h3>
                    <p class="text-xs font-medium text-slate-600">Active and past DOLE program grants attached to this master profile</p>
                </div>
            </div>

            @hasanyrole('Admin|Encoder|Validator')
            <button type="button" @click="openAddAvailmentModal()"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-700 px-4 py-2 text-xs font-extrabold text-white shadow-md hover:bg-indigo-800 transition cursor-pointer">
                <svg class="h-4 w-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                + Add New Program Availment
            </button>
            @endhasanyrole
        </div>

        {{-- Availments Table --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-700 font-bold uppercase">
                    <tr>
                        <th class="px-4 py-3">Program Grant</th>
                        <th class="px-4 py-3">Availment Year</th>
                        <th class="px-4 py-3">Type / Details</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white font-medium">
                    @forelse($beneficiary->beneficiaryPrograms as $bp)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center rounded-lg bg-blue-100 border border-blue-200 px-2.5 py-1 text-xs font-extrabold text-blue-900">
                                        {{ $bp->program?->code }}
                                    </span>
                                    <span class="font-bold text-slate-900">{{ $bp->program?->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 font-extrabold text-slate-900">
                                {{ $bp->availment_year }}
                                @if($bp->is_calamity_override)
                                    <span class="ml-1.5 inline-flex rounded bg-rose-100 text-rose-800 px-1.5 py-0.5 text-[10px] font-bold" title="{{ $bp->calamity_remarks ?? 'Emergency Override' }}">
                                        Calamity Override
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-slate-600">
                                @if($bp->program?->code === 'GIP')
                                    <span>Duration: <strong>{{ $bp->internship_duration === '1_year' ? '1 Year' : '6 Months' }}</strong></span>
                                @elseif($bp->program?->code === 'DILP')
                                    <span>Type: <strong>{{ ucfirst($bp->enrollment_type ?? 'individual') }}</strong></span>
                                    @if($bp->dilpGroup)
                                        <span class="block text-[11px] text-slate-500">Group: {{ $bp->dilpGroup->name }}</span>
                                    @endif
                                @elseif($bp->program?->code === 'TUPAD')
                                    <span>Community Work</span>
                                @else
                                    <span>Standard</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                @if(in_array($bp->status, ['approved', 'active']))
                                    <span class="inline-flex rounded-full bg-emerald-100 border border-emerald-200 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800">
                                        Approved / Active
                                    </span>
                                @elseif($bp->status === 'completed')
                                    <span class="inline-flex rounded-full bg-blue-100 border border-blue-200 px-2.5 py-0.5 text-[11px] font-bold text-blue-800">
                                        Completed
                                    </span>
                                @elseif($bp->status === 'pending')
                                    <span class="inline-flex rounded-full bg-amber-100 border border-amber-200 px-2.5 py-0.5 text-[11px] font-bold text-amber-800">
                                        Pending Review
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 border border-slate-300 px-2.5 py-0.5 text-[11px] font-bold text-slate-600">
                                        {{ ucfirst($bp->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex justify-end items-center gap-2">
                                    <button type="button" @click="openEditAvailmentModal({{ json_encode($bp) }})"
                                            class="rounded-lg p-1.5 text-blue-700 hover:bg-blue-50 transition cursor-pointer" title="Edit Program Details">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                    </button>

                                    <form action="{{ route('availments.destroy', $bp) }}" method="POST"
                                          data-confirm="Are you sure you want to remove {{ $bp->program?->code }} ({{ $bp->availment_year }}) from this beneficiary?"
                                          data-confirm-title="Remove Program Availment"
                                          data-confirm-btn="Yes, Remove Grant"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-red-600 hover:bg-red-50 transition cursor-pointer" title="Remove Availment">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400 font-semibold">
                                No program availments recorded yet. Click <strong>+ Add New Program Availment</strong> above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL 1: ADD NEW AVAILMENT --}}
    <div x-show="addAvailmentModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm overflow-hidden"
         style="display: none;">

        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-indigo-200 overflow-hidden"
             @click.away="addAvailmentModal = false">
            <form action="{{ route('beneficiaries.availments.store', $beneficiary) }}" method="POST">
                @csrf
                <div class="flex items-center justify-between border-b border-slate-200 p-5 bg-white">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-700 text-white font-extrabold text-sm">
                            +
                        </div>
                        <h4 class="font-extrabold text-slate-900 text-sm">Add Program Availment</h4>
                    </div>
                    <button type="button" @click="addAvailmentModal = false" class="text-slate-400 hover:text-slate-700">✕</button>
                </div>

                <div class="p-5 space-y-4 text-xs font-semibold text-slate-800">
                    <div>
                        <label class="mb-1 block font-bold text-slate-800">Select Program *</label>
                        <select name="program_code" x-model="newProgramCode" required
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-indigo-600 focus:outline-none">
                            <option value="">Select Program</option>
                            @foreach($programs as $prog)
                                <option value="{{ $prog->code }}">{{ $prog->name }} ({{ $prog->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block font-bold text-slate-800">Availment Year *</label>
                            <input type="number" name="availment_year" value="{{ date('Y') }}" min="2020" max="{{ date('Y') + 1 }}" required
                                   class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-indigo-600 focus:outline-none">
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-slate-800">Status *</label>
                            <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-indigo-600 focus:outline-none">
                                <option value="approved">Approved / Active</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    {{-- GIP Fields --}}
                    <div x-show="newProgramCode === 'GIP'">
                        <label class="mb-1 block font-bold text-slate-800">Internship Duration</label>
                        <select name="internship_duration" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900">
                            <option value="6_months">6 Months (High School / Technical Graduate)</option>
                            <option value="1_year">1 Year / 12 Months (College Graduate)</option>
                        </select>
                    </div>

                    {{-- DILP Fields --}}
                    <div x-show="newProgramCode === 'DILP'" class="space-y-3">
                        <div>
                            <label class="mb-1 block font-bold text-slate-800">Enrollment Type</label>
                            <select name="enrollment_type" x-model="newDilpType" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900">
                                <option value="individual">Individual Grant</option>
                                <option value="group">Group / Associated Co-Partner</option>
                            </select>
                        </div>

                        <div x-show="newDilpType === 'group'">
                            <label class="mb-1 block font-bold text-slate-800">Associated Group / ACP</label>
                            <select name="dilp_group_id" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900">
                                <option value="">Select DILP Group</option>
                                @foreach($dilpGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->municipality }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Calamity Override --}}
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 space-y-2">
                        <label class="flex items-center gap-2 font-bold text-slate-900 cursor-pointer">
                            <input type="checkbox" name="is_calamity_override" value="1" x-model="newCalamityOverride" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            Emergency / Calamity Override
                        </label>
                        <div x-show="newCalamityOverride">
                            <input type="text" name="calamity_remarks" placeholder="Enter reason (e.g. Typhoon relief)..."
                                   class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-900">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 p-4 bg-slate-50">
                    <button type="button" @click="addAvailmentModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-indigo-700 px-5 py-2 text-xs font-extrabold text-white shadow hover:bg-indigo-800 transition">Attach Program</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: EDIT AVAILMENT --}}
    <div x-show="editAvailmentModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm overflow-hidden"
         style="display: none;">

        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
             @click.away="editAvailmentModal = false">
            <form :action="editAvailmentActionUrl" method="POST">
                @csrf
                @method('PUT')
                <div class="flex items-center justify-between border-b border-slate-200 p-5 bg-white">
                    <h4 class="font-extrabold text-slate-900 text-sm">
                        Edit Availment: <span x-text="activeAvailment ? activeAvailment.program?.code : ''" class="text-blue-700"></span>
                    </h4>
                    <button type="button" @click="editAvailmentModal = false" class="text-slate-400 hover:text-slate-700">✕</button>
                </div>

                <div class="p-5 space-y-4 text-xs font-semibold text-slate-800">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block font-bold text-slate-800">Availment Year *</label>
                            <input type="number" name="availment_year" x-model="editAvailmentYear" min="2020" max="{{ date('Y') + 1 }}" required
                                   class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-bold text-slate-900">
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-slate-800">Status *</label>
                            <select name="status" x-model="editAvailmentStatus" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900">
                                <option value="approved">Approved / Active</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <template x-if="activeAvailment && activeAvailment.program?.code === 'GIP'">
                        <div>
                            <label class="mb-1 block font-bold text-slate-800">Internship Duration</label>
                            <select name="internship_duration" x-model="editAvailmentDuration" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900">
                                <option value="6_months">6 Months</option>
                                <option value="1_year">1 Year / 12 Months</option>
                            </select>
                        </div>
                    </template>

                    <div>
                        <label class="mb-1 block font-bold text-slate-800">Availment Remarks</label>
                        <input type="text" name="remarks" x-model="editAvailmentRemarks" placeholder="Optional notes..."
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-900">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 p-4 bg-slate-50">
                    <button type="button" @click="editAvailmentModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-blue-800 px-5 py-2 text-xs font-extrabold text-white shadow hover:bg-blue-900 transition">Update Availment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editBeneficiaryForm() {
        const initialMuni = @json(old('municipality', $beneficiary->municipality ?? ''));
        const initialBrgy = @json(old('barangay', $beneficiary->barangay ?? ''));
        const initialAddr = @json(old('address', $beneficiary->address ?? ''));

        const standardPuroks = [
            'Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7', 'Purok 8', 'Purok 9', 'Purok 10', 'Purok 11', 'Purok 12',
            'Purok 1A', 'Purok 1B', 'Purok 2A', 'Purok 2B', 'Purok 3A', 'Purok 3B', 'Purok Centro', 'Zone 1', 'Zone 2', 'Zone 3', 'Zone 4', 'Zone 5', 'Zone 6'
        ];

        let selectedPurokVal = '';
        let customAddressVal = '';
        let customAddressModeVal = false;

        if (initialAddr) {
            if (standardPuroks.includes(initialAddr)) {
                selectedPurokVal = initialAddr;
            } else {
                selectedPurokVal = '__OTHER__';
                customAddressVal = initialAddr;
                customAddressModeVal = true;
            }
        }

        return {
            municipality: initialMuni,
            selectedBarangay: initialBrgy,
            customBarangayInput: '',
            customBarangayMode: false,
            availableBarangays: [],
            selectedPurok: selectedPurokVal,
            customAddressInput: customAddressVal,
            customAddressMode: customAddressModeVal,

            // Availment Modals State
            addAvailmentModal: false,
            editAvailmentModal: false,
            newProgramCode: '',
            newDilpType: 'individual',
            newCalamityOverride: false,

            activeAvailment: null,
            editAvailmentYear: '',
            editAvailmentStatus: '',
            editAvailmentDuration: '',
            editAvailmentRemarks: '',
            editAvailmentActionUrl: '',

            openAddAvailmentModal() {
                this.newProgramCode = '';
                this.newDilpType = 'individual';
                this.newCalamityOverride = false;
                this.addAvailmentModal = true;
            },

            openEditAvailmentModal(availment) {
                this.activeAvailment = availment;
                this.editAvailmentYear = availment.availment_year;
                this.editAvailmentStatus = availment.status;
                this.editAvailmentDuration = availment.internship_duration || '6_months';
                this.editAvailmentRemarks = availment.remarks || '';
                this.editAvailmentActionUrl = `/availments/${availment.id}`;
                this.editAvailmentModal = true;
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
                if (this.municipality && this.barangaysByMunicipality[this.municipality]) {
                    this.availableBarangays = this.barangaysByMunicipality[this.municipality];
                    if (initialBrgy && !this.availableBarangays.includes(initialBrgy)) {
                        this.selectedBarangay = '__OTHER__';
                        this.customBarangayInput = initialBrgy;
                        this.customBarangayMode = true;
                    }
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
            }
        }
    }
</script>
@endsection
