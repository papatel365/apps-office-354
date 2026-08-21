{{-- Step 6: Penggajian (READ-ONLY PAYROLL STEP) --}}
{{-- ARCHITECTURE: Single Source of Truth is employeeWizard() in wizard.blade.php --}}
{{-- PayrollStep only displays data, it does NOT modify anything --}}
@php
    $isEditMode = isset($mode) && $mode === 'edit';
    $emp = $employee ?? null;

    // Banks list
    $banks = $banks ?? ['BCA', 'Mandiri', 'BNI', 'BRI', 'BTPN', 'CIMB Niaga', 'Danamon', 'Permata', 'Bank Mega', 'BSI', 'Bank Lainnya'];
@endphp

<div x-data="payrollStep()" class="bg-white rounded-xl border border-gray-200 p-6">
    <style>[x-cloak] { display: none !important; }</style>
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold mr-3">6</span>
        <i class="fa-solid fa-money-bills mr-2 text-indigo-600"></i>
        Penggajian
    </h3>

    <div x-show="!formData.payroll.skip" class="space-y-6">
        {{-- Hidden inputs for payroll data (to be read by Review and submitted to server) --}}
        <input type="hidden" name="payroll[basic_salary]" :value="formData.payroll.basic_salary ?? 0">
        <input type="hidden" name="payroll[payment_method]" :value="formData.payroll.payment_method ?? 'transfer'">
        <input type="hidden" name="payroll[bank_name]" :value="formData.payroll.bank_name ?? ''">
        <input type="hidden" name="payroll[bank_account]" :value="formData.payroll.bank_account ?? ''">
        <input type="hidden" name="payroll[bank_account_name]" :value="formData.payroll.bank_account_name ?? ''">

        <template x-for="(allowance, index) in formData.payroll.allowances" :key="'allowance-' + index">
            <div>
                <input type="hidden" :name="'payroll[allowances][' + index + '][name]'" :value="allowance.name ?? ''">
                <input type="hidden" :name="'payroll[allowances][' + index + '][type]'" :value="allowance.type ?? 'fixed'">
                <input type="hidden" :name="'payroll[allowances][' + index + '][amount]'" :value="allowance.amount ?? 0">
            </div>
        </template>
        <template x-for="(deduction, index) in formData.payroll.deductions" :key="'deduction-' + index">
            <div>
                <input type="hidden" :name="'payroll[deductions][' + index + '][name]'" :value="deduction.name ?? ''">
                <input type="hidden" :name="'payroll[deductions][' + index + '][type]'" :value="deduction.type ?? 'fixed'">
                <input type="hidden" :name="'payroll[deductions][' + index + '][amount]'" :value="deduction.amount ?? 0">
            </div>
        </template>

        {{-- Gaji Pokok --}}
        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fa-solid fa-coins mr-2 text-indigo-500"></i>
                Struktur Gaji
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gaji Pokok <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">Rp</span>
                        {{-- READ-ONLY display - use formatRupiah from wizard --}}
                        <input type="text"
                            :value="formData.payroll.basic_salary_display || formatDisplayValue(formData.payroll.basic_salary)"
                            @blur="handleBasicSalaryBlur($event)"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                            placeholder="5.000.000">
                    </div>
                </div>
            </div>
        </div>

        {{-- Tunjangan --}}
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-semibold text-gray-800 flex items-center">
                    <i class="fa-solid fa-plus-circle mr-2 text-green-500"></i>
                    Tunjangan
                </h4>
                <button type="button" @click="addAllowance()"
                    class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Tunjangan
                </button>
            </div>

            <template x-if="formData.payroll.allowances.length === 0">
                <div class="p-4 bg-white rounded-lg text-center">
                    <p class="text-gray-500 text-sm">Belum ada tunjangan. Klik "Tambah Tunjangan" untuk menambahkan.</p>
                </div>
            </template>

            <div class="space-y-2">
                <template x-for="(allowance, index) in formData.payroll.allowances" :key="'allowance-' + index">
                    <div class="flex items-center gap-3 p-3 bg-white rounded-lg">
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <input type="text" x-model="allowance.name" placeholder="Nama Tunjangan"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <select x-model="allowance.type"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                    <option value="fixed">Nominal Tetap</option>
                                    <option value="percentage">Persentase</option>
                                </select>
                            </div>
                            <div>
                                <div class="relative">
                                    <template x-if="allowance.type === 'percentage'">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                                    </template>
                                    <template x-if="allowance.type !== 'percentage'">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                                    </template>
                                    {{-- Use raw amount value, format on blur --}}
                                    <input type="text"
                                        :value="allowance.amount_display || formatDisplayValue(allowance.amount)"
                                        @blur="handleAllowanceBlur(index, $event)"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                                        :class="allowance.type === 'percentage' ? 'pr-8' : 'pl-10'">
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <template x-if="allowance.type === 'percentage'">
                                    <span class="text-sm text-gray-500 whitespace-nowrap"
                                        x-text="'dari Gaji Pokok'"></span>
                                </template>
                                <button type="button" @click="removeAllowance(index)"
                                    class="px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="formData.payroll.allowances.length > 0" class="mt-3 p-3 bg-green-50 rounded-lg">
                <span class="text-sm font-medium text-green-800">
                    Total Tunjangan: <span x-text="wizard.formatRupiah(payrollPreview.totalAllowances)"></span>
                </span>
            </div>
        </div>

        {{-- Potongan --}}
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-semibold text-gray-800 flex items-center">
                    <i class="fa-solid fa-minus-circle mr-2 text-red-500"></i>
                    Potongan
                </h4>
                <button type="button" @click="addDeduction()"
                    class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Potongan
                </button>
            </div>

            <template x-if="formData.payroll.deductions.length === 0">
                <div class="p-4 bg-white rounded-lg text-center">
                    <p class="text-gray-500 text-sm">Belum ada potongan. Klik "Tambah Potongan" untuk menambahkan.</p>
                </div>
            </template>

            <div class="space-y-2">
                <template x-for="(deduction, index) in formData.payroll.deductions" :key="'deduction-' + index">
                    <div class="flex items-center gap-3 p-3 bg-white rounded-lg">
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <input type="text" x-model="deduction.name" placeholder="Nama Potongan"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <select x-model="deduction.type"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                    <option value="fixed">Nominal Tetap</option>
                                    <option value="percentage">Persentase</option>
                                </select>
                            </div>
                            <div>
                                <div class="relative">
                                    <template x-if="deduction.type === 'percentage'">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                                    </template>
                                    <template x-if="deduction.type !== 'percentage'">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                                    </template>
                                    {{-- Use raw amount value, format on blur --}}
                                    <input type="text"
                                        :value="deduction.amount_display || formatDisplayValue(deduction.amount)"
                                        @blur="handleDeductionBlur(index, $event)"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                                        :class="deduction.type === 'percentage' ? 'pr-8' : 'pl-10'">
                                </div>
                            </div>
                            <div>
                                <button type="button" @click="removeDeduction(index)"
                                    class="px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="formData.payroll.deductions.length > 0" class="mt-3 p-3 bg-red-50 rounded-lg">
                <span class="text-sm font-medium text-red-800">
                    Total Potongan: <span x-text="wizard.formatRupiah(payrollPreview.totalDeductions)"></span>
                </span>
            </div>
        </div>

        {{-- Metode Pembayaran --}}
        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fa-solid fa-university mr-2 text-indigo-500"></i>
                Metode Pembayaran
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Metode</label>
                    <select x-model="formData.payroll.payment_method"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="transfer">Transfer Bank</option>
                        <option value="cash">Tunai</option>
                    </select>
                </div>
                <template x-if="formData.payroll.payment_method === 'transfer'">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bank</label>
                        <select x-model="formData.payroll.bank_name"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">Pilih Bank</option>
                            @foreach($banks as $bank)
                            <option value="{{ $bank }}">{{ $bank }}</option>
                            @endforeach
                        </select>
                    </div>
                </template>
            </div>

            <template x-if="formData.payroll.payment_method === 'transfer'">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Rekening</label>
                        <input type="text" x-model="formData.payroll.bank_account"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            placeholder="1234567890">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pemilik Rekening</label>
                        <input type="text" x-model="formData.payroll.bank_account_name"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            placeholder="Nama sesuai di rekening">
                    </div>
                </div>
            </template>
        </div>

        {{-- Ringkasan Gaji Preview --}}
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 border border-indigo-100">
            <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fa-solid fa-receipt mr-2 text-indigo-500"></i>
                Ringkasan Gaji
            </h4>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Gaji Pokok</span>
                    <span class="font-medium" x-text="wizard.formatRupiah(payrollPreview.basic)"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-green-600">Total Tunjangan</span>
                    <span class="font-medium text-green-600" x-text="'+' + wizard.formatRupiah(payrollPreview.totalAllowances)"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-red-600">Total Potongan</span>
                    <span class="font-medium text-red-600" x-text="'-' + wizard.formatRupiah(payrollPreview.totalDeductions)"></span>
                </div>
                <div class="border-t pt-2 mt-2">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-800">Gaji Bersih</span>
                        <span class="text-xl font-bold text-indigo-600" x-text="wizard.formatRupiah(payrollPreview.net)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="formData.payroll.skip" class="p-6 bg-gray-50 rounded-lg text-center">
        <i class="fa-solid fa-money-bill text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">Konfigurasi penggajian dapat diatur nanti melalui profil karyawan</p>
    </div>

    {{-- Security Notice --}}
    <div class="mt-6 p-4 bg-amber-50 rounded-lg">
        <p class="text-sm text-amber-700">
            <i class="fa-solid fa-shield-alt mr-1"></i>
            <strong>Keamanan Data:</strong> Data penggajian bersifat sensitif dan hanya dapat dilihat oleh pengguna dengan hak akses yang sesuai.
        </p>
    </div>
