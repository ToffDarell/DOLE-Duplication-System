@extends('layouts.app')
@section('title', 'Edit ' . $beneficiary->full_name)
@section('page-title', 'Edit Beneficiary Profile')
@section('page-subtitle', 'Update demographic information, location, and identification details')

@section('content')
<div class="mx-auto max-w-4xl" x-data="editBeneficiaryForm()">
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
