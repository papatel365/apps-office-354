{{-- Step 2: Data Pekerjaan (Unified - Create & Edit) --}}
@php
    $isEditMode = isset($mode) && $mode === 'edit';
    $emp = $employee ?? null;

    // Helper function for old input with fallback
    $oldInput = function($key, $emp = null, $default = '') {
        $value = old($key);
        if ($value !== null && $value !== '') {
            return $value;
        }
        if (str_starts_with($key, 'employee.')) {
            $field = substr($key, 10);
            if ($emp && isset($emp->{$field})) {
                return $emp->{$field};
            }
            return $default;
        }
        if ($emp && isset($emp->{$key})) {
            return $emp->{$key};
        }
        return $default;
    };

    // Selected values for cascading dropdowns (from employee data)
    // Gunakan field yang BENAR sesuai database
    $selectedDept = $oldInput('employee.department_id', $emp);
    $selectedDiv = $oldInput('employee.division_id', $emp);
    $selectedPos = $oldInput('employee.position_id', $emp);
    // supervisor_id dari employee_profiles.supervisor_id
    $selectedSup = $oldInput('employee.supervisor_id', $emp);
    // employee_type_id adalah field yang benar (bukan employment_type)
    $selectedEmpType = $oldInput('employee.employee_type_id', $emp);
    // Format tanggal untuk input date (YYYY-MM-DD)
    $joinDateRaw = $emp?->join_date;
    $joinDate = $joinDateRaw ? \Carbon\Carbon::parse($joinDateRaw)->format('Y-m-d') : now()->format('Y-m-d');
    $contractEndRaw = $emp?->contract_end;
    $contractStart = $contractEndRaw ? \Carbon\Carbon::parse($contractEndRaw)->format('Y-m-d') : '';
@endphp