</div>

@push('scripts')
<script>
/**
 * PAYROLL STEP - READ ONLY COMPONENT
 *
 * ARCHITECTURE:
 * - Single Source of Truth: window.wizardData (employeeWizard in wizard.blade.php)
 * - This component ONLY:
 *   1. Displays data from wizardData
 *   2. Handles user input for ALLOWANCES and DEDUCTIONS only
 *   3. Formats display values
 *
 * DO NOT:
 * - Set basic_salary from PHP/Blade
 * - Set bank details from PHP/Blade
 * - Overwrite any wizardData.payroll fields
 */
document.addEventListener('alpine:init', function() {
    Alpine.data('payrollStep', function() {
        return {
            /**
             * Reference to wizard (single source of truth)
             * MUST use getter to ensure reactivity
             */
            get wizard() {
                return window.wizardData;
            },

            /**
             * Proxy to wizard's formData.payroll
             * This keeps the reference live and reactive
             */
            get formData() {
                return window.wizardData ? window.wizardData.formData : { payroll: { skip: false, allowances: [], deductions: [] } };
            },

            /**
             * Use wizard's payrollPreview computed property
             */
            get payrollPreview() {
                return window.wizardData ? window.wizardData.payrollPreview : { basic: 0, totalAllowances: 0, totalDeductions: 0, net: 0 };
            },

            /**
             * INIT - READ ONLY
             * Does NOT populate any data
             * Just ensures window.wizardData is available
             */
            init: function() {
                // Data is already populated by wizard's populateFromEmployee()
                // This component is READ ONLY - no data setting here
                console.log('[PAYROLL] Initialized - using wizard data as source of truth');
            },

            /**
             * Format a number for display (e.g., 2500000 -> "2.500.000")
             * Uses wizard's formatNumber method
             */
            formatDisplayValue(value) {
                if (!value && value !== 0) return '';
                const num = parseFloat(value);
                if (isNaN(num)) return '';
                return num.toLocaleString('id-ID');
            },

            /**
             * Handle basic salary input blur
             * Parse formatted value and update wizard data
             */
            handleBasicSalaryBlur: function(event) {
                const input = event.target;
                const rawValue = input.value || '';
                const numericValue = parseInt(rawValue.replace(/[^0-9]/g, '')) || 0;

                // Update wizard's data (single source of truth)
                this.wizard.formData.payroll.basic_salary = numericValue;

                // Re-format the input display
                input.value = numericValue > 0 ? this.formatDisplayValue(numericValue) : '';
            },

            /**
             * Add a new allowance
             */
            addAllowance: function() {
                this.wizard.formData.payroll.allowances.push({
                    name: '',
                    type: 'fixed',
                    amount: 0,
                    amount_display: ''
                });
            },

            /**
             * Remove an allowance
             */
            removeAllowance: function(index) {
                this.wizard.formData.payroll.allowances.splice(index, 1);
            },

            /**
             * Handle allowance amount input blur
             */
            handleAllowanceBlur: function(index, event) {
                const input = event.target;
                const allowance = this.wizard.formData.payroll.allowances[index];
                if (!allowance) return;

                const rawValue = input.value || '';
                const numericValue = parseInt(rawValue.replace(/[^0-9]/g, '')) || 0;

                allowance.amount = numericValue;
                allowance.amount_display = numericValue > 0 ? this.formatDisplayValue(numericValue) : '';
            },

            /**
             * Add a new deduction
             */
            addDeduction: function() {
                this.wizard.formData.payroll.deductions.push({
                    name: '',
                    type: 'fixed',
                    amount: 0,
                    amount_display: ''
                });
            },

            /**
             * Remove a deduction
             */
            removeDeduction: function(index) {
                this.wizard.formData.payroll.deductions.splice(index, 1);
            },

            /**
             * Handle deduction amount input blur
             */
            handleDeductionBlur: function(index, event) {
                const input = event.target;
                const deduction = this.wizard.formData.payroll.deductions[index];
                if (!deduction) return;

                const rawValue = input.value || '';
                const numericValue = parseInt(rawValue.replace(/[^0-9]/g, '')) || 0;

                deduction.amount = numericValue;
                deduction.amount_display = numericValue > 0 ? this.formatDisplayValue(numericValue) : '';
            }
        };
    });
});
</script>
@endpush
