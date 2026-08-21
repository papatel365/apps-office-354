{{-- resources/views/crm/hrd/employees/wizard.blade.php --}}
@extends('layouts.app')

{{-- Pass data from controller to Blade scope --}}
@php
    // Mode: create or edit
    $wizardMode = $mode ?? 'create';
    $isEdit = $wizardMode === 'edit';
    // CRITICAL: Define $employee for partials - partials use $employee not $employeeData
    $employee = $employee ?? null;
    $employeeData = $employee;  // Alias for JavaScript use

    // Dropdown data for cascading selects
    $wizardDepartments = $departments ?? collect();
    $wizardDivisions = $divisions ?? collect();
    $wizardPositions = $positions ?? collect();
    $wizardPlacements = $placements ?? collect();
    $wizardShifts = $shifts ?? collect();
    $wizardLeaveTypes = $leaveTypes ?? collect();
    $wizardEmployeeTypes = $employeeTypes ?? collect();
    $wizardSupervisors = $supervisors ?? collect();

    // JSON encode for JavaScript
    $wizardDepartmentsJson = $wizardDepartments->toJson(JSON_UNESCAPED_UNICODE);
    $wizardDivisionsJson = $wizardDivisions->toJson(JSON_UNESCAPED_UNICODE);
    $wizardPositionsJson = $wizardPositions->toJson(JSON_UNESCAPED_UNICODE);
    $wizardPlacementsJson = $wizardPlacements->toJson(JSON_UNESCAPED_UNICODE);
    $wizardShiftsJson = $wizardShifts->toJson(JSON_UNESCAPED_UNICODE);
    $wizardLeaveTypesJson = $wizardLeaveTypes->toJson(JSON_UNESCAPED_UNICODE);
    $wizardEmployeeTypesJson = $wizardEmployeeTypes->toJson(JSON_UNESCAPED_UNICODE);
    $wizardSupervisorsJson = $wizardSupervisors->toJson(JSON_UNESCAPED_UNICODE);

    // Employee data for edit mode (include relations)
    $employeeJson = 'null';
    if ($employeeData) {
        // Load salary components untuk preload payroll wizard
        $employeeData->load([
            'user',
            'department',
            'division',
            'position',
            'placement',
            'salaries' => fn($q) => $q->with('components')->orderBy('period_year', 'desc')->orderBy('period_month', 'desc')->limit(1)
        ]);
        $employeeJson = $employeeData->toJson(JSON_UNESCAPED_UNICODE);
    }

    // Page title based on mode
    if ($isEdit && $employeeData) {
        $pageTitle = 'Edit Karyawan - ' . $employeeData->full_name;
    } else {
        $pageTitle = 'Tambah Karyawan Baru';
    }

    // Helper function to get old value or employee value
    function wizardOld($key, $employee = null, $default = '') {
        if (old($key)) {
            return old($key);
        }
        if ($employee) {
            $keys = explode('.', $key);
            $value = $employee;
            foreach ($keys as $k) {
                if (is_object($value) && isset($value->{$k})) {
                    $value = $value->{$k};
                } elseif (is_array($value) && isset($value[$k])) {
                    $value = $value[$k];
                } else {
                    return $default;
                }
            }
            return $value ?? $default;
        }
        return $default;
    }
@endphp

@section('title', $pageTitle)

@section('page-title')
    @if($isEdit)
        <i class="fa-solid fa-user-edit mr-2"></i>Edit Karyawan
    @else
        <i class="fa-solid fa-user-plus mr-2"></i>Tambah Karyawan Baru
    @endif
@endsection

@section('page-subtitle')
    @if($isEdit)
        {{ $employeeData->full_name ?? '' }}
    @else
        Lengkapi data karyawan dalam beberapa langkah
    @endif
@endsection

