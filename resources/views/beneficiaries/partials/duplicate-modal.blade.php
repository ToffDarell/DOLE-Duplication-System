{{-- Duplicate Flag / Match Warning Modal --}}
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
                    <h3 class="text-base font-extrabold text-slate-900" x-text="isReturningBeneficiary ? 'Existing Master Profile Found' : (isHouseholdLimit ? 'House Number / Address Match Warning' : (isSameYearConflict ? 'Annual Limit / Duplicate Availment Detected' : (isEligibilityConflict ? 'Eligibility Restriction Detected' : 'Potential Duplicate Detected!')))"></h3>
                    <p class="text-xs font-semibold text-slate-600 line-clamp-1">
                        <template x-if="isReturningBeneficiary">
                            <span>Beneficiary record already exists. You can link this new availment.</span>
                        </template>
                        <template x-if="!isReturningBeneficiary && isHouseholdLimit">
                            <span>Another resident at this exact House Number / address and barangay has already availed.</span>
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
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-rose-950 uppercase tracking-wide">
                                House Number / Address Limit Warning
                            </h4>
                            <p class="mt-1 text-xs font-semibold text-rose-900 leading-relaxed">
                                A relative or resident with the same physical House Number / Address in this Barangay is already enrolled in this TUPAD cycle. Under DOLE guidelines, TUPAD assistance is limited to 1 worker per house number / family unit per cycle.
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
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-rose-950 uppercase tracking-wide">
                                Annual Program Limit Detected
                            </h4>
                            <p class="mt-1 text-xs font-semibold text-rose-900 leading-relaxed">
                                This beneficiary already has a recorded availment for <span class="font-extrabold" x-text="targetAvailmentYear"></span> in this program. DOLE circulars limit regular participation to once per calendar year.
                            </p>
                            <p class="mt-1 text-xs font-medium text-rose-800">
                                In case of emergency calamity response (e.g. Typhoon/Disaster), check "Calamity / Emergency Override" in Availment Details to permit emergency re-enrollment.
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Eligibility Rule Restriction Banner --}}
            <template x-if="isEligibilityConflict && !isReturningBeneficiary && !isHouseholdLimit && !isSameYearConflict">
                <div class="rounded-xl border border-rose-300 bg-rose-50 p-4 shadow-2xs">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white font-extrabold text-base shadow-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-rose-950 uppercase tracking-wide">
                                Program Eligibility Restriction
                            </h4>
                            <p class="mt-1 text-xs font-semibold text-rose-900 leading-relaxed" x-text="eligibilityErrorMessage || 'The applicant does not meet standard eligibility requirements for this program.'"></p>
                            <p class="mt-1 text-xs font-medium text-rose-800">
                                Please review program qualification rules (e.g. age limits, government employee exclusions, student enrollment rules).
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Same Name Record Detected Banner --}}
            <template x-if="isSameNameDiffIdentity && !isReturningBeneficiary && !isHouseholdLimit && !isSameYearConflict">
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 shadow-2xs">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white font-extrabold text-base shadow-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-amber-950 uppercase tracking-wide">
                                Name Similarity / Sibling Warning (<span x-text="matchedScoreText"></span>)
                            </h4>
                            <p class="mt-1 text-xs font-semibold text-amber-900 leading-relaxed">
                                This applicant shares an identical or near-identical name with an existing registered beneficiary, but has a <strong>different Date of Birth or Address</strong>.
                            </p>
                            <p class="mt-1 text-xs font-medium text-amber-800">
                                If this is a sibling or distinct person with a coincidentally similar name, verify their valid government ID and approve below.
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Potential Matches List (Alpine & Blade Fallbacks) --}}
            <div class="space-y-3">
                <template x-for="match in duplicateMatches" :key="match.matched_beneficiary_id || match.id">
                    <div class="rounded-xl border p-4 shadow-2xs"
                         :class="match.is_returning_beneficiary ? 'border-blue-200 bg-blue-50/70' : ((isSameYearConflict || isHouseholdLimit) ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50')">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold uppercase text-slate-800"
                                  x-text="match.is_returning_beneficiary ? 'Existing Masterlist Record' : 'Potential Match in Database'"></span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-extrabold text-white"
                                  :class="match.is_returning_beneficiary ? 'bg-blue-700' : ((isSameYearConflict || isHouseholdLimit) ? 'bg-rose-600' : 'bg-amber-600')"
                                  x-text="(match.match_score || 0) + '% Match'"></span>
                        </div>
                        {{-- Candidate Full Name with robust fallback --}}
                        <p class="mt-2 text-sm font-extrabold text-slate-900"
                           x-text="match.matched_beneficiary_name || match.full_name || (match.matched_beneficiary ? (match.matched_beneficiary.full_name || (match.matched_beneficiary.last_name + ', ' + match.matched_beneficiary.first_name)) : '') || ((match.last_name && match.first_name) ? (match.last_name + ', ' + match.first_name) : 'Masterlist Record')"></p>
                        
                        {{-- Date of Birth with null-safety --}}
                        <p class="text-xs text-slate-600">DOB: 
                            <span class="font-bold text-slate-800" 
                                  x-text="match.existing_dob || match.date_of_birth || (match.matched_beneficiary ? (match.matched_beneficiary.date_of_birth || '') : '') || 'Not specified'"></span>
                        </p>
                        
                        {{-- Address / Purok with null-safety --}}
                        <p class="text-xs text-slate-600">House Number / Address: 
                            <span class="font-bold text-blue-900" 
                                  x-text="match.existing_address || match.address || (match.matched_beneficiary ? (match.matched_beneficiary.address || '') : '') || 'Not specified'"></span>
                        </p>
                        
                        {{-- Location (Barangay, Municipality) with null-safety --}}
                        <p class="text-xs text-slate-600">Barangay: 
                            <span class="font-semibold text-slate-800" 
                                  x-text="((match.existing_barangay || match.barangay || (match.matched_beneficiary ? match.matched_beneficiary.barangay : '')) ? ((match.existing_barangay || match.barangay || (match.matched_beneficiary ? match.matched_beneficiary.barangay : '')) + ', ' + (match.existing_municipality || match.municipality || (match.matched_beneficiary ? match.matched_beneficiary.municipality : ''))) : 'Not specified')"></span>
                        </p>
                    </div>
                </template>

                {{-- Server-rendered Blade Fallback (when duplicateMatches is empty or JS is disabled) --}}
                @if(isset($matches) && is_iterable($matches))
                    <div x-show="!duplicateMatches || duplicateMatches.length === 0" class="space-y-3">
                        @foreach($matches as $match)
                            <div class="rounded-xl border p-4 shadow-2xs border-amber-200 bg-amber-50">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold uppercase text-slate-800">
                                        Potential Match in Database
                                    </span>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-extrabold text-white bg-amber-600">
                                        {{ $match['match_score'] ?? 75 }}% Match
                                    </span>
                                </div>
                                <p class="mt-2 text-sm font-extrabold text-slate-900">
                                    {{ $match['matched_beneficiary_name'] ?? ($match['matched_beneficiary']->full_name ?? ($match['matched_beneficiary']['last_name'] . ', ' . $match['matched_beneficiary']['first_name'])) }}
                                </p>
                                <p class="text-xs text-slate-600">
                                    DOB: <span class="font-bold text-slate-800">{{ isset($match['existing_dob']) ? \Carbon\Carbon::parse($match['existing_dob'])->format('M d, Y') : 'Not specified' }}</span>
                                </p>
                                <p class="text-xs text-slate-600">
                                    House Number / Address: <span class="font-bold text-blue-900">{{ $match['existing_address'] ?? ($match['address'] ?? ($match['matched_beneficiary']->address ?? ($match['matched_beneficiary']['address'] ?? 'Not specified'))) }}</span>
                                </p>
                                <p class="text-xs text-slate-600">
                                    Barangay: <span class="font-semibold text-slate-800">
                                        @php
                                            $bBrgy = $match['existing_barangay'] ?? ($match['barangay'] ?? ($match['matched_beneficiary']->barangay ?? ($match['matched_beneficiary']['barangay'] ?? null)));
                                            $bMuni = $match['existing_municipality'] ?? ($match['municipality'] ?? ($match['matched_beneficiary']->municipality ?? ($match['matched_beneficiary']['municipality'] ?? null)));
                                        @endphp
                                        {{ $bBrgy ? ($bBrgy . ($bMuni ? ', ' . $bMuni : '')) : 'Not specified' }}
                                    </span>
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @hasanyrole('Admin|Validator')
            <div x-show="!isReturningBeneficiary" class="pt-1">
                <label class="mb-1.5 block text-xs font-bold text-slate-900">
                    <span x-text="isHouseholdLimit ? 'Validator House Number Verification Remarks (Required) *' : (isSameNameDiffIdentity ? 'Validator Verification Remarks (e.g., Verified distinct individual / sibling via ID) *' : (isSameYearConflict ? 'Validator / Calamity Override Remarks *' : 'Validator Remarks (Required to Approve) *'))"></span>
                </label>
                <textarea x-model="overrideRemarks" rows="2"
                          :placeholder="isHouseholdLimit ? 'e.g., Verified separate family unit residing in separate structure / house number in the same compound...' : (isSameNameDiffIdentity ? 'e.g., Verified distinct individual or sibling living in separate purok/home...' : 'State reason for approving/saving despite duplicate flag...')"
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
                    <span x-text="isHouseholdLimit ? 'Verify Separate House Number Unit & Approve' : (isSameNameDiffIdentity ? 'Verify as Distinct Person & Save' : 'Override & Proceed Registration')"></span>
                </button>
            </template>
            @endhasanyrole
        </div>
    </div>
</div>