<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6" x-data="employmentStep2">
    <style>[x-cloak] { display: none !important; }</style>
    <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-100 mb-6 flex items-center">
        <span class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm font-bold mr-3">2</span>
        Data Pekerjaan
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {{-- Departemen --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">
                <i class="fa-solid fa-building mr-1"></i>Departemen <span class="text-red-500">*</span>
            </label>
            <select name="employee[department_id]" x-model="selectedDepartment" @change="onDepartmentChange()"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                <option value="">Pilih Departemen</option>
                <template x-for="dept in departments" :key="dept.id">
                    <option :value="dept.id" x-text="dept.name"></option>
                </template>
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                <a href="{{ route('pengaturan.umum.index') }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                    <i class="fa-solid fa-external-link-alt mr-1"></i>Kelola di Pengaturan Umum
                </a>
            </p>
        </div>

        {{-- Divisi --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">
                <i class="fa-solid fa-sitemap mr-1"></i>Divisi <span class="text-red-500">*</span>
            </label>
            <select name="employee[division_id]" x-model="selectedDivision"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                <option value="">Pilih Divisi</option>
                <template x-for="div in filteredDivisions" :key="div.id">
                    <option :value="div.id" x-text="div.name"></option>
                </template>
            </select>
        </div>

        {{-- Posisi / Jabatan --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">
                <i class="fa-solid fa-user-tie mr-1"></i>Posisi / Jabatan <span class="text-red-500">*</span>
            </label>
            <select name="employee[position_id]" x-model="selectedPosition"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                <option value="">Pilih Posisi</option>
                <template x-for="pos in filteredPositions" :key="pos.id">
                    <option :value="pos.id" x-text="pos.name"></option>
                </template>
            </select>
        </div>

        {{-- Atasan Langsung --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">
                Atasan Langsung <span class="text-red-500">*</span>
            </label>
            <select name="employee[supervisor_id]" x-model="selectedSupervisor"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                <option value="">Pilih Atasan</option>
                <template x-for="sup in supervisors" :key="sup.id">
                    <option :value="sup.id" x-text="sup.full_name"></option>
                </template>
            </select>
        </div>

        {{-- Tipe Karyawan - dari database --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">
                Status Karyawan <span class="text-red-500">*</span>
            </label>
            <select name="employee[employee_type_id]" x-model="selectedEmployeeType"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                <option value="">Pilih</option>
                <template x-for="et in employeeTypes" :key="et.id">
                    <option :value="et.id" x-text="et.name"></option>
                </template>
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                <a href="{{ route('pengaturan.umum.index', ['tab' => 'employee-types']) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                    <i class="fa-solid fa-external-link-alt mr-1"></i>Kelola Tipe Karyawan
                </a>
            </p>
        </div>

        {{-- Tanggal Bergabung --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Tanggal Bergabung</label>
            <input type="date" name="employee[join_date]" value="{{ $joinDate }}"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
        </div>

        {{-- Tanggal Mulai Kerja --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">
                Tanggal Mulai Kerja <span class="text-red-500">*</span>
            </label>
            <input type="date" name="employee[contract_start]" value="{{ $contractStart }}"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
        </div>
    </div>
</div>

@php
    // Ensure all variables are arrays for JSON encoding
    $wizardDepartmentsJson = json_encode($wizardDepartments ?? []);
    $wizardDivisionsJson = json_encode($wizardDivisions ?? []);
    $wizardPositionsJson = json_encode($wizardPositions ?? []);
    $wizardSupervisorsJson = json_encode($wizardSupervisors ?? []);
    $wizardEmployeeTypesJson = json_encode($employeeTypes ?? []);

    // Alpine uses selectedEmpTypeId (pakai snake_case), Blade uses selectedEmpType
    // Keduanya ambil dari employee_type_id
    $selectedEmpTypeId = $emp?->employee_type_id ? (string)$emp->employee_type_id : '';
@endphp

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('employmentStep2', function() {
        return {
            // Data arrays
            departments: [],
            divisions: [],
            positions: [],
            supervisors: [],
            employeeTypes: [],

            // Selected values - initialized from PHP
            selectedDepartment: '{{ $selectedDept }}',
            selectedDivision: '{{ $selectedDiv }}',
            selectedPosition: '{{ $selectedPos }}',
            selectedSupervisor: '{{ $selectedSup }}',
            selectedEmployeeType: '{{ $selectedEmpTypeId }}',

            init: function() {
                console.log('[WIZARD-STEP2] Initializing...');
                var self = this;
                console.log('[WIZARD-STEP2] Pre-selected values:', {
                    department: this.selectedDepartment,
                    division: this.selectedDivision,
                    position: this.selectedPosition,
                    supervisor: this.selectedSupervisor,
                    employeeType: this.selectedEmployeeType
                });

                try {
                    // Parse data from PHP
                    var departmentsData = {!! $wizardDepartmentsJson !!};
                    var divisionsData = {!! $wizardDivisionsJson !!};
                    var positionsData = {!! $wizardPositionsJson !!};
                    var supervisorsData = {!! $wizardSupervisorsJson !!};
                    var employeeTypesData = {!! $wizardEmployeeTypesJson !!};

                    console.log('[WIZARD-STEP2] Raw data:', {
                        departments: departmentsData,
                        divisions: divisionsData,
                        positions: positionsData,
                        supervisors: supervisorsData,
                        employeeTypes: employeeTypesData
                    });

                    // Map departments
                    if (Array.isArray(departmentsData)) {
                        this.departments = departmentsData.map(function(d) {
                            return {
                                id: String(d.id),
                                name: d.name || '',
                                code: d.code || ''
                            };
                        });
                    }

                    // Map divisions
                    if (Array.isArray(divisionsData)) {
                        this.divisions = divisionsData.map(function(d) {
                            return {
                                id: String(d.id),
                                name: d.name || '',
                                code: d.code || '',
                                department_id: d.department_id ? String(d.department_id) : ''
                            };
                        });
                    }

                    // Map positions
                    if (Array.isArray(positionsData)) {
                        this.positions = positionsData.map(function(p) {
                            return {
                                id: String(p.id),
                                name: p.name || '',
                                code: p.code || '',
                                department_id: p.department_id ? String(p.department_id) : '',
                                division_id: p.division_id ? String(p.division_id) : ''
                            };
                        });
                    }

                    // Map supervisors
                    if (Array.isArray(supervisorsData)) {
                        this.supervisors = supervisorsData.map(function(s) {
                            var name = 'Unknown';
                            if (s.user && s.user.name) {
                                name = s.user.name;
                            } else if (s.full_name) {
                                name = s.full_name;
                            } else if (s.name) {
                                name = s.name;
                            }
                            return {
                                id: String(s.id),
                                full_name: name
                            };
                        });
                    }

                    // Map employee types from database
                    if (Array.isArray(employeeTypesData)) {
                        this.employeeTypes = employeeTypesData.map(function(t) {
                            return {
                                id: String(t.id),
                                name: t.name || '',
                                code: t.code || '',
                                color: t.color || '#6B7280'
                            };
                        });

                        // SET DOM SELECT VALUE SETELAH OPTIONS DIMUAT
                        // Ini adalah kunci perbaikan - Alpine x-model tidak cukup untuk select dengan template
                        var empTypeVal = self.selectedEmployeeType;
                        this.$nextTick(function() {
                            var select = document.querySelector('select[name="employee[employee_type_id]"]');
                            if (select && empTypeVal) {
                                console.log('[STEP2] Setting employee_type select value:', empTypeVal);
                                select.value = empTypeVal;
                            }
                        });
                    }

                    console.log('[STEP2] employeeTypes loaded:', this.employeeTypes.length);
                    console.log('[STEP2] selectedEmployeeType:', this.selectedEmployeeType);

                    // Sync with parent wizard state after a tick
                    this.$nextTick(function() {
                        if (window.wizardData) {
                            self.syncWithParent();
                        }
                    });

                    // Also try to sync after a delay in case parent isn't ready
                    setTimeout(function() {
                        self.syncWithParent();
                    }, 200);

                } catch (e) {
                    console.error('[WIZARD-STEP2] Error initializing:', e);
                }
            },

            // Sync selected values with parent wizard
            syncWithParent: function() {
                if (!window.wizardData) return;

                // Only sync if parent has values but this component doesn't
                var parentDept = window.wizardData.selectedDepartment;
                var parentDiv = window.wizardData.selectedDivision;
                var parentPos = window.wizardData.selectedPosition;
                var parentSup = window.wizardData.selectedSupervisor;
                var parentEmpType = window.wizardData.selectedEmployeeType;

                if (parentDept && !this.selectedDepartment) {
                    this.selectedDepartment = parentDept;
                }
                if (parentDiv && !this.selectedDivision) {
                    this.selectedDivision = parentDiv;
                }
                if (parentPos && !this.selectedPosition) {
                    this.selectedPosition = parentPos;
                }
                if (parentSup && !this.selectedSupervisor) {
                    this.selectedSupervisor = parentSup;
                }
                if (parentEmpType && !this.selectedEmployeeType) {
                    this.selectedEmployeeType = parentEmpType;
                }

                // Also sync employeeTypes from parent
                if (window.wizardData.employeeTypes && window.wizardData.employeeTypes.length > 0) {
                    this.employeeTypes = window.wizardData.employeeTypes;
                }

                // SET DOM SELECT VALUE SETELAH SYNC
                // Ini penting karena syncWithParent overwrite employeeTypes
                var empTypeVal2 = this.selectedEmployeeType;
                this.$nextTick(function() {
                    var select = document.querySelector('select[name="employee[employee_type_id]"]');
                    if (select && empTypeVal2) {
                        console.log('[STEP2] syncWithParent - setting employee_type select value:', empTypeVal2);
                        select.value = empTypeVal2;
                    }
                });

                console.log('[WIZARD-STEP2] Synced with parent:', {
                    department: this.selectedDepartment,
                    division: this.selectedDivision,
                    position: this.selectedPosition,
                    supervisor: this.selectedSupervisor,
                    employeeType: this.selectedEmployeeType
                });
            },

            // Computed: filtered divisions based on selected department
            get filteredDivisions() {
                if (!this.selectedDepartment) {
                    return this.divisions;
                }
                var self = this;
                return this.divisions.filter(function(d) {
                    return !d.department_id || d.department_id === self.selectedDepartment;
                });
            },

            // Computed: filtered positions based on selected department and division
            get filteredPositions() {
                var self = this;
                var result = this.positions;

                if (this.selectedDepartment) {
                    result = result.filter(function(p) {
                        return !p.department_id || p.department_id === self.selectedDepartment;
                    });
                }

                if (this.selectedDivision) {
                    result = result.filter(function(p) {
                        return !p.division_id || p.division_id === self.selectedDivision;
                    });
                }

                return result;
            },

            // Handle department change - reset dependent selections
            onDepartmentChange: function() {
                console.log('[WIZARD-STEP2] Department changed to:', this.selectedDepartment);

                // Sync with parent wizard
                if (window.wizardData) {
                    window.wizardData.selectedDepartment = this.selectedDepartment;
                }

                // Reset division if it doesn't match new department
                if (this.selectedDivision) {
                    var division = this.divisions.find(function(d) {
                        return d.id === this.selectedDivision;
                    }.bind(this));

                    if (division && division.department_id && division.department_id !== this.selectedDepartment) {
                        console.log('[WIZARD-STEP2] Resetting division');
                        this.selectedDivision = '';
                        if (window.wizardData) {
                            window.wizardData.selectedDivision = '';
                        }
                    }
                }

                // Reset position if it doesn't match new department
                if (this.selectedPosition) {
                    var position = this.positions.find(function(p) {
                        return p.id === this.selectedPosition;
                    }.bind(this));

                    if (position && position.department_id && position.department_id !== this.selectedDepartment) {
                        console.log('[WIZARD-STEP2] Resetting position');
                        this.selectedPosition = '';
                        if (window.wizardData) {
                            window.wizardData.selectedPosition = '';
                        }
                    }
                }
            }
        };
    });
});
</script>
@endpush