@section('content')
<div class="max-w-5xl mx-auto" x-data="employeeWizard()" x-init="init()">

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
    <form :action="formAction" method="POST">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Step 1: Data Pribadi --}}
        <div x-show="currentStep === 0" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            @include('crm.hrd.employees.partials.wizard-step-1-personal')
        </div>

        {{-- Step 2: Pekerjaan --}}
        <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
            @include('crm.hrd.employees.partials.wizard-step-2-employment')
        </div>

        {{-- Step 3: Penempatan --}}
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

        {{-- Navigation Buttons --}}
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
                {{ $isEdit ? 'Update Karyawan' : 'Simpan Karyawan' }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function employeeWizard() {
    var _wizardMode = '{{ $wizardMode ? $wizardMode : 'create' }}';
    var _isEdit = {{ $isEdit ? 'true' : 'false' }};
    var _employeeData = {!! $employeeJson ? $employeeJson : 'null' !!};

    return {
        currentStep: 0,
        validationErrors: [],
        mode: _wizardMode,
        employee: _employeeData,
        isEdit: _isEdit,

        steps: [
            { label: 'Data Pribadi' },
            { label: 'Pekerjaan' },
            { label: 'Penempatan' },
            { label: 'Penggajian' },
            { label: 'Review' },
            { label: 'Hak Akses Sidebar' }
        ],

        formData: {
            placement: { skip: false, placement_id: '', start_date: '', notes: '' },
            payroll: {
                skip: false,
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

        departments: [],
        divisions: [],
        positions: [],
        supervisors: [],
        placements: [],
        shifts: [],
        leaveTypes: [],
        employeeTypes: [],

        selectedDepartment: '',
        selectedDivision: '',
        selectedPosition: '',
        selectedSupervisor: '',

        get payrollPreview() {
            const basic = this.formData.payroll.basic_salary || 0;
            const allowances = this.formData.payroll.allowances.reduce((sum, a) => sum + (parseFloat(a.amount) || 0), 0);
            const deductions = this.formData.payroll.deductions.reduce((sum, d) => sum + (parseFloat(d.amount) || 0), 0);
            return {
                basic: basic,
                totalAllowances: allowances,
                totalDeductions: deductions,
                net: basic + allowances - deductions
            };
        },

        get formAction() {
            if (this.isEdit && this.employee) {
                return '{{ route('administrasi.data_karyawan.wizard.update', ':id') }}'.replace(':id', this.employee.id);
            }
            return '{{ route('administrasi.data_karyawan.wizard.store') }}';
        },

        init() {
            console.log('[WIZARD] Mode:', this.mode);
            console.log('[WIZARD] Employee:', this.employee);
            console.log('[WIZARD] isEdit:', this.isEdit);

            // CRITICAL: Set window.wizardData FIRST, before any other initialization
            // This ensures all components can reference it
            window.wizardData = this;

            // Load dropdown data
            this.loadDropdownData();

            // Populate from employee data if in edit mode
            // NO setTimeout - direct synchronous call
            if (this.isEdit && this.employee) {
                this.populateFromEmployee();
            }

            // Listen for step changes from other components
            window.addEventListener('wizard-step-change', (e) => {
                this.navigateToStep(e.detail);
            });
        },

        loadDropdownData() {
            this.departments = {!! $wizardDepartmentsJson !!}.map(d => ({
                id: String(d.id),
                name: d.name,
                code: d.code || ''
            }));

            this.divisions = {!! $wizardDivisionsJson !!}.map(d => ({
                id: String(d.id),
                name: d.name,
                code: d.code || '',
                department_id: d.department_id ? String(d.department_id) : ''
            }));

            this.positions = {!! $wizardPositionsJson !!}.map(p => ({
                id: String(p.id),
                name: p.name,
                code: p.code || '',
                department_id: p.department_id ? String(p.department_id) : '',
                division_id: p.division_id ? String(p.division_id) : ''
            }));

            this.supervisors = {!! $wizardSupervisorsJson !!}.map(s => ({
                id: String(s.id),
                full_name: s.user ? s.user.name : (s.full_name || 'Unknown')
            }));

            this.placements = {!! $wizardPlacementsJson !!}.map(p => ({
                id: String(p.id),
                name: p.name,
                code: p.code || '',
                address: p.address || '',
                city: p.city || ''
            }));

            this.shifts = {!! $wizardShiftsJson !!}.map(s => ({
                id: String(s.id),
                name: s.name,
                code: s.code || '',
                start_time: s.start_time,
                end_time: s.end_time
            }));

            this.leaveTypes = {!! $wizardLeaveTypesJson !!}.map(l => ({
                id: String(l.id),
                name: l.name,
                code: l.code || '',
                color: l.color || '#10B981'
            }));

            this.employeeTypes = {!! $wizardEmployeeTypesJson !!}.map(t => ({
                id: String(t.id),
                name: t.name,
                code: t.code || '',
                color: t.color || '#6B7280'
            }));

            console.log('[WIZARD] Dropdowns loaded:', {
                departments: this.departments.length,
                divisions: this.divisions.length,
                positions: this.positions.length,
                supervisors: this.supervisors.length,
                placements: this.placements.length,
                employeeTypes: this.employeeTypes.length
            });
        },

        populateFromEmployee() {
            const emp = this.employee;
            if (!emp) {
                console.log('[WIZARD] populateFromEmployee: emp is null, skipping');
                return;
            }

            console.log('[WIZARD] populateFromEmployee: Starting');
            console.log('[WIZARD] Employee salaries:', emp.salaries);

            // User account
            if (emp.user) {
                const userNameInput = document.querySelector('input[name="user[name]"]');
                const userEmailInput = document.querySelector('input[name="user[email]"]');
                if (userNameInput) userNameInput.value = emp.user.name || '';
                if (userEmailInput) userEmailInput.value = emp.user.email || '';
            }

            // Format date helper
            const formatDateForInput = (dateValue) => {
                if (!dateValue) return '';
                if (typeof dateValue === 'string') {
                    const match = dateValue.match(/^(\d{4})-(\d{2})-(\d{2})/);
                    if (match) {
                        return `${match[1]}-${match[2]}-${match[3]}`;
                    }
                    if (dateValue.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        return dateValue;
                    }
                }
                return '';
            };

            const fullNameEl = document.querySelector('input[name="employee[full_name]"]');
            const nickNameEl = document.querySelector('input[name="employee[nick_name]"]');
            const phoneEl = document.querySelector('input[name="employee[phone]"]');
            const nikEl = document.querySelector('input[name="employee[nik]"]');
            const birthPlaceEl = document.querySelector('input[name="employee[place_of_birth]"]');
            const birthDateEl = document.querySelector('input[name="employee[date_of_birth]"]');
            const addressEl = document.querySelector('textarea[name="employee[address]"]');
            const cityEl = document.querySelector('input[name="employee[city]"]');
            const provinceEl = document.querySelector('input[name="employee[province]"]');
            const postalCodeEl = document.querySelector('input[name="employee[postal_code]"]');

            if (fullNameEl) fullNameEl.value = emp.full_name || '';
            if (nickNameEl) nickNameEl.value = emp.nick_name || '';
            if (phoneEl) phoneEl.value = emp.phone || '';
            if (nikEl) nikEl.value = emp.nik || emp.ktp_number || '';
            if (birthPlaceEl) birthPlaceEl.value = emp.birth_place || '';
            if (birthDateEl) birthDateEl.value = formatDateForInput(emp.birth_date);
            if (addressEl) addressEl.value = emp.address || '';
            if (cityEl) cityEl.value = emp.city || '';
            if (provinceEl) provinceEl.value = emp.province || '';
            if (postalCodeEl) postalCodeEl.value = emp.postal_code || '';

            // Selects
            const genderSelect = document.querySelector('select[name="employee[gender]"]');
            const religionSelect = document.querySelector('select[name="employee[religion]"]');
            const maritalSelect = document.querySelector('select[name="employee[marital_status]"]');
            const bloodSelect = document.querySelector('select[name="employee[blood_type]"]');

            if (genderSelect && emp.gender) genderSelect.value = emp.gender;
            if (religionSelect && emp.religion) religionSelect.value = emp.religion;
            if (maritalSelect && emp.marital_status) maritalSelect.value = emp.marital_status;
            if (bloodSelect && emp.blood_type) bloodSelect.value = emp.blood_type;

            // Employment data
            this.selectedDepartment = emp.department_id ? String(emp.department_id) : '';
            this.selectedDivision = emp.division_id ? String(emp.division_id) : '';
            this.selectedPosition = emp.position_id ? String(emp.position_id) : '';
            this.selectedSupervisor = emp.supervisor_id ? String(emp.supervisor_id) : '';

            const joinDateEl = document.querySelector('input[name="employee[join_date]"]');
            if (joinDateEl && emp.join_date) joinDateEl.value = formatDateForInput(emp.join_date);

            const contractEndEl = document.querySelector('input[name="employee[contract_end_date]"]');
            const contractEndDateEl = document.querySelector('input[name="employee[contract_end]"]');
            if (emp.contract_end_date) {
                const formattedContractEnd = formatDateForInput(emp.contract_end_date);
                if (contractEndEl) contractEndEl.value = formattedContractEnd;
                if (contractEndDateEl) contractEndDateEl.value = formattedContractEnd;
            }

            const empTypeSelect = document.querySelector('select[name="employee[employment_type]"]');
            if (empTypeSelect && emp.employment_type) empTypeSelect.value = emp.employment_type;

            // ============================================================
            // PLACEMENT DATA - SINGLE SOURCE OF TRUTH
            // All placement data comes from HERE - do NOT set elsewhere
            // ============================================================
            if (emp.placement_id) {
                this.formData.placement.placement_id = String(emp.placement_id);
            }
            // Notes - from employee's general notes field or placement notes
            if (emp.notes) {
                this.formData.placement.notes = emp.notes || '';
            }
            // Start date - use join_date as default if available
            if (emp.join_date) {
                this.formData.placement.start_date = emp.join_date;
            }

            // ============================================================
            // PAYROLL DATA - SINGLE SOURCE OF TRUTH
            // All payroll data comes from HERE - do NOT set elsewhere
            // ============================================================
            if (emp.salaries && emp.salaries.length > 0) {
                const latestSalary = emp.salaries[0];

                // Reset payroll data first
                this.formData.payroll.basic_salary = 0;
                this.formData.payroll.basic_salary_display = '';
                this.formData.payroll.allowances = [];
                this.formData.payroll.deductions = [];

                // Basic salary
                if (latestSalary.basic_salary) {
                    const rawSalary = parseFloat(latestSalary.basic_salary) || 0;
                    this.formData.payroll.basic_salary = rawSalary;
                    // Store formatted value for display - ONLY for input display
                    this.formData.payroll.basic_salary_display = rawSalary > 0 ? this.formatNumber(rawSalary) : '';
                }

                // Payment method and bank details
                this.formData.payroll.payment_method = latestSalary.payment_method || 'transfer';
                this.formData.payroll.bank_name = latestSalary.bank_name || '';
                this.formData.payroll.bank_account = latestSalary.bank_account_number || '';
                this.formData.payroll.bank_account_name = latestSalary.bank_account_holder || '';

                // Salary components (allowances and deductions)
                if (latestSalary.components && latestSalary.components.length > 0) {
                    latestSalary.components.forEach(comp => {
                        if (comp.type === 'allowance') {
                            this.formData.payroll.allowances.push({
                                name: comp.name || '',
                                type: comp.calculation_type || 'fixed',
                                amount: parseFloat(comp.amount) || 0,
                                amount_display: this.formatNumber(parseFloat(comp.amount) || 0)
                            });
                        } else if (comp.type === 'deduction') {
                            this.formData.payroll.deductions.push({
                                name: comp.name || '',
                                type: comp.calculation_type || 'fixed',
                                amount: parseFloat(comp.amount) || 0,
                                amount_display: this.formatNumber(parseFloat(comp.amount) || 0)
                            });
                        }
                    });
                }

                console.log('[WIZARD] Payroll populated:', {
                    basic_salary: this.formData.payroll.basic_salary,
                    basic_salary_display: this.formData.payroll.basic_salary_display,
                    payment_method: this.formData.payroll.payment_method,
                    allowances_count: this.formData.payroll.allowances.length,
                    deductions_count: this.formData.payroll.deductions.length
                });
            }

            console.log('[WIZARD] populateFromEmployee: Done');
        },

        getStepClass(idx) {
            if (this.currentStep === idx) {
                return 'bg-indigo-600 text-white border-indigo-600';
            }
            if (this.currentStep > idx) {
                return 'bg-green-500 text-white border-green-500';
            }
            return 'bg-white text-gray-400 border-gray-300 hover:border-indigo-400 hover:text-indigo-500';
        },

        navigateToStep(step) {
            if (step < this.currentStep) {
                this.validationErrors = [];
                this.clearErrorStyles();
                this.currentStep = step;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                window.dispatchEvent(new CustomEvent('wizard-step-change', { detail: step }));
                return;
            }

            if (step > this.currentStep) {
                if (!this.validateStep(this.currentStep)) {
                    document.querySelector('.max-w-5xl')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    return;
                }

                if (this.currentStep < this.steps.length - 1) {
                    this.currentStep = step;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    window.dispatchEvent(new CustomEvent('wizard-step-change', { detail: step }));
                }
            }
        },

        nextStep() {
            if (this.currentStep < this.steps.length - 1) {
                this.navigateToStep(this.currentStep + 1);
            }
        },

        prevStep() {
            if (this.currentStep > 0) {
                this.navigateToStep(this.currentStep - 1);
            }
        },

        goToStep(step) {
            this.navigateToStep(step);
        },

        validateStep(step) {
            this.clearErrorStyles();
            this.validationErrors = [];
            let errors = [];

            if (step === 0) {
                errors = this.validatePersonalData();
            } else if (step === 1) {
                errors = this.validateEmploymentData();
            } else if (step === 2) {
                errors = this.validatePlacementData();
            }

            this.validationErrors = errors;
            return errors.length === 0;
        },

        validatePersonalData() {
            const errors = [];
            const addError = (el, msg) => {
                if (el) {
                    errors.push(msg);
                    el.classList.add('border-red-500');
                }
            };

            const inputs = {
                'user[name]': 'Username wajib diisi',
                'user[email]': 'Email wajib diisi',
                'employee[full_name]': 'Nama Lengkap wajib diisi',
                'employee[phone]': 'Nomor Ponsel wajib diisi',
                'employee[nik]': 'NIK wajib diisi',
            };

            for (const [name, msg] of Object.entries(inputs)) {
                const el = document.querySelector(`[name="${name}"]`);
                if (!el?.value?.trim()) addError(el, msg);
            }

            if (!this.isEdit) {
                const pwdEl = document.querySelector('input[name="user[password]"]');
                if (!pwdEl?.value) {
                    errors.push('Password wajib diisi');
                    pwdEl?.classList.add('border-red-500');
                }
            }

            const selects = [
                'employee[gender]',
                'employee[religion]',
                'employee[marital_status]',
                'employee[blood_type]'
            ];

            selects.forEach(name => {
                const el = document.querySelector(`select[name="${name}"]`);
                if (!el?.value) {
                    errors.push('Field wajib dipilih');
                    el?.classList.add('border-red-500');
                }
            });

            return errors;
        },

        validateEmploymentData() {
            const errors = [];

            if (!this.selectedDepartment) {
                const el = document.querySelector('select[name="employee[department_id]"]');
                errors.push('Departemen wajib dipilih');
                el?.classList.add('border-red-500');
            }

            return errors;
        },

        validatePlacementData() {
            const errors = [];
            const el = document.querySelector('select[name="placement[placement_id]"]');
            if (!el?.value) {
                errors.push('Lokasi Penempatan wajib dipilih');
                el?.classList.add('border-red-500');
            }
            return errors;
        },

        clearErrorStyles() {
            document.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500');
            });
            document.querySelectorAll('.validation-error-msg').forEach(el => el.remove());
        },

        get filteredDivisions() {
            if (!this.selectedDepartment) return this.divisions;
            return this.divisions.filter(d =>
                !d.department_id || d.department_id === this.selectedDepartment
            );
        },

        get filteredPositions() {
            let result = this.positions;
            if (this.selectedDepartment) {
                result = result.filter(p =>
                    !p.department_id || p.department_id === this.selectedDepartment
                );
            }
            if (this.selectedDivision) {
                result = result.filter(p =>
                    !p.division_id || p.division_id === this.selectedDivision
                );
            }
            return result;
        },

        onDepartmentChange() {
            if (this.selectedDivision) {
                const div = this.divisions.find(d => d.id === this.selectedDivision);
                if (div?.department_id && div.department_id !== this.selectedDepartment) {
                    this.selectedDivision = '';
                }
            }
            if (this.selectedPosition) {
                const pos = this.positions.find(p => p.id === this.selectedPosition);
                if (pos?.department_id && pos.department_id !== this.selectedDepartment) {
                    this.selectedPosition = '';
                }
            }
        },

        formatNumber(value) {
            if (!value && value !== 0) return '';
            const num = parseFloat(value);
            if (isNaN(num)) return '';
            return num.toLocaleString('id-ID');
        },

        formatRupiah(value) {
            if (!value && value !== 0) return '';
            const num = parseFloat(value);
            if (isNaN(num)) return '';
            return 'Rp ' + num.toLocaleString('id-ID');
        },

        parseNumber(value) {
            if (!value) return 0;
            return parseInt(value.toString().replace(/[^0-9]/g, '')) || 0;
        }
    };
}
</script>
@endpush
