{{-- resources/views/crm/hrd/employees/create-wizard.blade.php --}}
@extends('layouts.app')

{{-- Define dropdown data in parent scope for children to access --}}
@php
    // Dropdown data from controller
    $wizardDepartments = $departments ?? collect();
    $wizardDivisions = $divisions ?? collect();
    $wizardPositions = $positions ?? collect();
    $wizardPlacements = $placements ?? collect();
    $wizardShifts = $shifts ?? collect();
    $wizardLeaveTypes = $leaveTypes ?? collect();
    $wizardSupervisors = $supervisors ?? collect();
@endphp

@section('title', 'Tambah Karyawan Baru')

@section('page-title')
    <i class="fa-solid fa-user-plus mr-2"></i>Tambah Karyawan Baru
@endsection
@section('page-subtitle', 'Lengkapi data karyawan dalam beberapa langkah')

@section('content')
<div class="max-w-5xl mx-auto" x-data="wizardData()">

    {{-- Stepper Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="hidden md:block">
            <div class="flex items-center justify-between gap-2">
                <template x-for="(step, idx) in steps" :key="idx">
                    <div class="flex items-center" :class="idx < steps.length - 1 ? 'flex-1' : ''">
                        <div class="flex flex-col items-center cursor-pointer group" @click="goToStep(idx)">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 border-2"
                                :class="getStepClass(idx)">
                                <template x-if="currentStep > idx">
                                    <i class="fa-solid fa-check text-sm"></i>
                                </template>
                                <template x-if="currentStep <= idx">
                                    <span x-text="idx + 1"></span>
                                </template>
                            </div>
                            <span class="mt-2 text-xs font-medium text-center max-w-[90px] transition-colors"
                                :class="currentStep === idx ? 'text-indigo-600 font-semibold' : 'text-gray-500 group-hover:text-gray-700'"
                                x-text="step.label"></span>
                        </div>
                        <template x-if="idx < steps.length - 1">
                            <div class="flex-1 mx-3">
                                <div class="h-0.5 rounded-full transition-colors duration-300"
                                    :class="currentStep > idx ? 'bg-green-500' : 'bg-gray-200'"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="hidden md:block mt-6 pt-4 border-t border-gray-100">
            <div class="text-center">
                <p class="text-sm text-indigo-600 font-medium" x-text="'Langkah ' + (currentStep + 1) + ' dari ' + steps.length"></p>
                <h2 class="text-xl font-bold text-gray-900 mt-1" x-text="steps[currentStep].label"></h2>
            </div>
        </div>

        <div class="md:hidden">
            <div class="flex items-center justify-between mb-3">
                <button @click="prevStep()" :disabled="currentStep === 0"
                    class="p-2 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    :class="currentStep === 0 ? 'text-gray-300' : 'text-indigo-600 hover:bg-indigo-50'">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <div class="text-center">
                    <p class="text-sm text-indigo-600 font-semibold" x-text="'Langkah ' + (currentStep + 1) + ' dari ' + steps.length"></p>
                    <p class="text-base font-bold text-gray-900" x-text="steps[currentStep].label"></p>
                </div>
                <button @click="nextStep()" :disabled="currentStep === steps.length - 1"
                    class="p-2 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    :class="currentStep === steps.length - 1 ? 'text-gray-300' : 'text-indigo-600 hover:bg-indigo-50'">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Validation Error Messages --}}
    <div x-show="validationErrors.length > 0" x-transition class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-exclamation-circle text-red-500 mt-0.5"></i>
            <div>
                <p class="font-medium text-red-800">Mohon lengkapi field yang wajib:</p>
                <ul class="mt-1 text-sm text-red-700 space-y-1">
                    <template x-for="error in validationErrors" :key="error">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('administrasi.data_karyawan.wizard.store') }}" method="POST">
        @csrf

        <div x-show="currentStep === 0" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            @include('crm.hrd.employees.partials.wizard-step-1-personal')
        </div>

        <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
            @include('crm.hrd.employees.partials.wizard-step-2-employment')
        </div>

        <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
            @include('crm.hrd.employees.partials.wizard-step-3-placement')
        </div>

        {{-- Step 4: Penggajian --}}
        <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
            @include('crm.hrd.employees.partials.wizard-step-6-payroll')
        </div>

        {{-- Step 5: Review --}}
        <div x-show="currentStep === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
            @include('crm.hrd.employees.partials.wizard-step-7-review')
        </div>

        {{-- Step 6: Hak Akses Sidebar --}}
        <div x-show="currentStep === 5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
            @include('crm.hrd.employees.partials.wizard-step-8-sidebar-permissions')
        </div>

        <div class="mt-6 flex justify-between items-center gap-4">
            <button type="button" @click="prevStep()" x-show="currentStep > 0"
                class="px-6 py-2.5 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 flex items-center transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Sebelumnya
            </button>
            <div x-show="currentStep === 0" class="flex-1"></div>

            <button type="button" @click="nextStep()" x-show="currentStep < steps.length - 1"
                class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center transition-colors shadow-sm">
                Selanjutnya
                <i class="fa-solid fa-arrow-right ml-2"></i>
            </button>

            <button type="submit" x-show="currentStep === steps.length - 1"
                class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center transition-colors shadow-sm">
                <i class="fa-solid fa-save mr-2"></i>
                Simpan Karyawan
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function wizardData() {
    return {
        currentStep: 0,
        validationErrors: [],
        isNavigatingFromReview: false,

        steps: [
            { label: 'Data Pribadi' },
            { label: 'Pekerjaan' },
            { label: 'Penempatan' },
            { label: 'Penggajian' },
            { label: 'Review' },
            { label: 'Hak Akses Sidebar' }
        ],

        init: function() {
            var self = this;
            // Expose wizard data to window for child components (like Review)
            window.wizardData = this;
            // Listen for step change events from Review component
            window.addEventListener('wizard-step-change', function(e) {
                self.navigateToStep(e.detail);
            });
        },

        navigateToStep: function(step) {
            // Allow going backward without validation (from Review Edit buttons)
            if (step < this.currentStep) {
                this.validationErrors = [];
                var els = document.querySelectorAll('.border-red-500');
                for (var i = 0; i < els.length; i++) {
                    els[i].classList.remove('border-red-500');
                }
                this.currentStep = step;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                window.dispatchEvent(new CustomEvent('wizard-step-change', { detail: step }));
                return;
            }

            // Allow going forward only with validation (from Next button)
            if (step > this.currentStep) {
                if (!this.validateStep(this.currentStep)) {
                    document.querySelector('.max-w-5xl').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    return;
                }

                if (this.currentStep < this.steps.length - 1) {
                    this.currentStep = step;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    window.dispatchEvent(new CustomEvent('wizard-step-change', { detail: step }));
                }
            }
        },

        formData: {
            placement: {
                skip: {{ old('placement.skip', 'false') }},
                placement_id: '{{ old('placement.placement_id', '') }}',
                start_date: '{{ old('placement.start_date', now()->format('Y-m-d')) }}',
                notes: '{{ old('placement.notes', '') }}'
            },
            payroll: {
                skip: {{ old('payroll.skip', 'false') }},
                basic_salary: 0,
                basic_salary_display: '',
                payment_method: 'transfer',
                bank_name: '',
                bank_account: '',
                bank_account_name: '',
                allowances: [],
                deductions: []
            }
        },

        get payrollPreview() {
            const basic = this.formData.payroll.basic_salary || 0;
            const allowances = this.formData.payroll.allowances.reduce(function(sum, a) { return sum + (parseFloat(a.amount) || 0); }, 0);
            const deductions = this.formData.payroll.deductions.reduce(function(sum, d) { return sum + (parseFloat(d.amount) || 0); }, 0);
            return {
                basic: basic,
                totalAllowances: allowances,
                totalDeductions: deductions,
                net: basic + allowances - deductions
            };
        },

        getStepClass: function(idx) {
            if (this.currentStep === idx) {
                return 'bg-indigo-600 text-white border-indigo-600';
            }
            if (this.currentStep > idx) {
                return 'bg-green-500 text-white border-green-500';
            }
            return 'bg-white text-gray-400 border-gray-300 hover:border-indigo-400 hover:text-indigo-500';
        },

        validateStep1: function() {
            this.validationErrors = [];
            var errs = [];
            var firstErrorField = null;

            var clearErrors = function() {
                var els = document.querySelectorAll('.border-red-500');
                for (var i = 0; i < els.length; i++) {
                    els[i].classList.remove('border-red-500');
                    els[i].classList.add('border-gray-300');
                }
                var errMsgs = document.querySelectorAll('.validation-error-msg');
                for (var j = 0; j < errMsgs.length; j++) {
                    errMsgs[j].remove();
                }
            };

            var addError = function(input, message) {
                if (input) {
                    errs.push(message);
                    input.classList.add('border-red-500');
                    input.classList.remove('border-gray-300');
                    if (!firstErrorField) {
                        firstErrorField = input;
                    }
                    // Remove existing error message
                    var existingMsg = input.parentElement.querySelector('.validation-error-msg');
                    if (existingMsg) existingMsg.remove();
                    // Add new error message
                    var msgEl = document.createElement('p');
                    msgEl.className = 'mt-1 text-sm text-red-500 validation-error-msg';
                    msgEl.textContent = message;
                    input.parentElement.appendChild(msgEl);
                }
            };

            var clearFieldError = function(input) {
                if (input) {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-gray-300');
                    var msg = input.parentElement.querySelector('.validation-error-msg');
                    if (msg) msg.remove();
                }
            };

            clearErrors();

            // ========== A. AKUN LOGIN ==========
            var nameInput = document.querySelector('input[name="user[name]"]');
            var emailInput = document.querySelector('input[name="user[email]"]');
            var passwordInput = document.querySelector('input[name="user[password]"]');

            // Username
            if (!nameInput || !nameInput.value.trim()) {
                addError(nameInput, 'Username wajib diisi');
            } else if (nameInput.value.trim().length < 4) {
                addError(nameInput, 'Username minimal 4 karakter');
            } else if (!/^[a-zA-Z0-9._]+$/.test(nameInput.value.trim())) {
                addError(nameInput, 'Username hanya boleh huruf, angka, titik, dan underscore');
            } else {
                clearFieldError(nameInput);
            }

            // Email
            if (!emailInput || !emailInput.value.trim()) {
                addError(emailInput, 'Email Login wajib diisi');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
                addError(emailInput, 'Format Email Login tidak valid');
            } else {
                clearFieldError(emailInput);
            }

            // Password
            if (!passwordInput || !passwordInput.value) {
                addError(passwordInput, 'Password wajib diisi');
            } else if (passwordInput.value.length < 6) {
                addError(passwordInput, 'Password minimal 6 karakter');
            } else {
                clearFieldError(passwordInput);
            }

            // ========== B. DATA PRIBADI KARYAWAN ==========
            var fullNameInput = document.querySelector('input[name="employee[full_name]"]');
            var nickNameInput = document.querySelector('input[name="employee[nick_name]"]');
            var phoneInput = document.querySelector('input[name="employee[phone]"]');
            var nikInput = document.querySelector('input[name="employee[nik]"]');
            var placeOfBirthInput = document.querySelector('input[name="employee[place_of_birth]"]');
            var dateOfBirthInput = document.querySelector('input[name="employee[date_of_birth]"]');
            var genderInput = document.querySelector('select[name="employee[gender]"]');
            var religionInput = document.querySelector('select[name="employee[religion]"]');
            var maritalStatusInput = document.querySelector('select[name="employee[marital_status]"]');
            var bloodTypeInput = document.querySelector('select[name="employee[blood_type]"]');
            var addressInput = document.querySelector('textarea[name="employee[address]"]');
            var cityInput = document.querySelector('input[name="employee[city]"]');
            var provinceInput = document.querySelector('input[name="employee[province]"]');
            var postalCodeInput = document.querySelector('input[name="employee[postal_code]"]');

            // Nama Lengkap
            if (!fullNameInput || !fullNameInput.value.trim()) {
                addError(fullNameInput, 'Nama Lengkap wajib diisi');
            } else {
                clearFieldError(fullNameInput);
            }

            // Nama Panggilan
            if (!nickNameInput || !nickNameInput.value.trim()) {
                addError(nickNameInput, 'Nama Panggilan wajib diisi');
            } else {
                clearFieldError(nickNameInput);
            }

            // Nomor Ponsel
            if (!phoneInput || !phoneInput.value.trim()) {
                addError(phoneInput, 'Nomor Ponsel wajib diisi');
            } else if (!/^[0-9]+$/.test(phoneInput.value.trim())) {
                addError(phoneInput, 'Nomor Ponsel hanya boleh angka');
            } else if (phoneInput.value.trim().length < 10) {
                addError(phoneInput, 'Nomor Ponsel minimal 10 digit');
            } else if (phoneInput.value.trim().length > 15) {
                addError(phoneInput, 'Nomor Ponsel maksimal 15 digit');
            } else {
                clearFieldError(phoneInput);
            }

            // NIK
            if (!nikInput || !nikInput.value.trim()) {
                addError(nikInput, 'NIK / No. KTP wajib diisi');
            } else if (nikInput.value.trim().length !== 16) {
                addError(nikInput, 'NIK harus tepat 16 digit');
            } else if (!/^[0-9]+$/.test(nikInput.value.trim())) {
                addError(nikInput, 'NIK hanya boleh angka');
            } else {
                clearFieldError(nikInput);
            }

            // Tempat Lahir
            if (!placeOfBirthInput || !placeOfBirthInput.value.trim()) {
                addError(placeOfBirthInput, 'Tempat Lahir wajib diisi');
            } else {
                clearFieldError(placeOfBirthInput);
            }

            // Tanggal Lahir
            if (!dateOfBirthInput || !dateOfBirthInput.value) {
                addError(dateOfBirthInput, 'Tanggal Lahir wajib diisi');
            } else {
                clearFieldError(dateOfBirthInput);
            }

            // Jenis Kelamin
            if (!genderInput || !genderInput.value) {
                addError(genderInput, 'Jenis Kelamin wajib dipilih');
            } else {
                clearFieldError(genderInput);
            }

            // Agama
            if (!religionInput || !religionInput.value) {
                addError(religionInput, 'Agama wajib dipilih');
            } else {
                clearFieldError(religionInput);
            }

            // Status Perkawinan
            if (!maritalStatusInput || !maritalStatusInput.value) {
                addError(maritalStatusInput, 'Status Perkawinan wajib dipilih');
            } else {
                clearFieldError(maritalStatusInput);
            }

            // Golongan Darah
            if (!bloodTypeInput || !bloodTypeInput.value) {
                addError(bloodTypeInput, 'Golongan Darah wajib dipilih');
            } else {
                clearFieldError(bloodTypeInput);
            }

            // Alamat
            if (!addressInput || !addressInput.value.trim()) {
                addError(addressInput, 'Alamat wajib diisi');
            } else {
                clearFieldError(addressInput);
            }

            // Kota/Kabupaten
            if (!cityInput || !cityInput.value.trim()) {
                addError(cityInput, 'Kota/Kabupaten wajib diisi');
            } else {
                clearFieldError(cityInput);
            }

            // Provinsi
            if (!provinceInput || !provinceInput.value.trim()) {
                addError(provinceInput, 'Provinsi wajib diisi');
            } else {
                clearFieldError(provinceInput);
            }

            // Kode Pos
            if (!postalCodeInput || !postalCodeInput.value.trim()) {
                addError(postalCodeInput, 'Kode Pos wajib diisi');
            } else if (!/^[0-9]+$/.test(postalCodeInput.value.trim())) {
                addError(postalCodeInput, 'Kode Pos hanya boleh angka');
            } else if (postalCodeInput.value.trim().length !== 5) {
                addError(postalCodeInput, 'Kode Pos harus 5 digit');
            } else {
                clearFieldError(postalCodeInput);
            }

            this.validationErrors = errs;

            if (errs.length > 0 && firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField.focus();
            }

            return errs.length === 0;
        },

        validateStep2: function() {
            this.validationErrors = [];
            var errs = [];
            var firstErrorField = null;

            var clearErrors = function() {
                var els = document.querySelectorAll('.border-red-500');
                for (var i = 0; i < els.length; i++) {
                    els[i].classList.remove('border-red-500');
                    els[i].classList.add('border-gray-300');
                }
                var errMsgs = document.querySelectorAll('.validation-error-msg');
                for (var j = 0; j < errMsgs.length; j++) {
                    errMsgs[j].remove();
                }
            };

            var addError = function(input, message) {
                if (input) {
                    errs.push(message);
                    input.classList.add('border-red-500');
                    input.classList.remove('border-gray-300');
                    if (!firstErrorField) {
                        firstErrorField = input;
                    }
                    var existingMsg = input.parentElement.querySelector('.validation-error-msg');
                    if (existingMsg) existingMsg.remove();
                    var msgEl = document.createElement('p');
                    msgEl.className = 'mt-1 text-sm text-red-500 validation-error-msg';
                    msgEl.textContent = message;
                    input.parentElement.appendChild(msgEl);
                }
            };

            var clearFieldError = function(input) {
                if (input) {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-gray-300');
                    var msg = input.parentElement.querySelector('.validation-error-msg');
                    if (msg) msg.remove();
                }
            };

            clearErrors();

            // ========== DATA PEKERJAAN ==========
            var departmentSelect = document.querySelector('select[name="employee[department_id]"]');
            var divisionSelect = document.querySelector('select[name="employee[division_id]"]');
            var positionSelect = document.querySelector('select[name="employee[position_id]"]');
            var supervisorSelect = document.querySelector('select[name="employee[supervisor_id]"]');
            var employmentTypeSelect = document.querySelector('select[name="employee[employment_type]"]');
            var contractStartInput = document.querySelector('input[name="employee[contract_start]"]');

            // Departemen
            if (!departmentSelect || !departmentSelect.value) {
                addError(departmentSelect, 'Departemen wajib dipilih');
            } else {
                clearFieldError(departmentSelect);
            }

            // Divisi
            if (!divisionSelect || !divisionSelect.value) {
                addError(divisionSelect, 'Divisi wajib dipilih');
            } else {
                clearFieldError(divisionSelect);
            }

            // Posisi/Jabatan
            if (!positionSelect || !positionSelect.value) {
                addError(positionSelect, 'Posisi/Jabatan wajib dipilih');
            } else {
                clearFieldError(positionSelect);
            }

            // Atasan Langsung
            if (!supervisorSelect || !supervisorSelect.value) {
                addError(supervisorSelect, 'Atasan Langsung wajib dipilih');
            } else {
                clearFieldError(supervisorSelect);
            }

            // Tipe Karyawan
            if (!employmentTypeSelect || !employmentTypeSelect.value) {
                addError(employmentTypeSelect, 'Tipe Karyawan wajib dipilih');
            } else {
                clearFieldError(employmentTypeSelect);
            }

            // Tanggal Mulai Kerja
            if (!contractStartInput || !contractStartInput.value) {
                addError(contractStartInput, 'Tanggal Mulai Kerja wajib diisi');
            } else {
                clearFieldError(contractStartInput);
            }

            this.validationErrors = errs;

            if (errs.length > 0 && firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField.focus();
            }

            return errs.length === 0;
        },

        validateStep3: function() {
            this.validationErrors = [];
            var errs = [];
            var firstErrorField = null;

            var clearErrors = function() {
                var els = document.querySelectorAll('.border-red-500');
                for (var i = 0; i < els.length; i++) {
                    els[i].classList.remove('border-red-500');
                    els[i].classList.add('border-gray-300');
                }
                var errMsgs = document.querySelectorAll('.validation-error-msg');
                for (var j = 0; j < errMsgs.length; j++) {
                    errMsgs[j].remove();
                }
            };

            var addError = function(input, message) {
                if (input) {
                    errs.push(message);
                    input.classList.add('border-red-500');
                    input.classList.remove('border-gray-300');
                    if (!firstErrorField) {
                        firstErrorField = input;
                    }
                    var existingMsg = input.parentElement.querySelector('.validation-error-msg');
                    if (existingMsg) existingMsg.remove();
                    var msgEl = document.createElement('p');
                    msgEl.className = 'mt-1 text-sm text-red-500 validation-error-msg';
                    msgEl.textContent = message;
                    input.parentElement.appendChild(msgEl);
                }
            };

            var clearFieldError = function(input) {
                if (input) {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-gray-300');
                    var msg = input.parentElement.querySelector('.validation-error-msg');
                    if (msg) msg.remove();
                }
            };

            clearErrors();

            // ========== PENEMPATAN ==========
            var placementSelect = document.querySelector('select[name="placement[placement_id]"]');
            var startDateInput = document.querySelector('input[name="placement[start_date]"]');

            // Lokasi Penempatan
            if (!placementSelect || !placementSelect.value) {
                addError(placementSelect, 'Lokasi Penempatan wajib dipilih');
            } else {
                clearFieldError(placementSelect);
            }

            // Tanggal Mulai Penempatan
            if (!startDateInput || !startDateInput.value) {
                addError(startDateInput, 'Tanggal Mulai Penempatan wajib diisi');
            } else {
                clearFieldError(startDateInput);
            }

            this.validationErrors = errs;

            if (errs.length > 0 && firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField.focus();
            }

            return errs.length === 0;
        },

        validateStep: function(step) {
            var els = document.querySelectorAll('.border-red-500');
            for (var i = 0; i < els.length; i++) {
                els[i].classList.remove('border-red-500');
                els[i].classList.add('border-gray-300');
            }

            if (step === 0) {
                return this.validateStep1();
            }
            if (step === 1) {
                return this.validateStep2();
            }
            if (step === 2) {
                return this.validateStep3();
            }
            return true;
        },

        nextStep: function() {
            var nextStepNum = this.currentStep + 1;
            if (nextStepNum < this.steps.length) {
                this.navigateToStep(nextStepNum);
            }
        },

        prevStep: function() {
            this.navigateToStep(this.currentStep - 1);
        },

        goToStep: function(step) {
            this.navigateToStep(step);
        }
    };
}

document.addEventListener('alpine:init', function() {
    Alpine.data('togglePassword', function() {
        return {
            showPassword: false,
            toggle: function() {
                this.showPassword = !this.showPassword;
                var input = document.getElementById('password-input');
                if (input) {
                    input.type = this.showPassword ? 'text' : 'password';
                }
            }
        };
    });
});
</script>
@endpush
