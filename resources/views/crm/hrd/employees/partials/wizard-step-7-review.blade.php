{{-- Step 7: Review & Simpan --}}
<div class="bg-white rounded-xl border border-gray-200 p-6" x-data="reviewStep" x-init="initReview()">
    <style>[x-cloak] { display: none !important; }</style>
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <span class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-sm font-bold mr-3">
            <i class="fa-solid fa-check"></i>
        </span>
        <i class="fa-solid fa-clipboard-check mr-2 text-green-600"></i>
        Review & Simpan
    </h3>

    <p class="text-gray-600 mb-6">
        Pastikan seluruh data di bawah ini sudah benar sebelum disimpan.
    </p>

    <div class="space-y-6">
        {{-- Data Pribadi Summary --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h4 class="font-semibold text-gray-700 flex items-center">
                    <i class="fa-solid fa-user mr-2 text-indigo-500"></i>
                    Data Pribadi
                </h4>
                <button type="button" @click="goToStep(0)" class="text-sm text-indigo-600 hover:text-indigo-800">
                    <i class="fa-solid fa-pen mr-1"></i> Edit
                </button>
            </div>
            <div class="p-4">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Nama</dt>
                        <dd class="font-medium text-gray-900" x-text="data.user.name || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Email</dt>
                        <dd class="font-medium text-gray-900" x-text="data.user.email || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Telepon</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employee.phone || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">NIK</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employee.nik || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Tempat, Tgl Lahir</dt>
                        <dd class="font-medium text-gray-900" x-text="formatBirthDate()">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Jenis Kelamin</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employee.gender || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Agama</dt>
                        <dd class="font-medium text-gray-900 capitalize" x-text="data.employee.religion || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Status</dt>
                        <dd class="font-medium text-gray-900 capitalize" x-text="data.employee.marital_status || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Gol. Darah</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employee.blood_type || '-'">-</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-xs text-gray-500 uppercase">Alamat</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employee.address || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Kota</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employee.city || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Provinsi</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employee.province || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Kode Pos</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employee.postal_code || '-'">-</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Data Pekerjaan Summary --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h4 class="font-semibold text-gray-700 flex items-center">
                    <i class="fa-solid fa-briefcase mr-2 text-indigo-500"></i>
                    Data Pekerjaan
                </h4>
                <button type="button" @click="goToStep(1)" class="text-sm text-indigo-600 hover:text-indigo-800">
                    <i class="fa-solid fa-pen mr-1"></i> Edit
                </button>
            </div>
            <div class="p-4">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Departemen</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employment.department || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Divisi</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employment.division || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Posisi</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employment.position || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Atasan</dt>
                        <dd class="font-medium text-gray-900" x-text="data.employment.supervisor || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Tipe Karyawan</dt>
                        <dd class="font-medium text-gray-900 capitalize" x-text="data.employment.employment_type || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Tanggal Bergabung</dt>
                        <dd class="font-medium text-gray-900" x-text="formatDate(data.employment.join_date)">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Tanggal Mulai Kerja</dt>
                        <dd class="font-medium text-gray-900" x-text="formatDate(data.employment.contract_start)">-</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Penempatan Summary --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h4 class="font-semibold text-gray-700 flex items-center">
                    <i class="fa-solid fa-map-marker-alt mr-2 text-indigo-500"></i>
                    Penempatan
                </h4>
                <button type="button" @click="goToStep(2)" class="text-sm text-indigo-600 hover:text-indigo-800">
                    <i class="fa-solid fa-pen mr-1"></i> Edit
                </button>
            </div>
            <div class="p-4">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Lokasi</dt>
                        <dd class="font-medium text-gray-900" x-text="data.placement.location || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Tanggal Mulai</dt>
                        <dd class="font-medium text-gray-900" x-text="formatDate(data.placement.start_date)">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Catatan</dt>
                        <dd class="font-medium text-gray-900" x-text="data.placement.notes || '-'">-</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Penggajian Summary --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h4 class="font-semibold text-gray-700 flex items-center">
                    <i class="fa-solid fa-money-bills mr-2 text-indigo-500"></i>
                    Penggajian
                </h4>
                <button type="button" @click="goToStep(3)" class="text-sm text-indigo-600 hover:text-indigo-800">
                    <i class="fa-solid fa-pen mr-1"></i> Edit
                </button>
            </div>
            <div class="p-4">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Gaji Pokok</dt>
                        <dd class="font-medium text-green-600" x-text="data.payroll.basic_salary_formatted || '-'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Total Tunjangan</dt>
                        <dd class="font-medium text-green-600" x-text="data.payroll.allowances_formatted || 'Rp 0'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Total Potongan</dt>
                        <dd class="font-medium text-red-600" x-text="data.payroll.deductions_formatted || 'Rp 0'">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Estimasi Bersih</dt>
                        <dd class="font-bold text-indigo-600" x-text="data.payroll.net_formatted || '-'">-</dd>
                    </div>
                </dl>

                {{-- Payment Method --}}
                <div>
                    <dt class="text-xs text-gray-500 uppercase mb-1">Metode Pembayaran</dt>
                    <dd class="font-medium text-gray-900 capitalize" x-text="data.payroll.payment_method || '-'">-</dd>
                </div>

                {{-- Bank Info --}}
                <template x-if="data.payroll.bank_name">
                    <div class="mt-2">
                        <dt class="text-xs text-gray-500 uppercase mb-1">Bank</dt>
                        <dd class="font-medium text-gray-900" x-text="data.payroll.bank_name">-</dd>
                    </div>
                </template>
                <template x-if="data.payroll.bank_account">
                    <div class="mt-2">
                        <dt class="text-xs text-gray-500 uppercase mb-1">Nomor Rekening</dt>
                        <dd class="font-medium text-gray-900" x-text="data.payroll.bank_account">-</dd>
                    </div>
                </template>
                <template x-if="data.payroll.bank_account_name">
                    <div class="mt-2">
                        <dt class="text-xs text-gray-500 uppercase mb-1">Nama Pemilik</dt>
                        <dd class="font-medium text-gray-900" x-text="data.payroll.bank_account_name">-</dd>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Important Notice --}}
    <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <h4 class="font-semibold text-green-800 mb-2 flex items-center">
            <i class="fa-solid fa-info-circle mr-2"></i>
            Informasi Penting
        </h4>
        <strong>Perhatian:</strong> Setelah data disubmit, pastikan seluruh informasi yang dimasukkan sudah benar karena data akan diproses oleh sistem.
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('reviewStep', function() {
        return {
            data: {
                user: {},
                employee: {},
                employment: {},
                placement: {},
                payroll: {}
            },

            get wizard() {
                return window.wizardData;
            },

            initReview: function() {
                var self = this;
                // Initial refresh
                this.refreshData();
                // Listen for step changes
                window.addEventListener('wizard-step-change', function(e) {
                    // Refresh when coming TO review step (step 4)
                    if (e.detail === 4) {
                        self.refreshData();
                    }
                });
            },

            refreshData: function() {
                // Read all form data from DOM and shared wizard state
                var self = this;

                // Get shared payroll data from wizard parent (single source of truth)
                var wizardPayroll = window.wizardData ? window.wizardData.formData.payroll : null;
                var wizardPreview = window.wizardData ? window.wizardData.payrollPreview : null;

                // Step 1: User data
                this.data.user = {
                    name: this.getVal('input[name="user[name]"]'),
                    email: this.getVal('input[name="user[email]"]')
                };

                // Step 1: Employee personal data
                this.data.employee = {
                    full_name: this.getVal('input[name="employee[full_name]"]'),
                    nick_name: this.getVal('input[name="employee[nick_name]"]'),
                    phone: this.getVal('input[name="employee[phone]"]'),
                    nik: this.getVal('input[name="employee[nik]"]'),
                    place_of_birth: this.getVal('input[name="employee[place_of_birth]"]'),
                    date_of_birth: this.getVal('input[name="employee[date_of_birth]"]'),
                    gender: this.getSelectText('select[name="employee[gender]"]'),
                    religion: this.getSelectText('select[name="employee[religion]"]'),
                    marital_status: this.getSelectText('select[name="employee[marital_status]"]'),
                    blood_type: this.getVal('select[name="employee[blood_type]"]'),
                    address: this.getVal('textarea[name="employee[address]"]'),
                    city: this.getVal('input[name="employee[city]"]'),
                    province: this.getVal('input[name="employee[province]"]'),
                    postal_code: this.getVal('input[name="employee[postal_code]"]')
                };

                // Step 2: Employment data
                this.data.employment = {
                    department: this.getSelectText('select[name="employee[department_id]"]'),
                    division: this.getSelectText('select[name="employee[division_id]"]'),
                    position: this.getSelectText('select[name="employee[position_id]"]'),
                    supervisor: this.getSelectText('select[name="employee[supervisor_id]"]'),
                    employment_type: this.getEmploymentType(),
                    join_date: this.getVal('input[name="employee[join_date]"]'),
                    contract_start: this.getVal('input[name="employee[contract_start]"]')
                };

                // Step 3: Placement data
                this.data.placement = {
                    location: this.getSelectText('select[name="placement[placement_id]"]'),
                    start_date: this.getVal('input[name="placement[start_date]"]'),
                    notes: this.getVal('textarea[name="placement[notes]"]')
                };

                // Step 4: Payroll data - use shared wizard state (single source of truth)
                var basicSalary = wizardPreview ? wizardPreview.basic : this.getPayrollBasic();
                var allowances = wizardPreview ? wizardPreview.totalAllowances : this.getPayrollAllowances();
                var deductions = wizardPreview ? wizardPreview.totalDeductions : this.getPayrollDeductions();
                var net = basicSalary + allowances - deductions;

                this.data.payroll = {
                    basic_salary: basicSalary,
                    basic_salary_formatted: basicSalary > 0 ? this.formatRupiah(basicSalary) : '-',
                    allowances: allowances,
                    allowances_formatted: allowances > 0 ? this.formatRupiah(allowances) : 'Rp 0',
                    deductions: deductions,
                    deductions_formatted: deductions > 0 ? this.formatRupiah(deductions) : 'Rp 0',
                    net: net,
                    net_formatted: net > 0 ? this.formatRupiah(net) : '-',
                    payment_method: wizardPayroll ? wizardPayroll.payment_method : this.getPaymentMethod(),
                    bank_name: wizardPayroll ? wizardPayroll.bank_name : this.getSelectText('select[name="payroll[bank_name]"]'),
                    bank_account: wizardPayroll ? wizardPayroll.bank_account : this.getVal('input[name="payroll[bank_account]"]'),
                    bank_account_name: wizardPayroll ? wizardPayroll.bank_account_name : this.getVal('input[name="payroll[bank_account_name]"]')
                };

                console.log('[REVIEW] Data refreshed from wizard state:', {
                    basicSalary: basicSalary,
                    allowances: allowances,
                    deductions: deductions,
                    net: net,
                    wizardPayroll: wizardPayroll
                });
            },

            getVal: function(selector) {
                var el = document.querySelector(selector);
                return el ? el.value : '';
            },

            getSelectText: function(selector) {
                var el = document.querySelector(selector);
                if (!el) return '';
                var selected = el.selectedOptions[0];
                return selected ? selected.textContent.trim() : '';
            },

            getEmploymentType: function() {
                var val = this.getVal('select[name="employee[employment_type]"]');
                var labels = {
                    'permanent': 'Tetap',
                    'contract': 'Kontrak',
                    'probation': 'Percobaan',
                    'part_time': 'Paruh Waktu'
                };
                return labels[val] || val;
            },

            getPaymentMethod: function() {
                var val = this.getVal('select[name="payroll[payment_method]"]');
                var labels = {
                    'transfer': 'Transfer Bank',
                    'cash': 'Tunai'
                };
                return labels[val] || val;
            },

            getPayrollBasic: function() {
                // Try to get from hidden input first
                var hiddenInput = document.querySelector('input[name="payroll[basic_salary]"]');
                if (hiddenInput && hiddenInput.value) {
                    return parseInt(hiddenInput.value) || 0;
                }
                // Fallback to display input
                var displayInput = document.querySelector('input[x-model*="basic_salary_display"]');
                if (displayInput && displayInput.value) {
                    return parseInt(displayInput.value.replace(/[^0-9]/g, '')) || 0;
                }
                return 0;
            },

            getPayrollAllowances: function() {
                // Get from hidden inputs
                var total = 0;
                var inputs = document.querySelectorAll('input[name*="payroll[allowances]"][name*="[amount]"]');
                inputs.forEach(function(input) {
                    var val = parseInt(input.value) || 0;
                    total += val;
                });
                return total;
            },

            getPayrollDeductions: function() {
                // Get from hidden inputs
                var total = 0;
                var inputs = document.querySelectorAll('input[name*="payroll[deductions]"][name*="[amount]"]');
                inputs.forEach(function(input) {
                    var val = parseInt(input.value) || 0;
                    total += val;
                });
                return total;
            },

            formatDate: function(dateStr) {
                if (!dateStr) return '';
                try {
                    var date = new Date(dateStr);
                    var options = { day: 'numeric', month: 'long', year: 'numeric' };
                    return date.toLocaleDateString('id-ID', options);
                } catch (e) {
                    return dateStr;
                }
            },

            formatBirthDate: function() {
                var place = this.data.employee.place_of_birth;
                var date = this.data.employee.date_of_birth;
                if (!place && !date) return '-';
                var result = place || '';
                if (date) {
                    result += (result ? ', ' : '') + this.formatDate(date);
                }
                return result || '-';
            },

            /**
             * Format currency - delegates to wizard's formatRupiah
             */
            formatRupiah: function(value) {
                if (window.wizardData && window.wizardData.formatRupiah) {
                    return window.wizardData.formatRupiah(value);
                }
                // Fallback
                if (!value && value !== 0) return '0';
                var num = parseInt(value);
                if (isNaN(num)) return '0';
                return 'Rp ' + num.toLocaleString('id-ID');
            },

            goToStep: function(step) {
                // Dispatch event for wizard to handle
                window.dispatchEvent(new CustomEvent('wizard-step-change', { detail: step }));
            }
        };
    });
});
</script>
@endpush
