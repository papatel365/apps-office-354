{{-- Step 5: Hak Cuti --}}
<div x-data="leaveStep" class="bg-white rounded-xl border border-gray-200 p-6">
    <style>[x-cloak] { display: none !important; }</style>
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold mr-3">5</span>
        <i class="fa-solid fa-calendar-minus mr-2 text-indigo-600"></i>
        Pengaturan Hak Cuti
    </h3>

    <div class="mb-6">
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" name="leave[skip]" value="1" x-model="formData.leave.skip"
                class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
            <span class="ml-3 text-gray-700">Lewati langkah ini - Atur Nanti</span>
        </label>
    </div>

    <div x-show="!formData.leave.skip" class="space-y-6">
        {{-- Mode Selection --}}
        <div class="bg-gray-50 rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-700 mb-3">Metode Pengaturan Hak Cuti</label>
            <div class="space-y-3">
                <label class="flex items-center cursor-pointer p-3 rounded-lg hover:bg-gray-100 transition-colors"
                       :class="formData.leave.mode === 'policy' ? 'bg-indigo-50 border-2 border-indigo-500' : 'bg-white border-2 border-gray-200'">
                    <input type="radio" name="leave[mode]" value="policy" x-model="formData.leave.mode"
                        class="w-5 h-5 text-indigo-600 focus:ring-indigo-500">
                    <div class="ml-3">
                        <span class="font-medium text-gray-900">Gunakan Kebijakan Cuti Perusahaan</span>
                        <p class="text-sm text-gray-500">Terapkan jatah cuti dari kebijakan yang sudah ada</p>
                    </div>
                </label>
                <label class="flex items-center cursor-pointer p-3 rounded-lg hover:bg-gray-100 transition-colors"
                       :class="formData.leave.mode === 'manual' ? 'bg-indigo-50 border-2 border-indigo-500' : 'bg-white border-2 border-gray-200'">
                    <input type="radio" name="leave[mode]" value="manual" x-model="formData.leave.mode"
                        class="w-5 h-5 text-indigo-600 focus:ring-indigo-500">
                    <div class="ml-3">
                        <span class="font-medium text-gray-900">Atur Hak Cuti Manual</span>
                        <p class="text-sm text-gray-500">Tentukan jatah cuti secara manual untuk setiap jenis cuti</p>
                    </div>
                </label>
            </div>
        </div>

        {{-- Policy Mode --}}
        <div x-show="formData.leave.mode === 'policy'" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kebijakan Cuti</label>
                <div class="flex gap-2">
                    <select name="leave[leave_type_id]" x-model="formData.leave.policy_leave_type_id"
                        class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">Pilih Kebijakan Cuti</option>
                        <template x-for="lt in leaveTypes" :key="lt.id">
                            <option :value="lt.id"
                                :data-default-days="lt.default_days"
                                :data-is-paid="lt.is_paid"
                                x-text="lt.name + (lt.default_days ? ' (' + lt.default_days + ' hari)' : '') + (lt.is_paid ? ' - Berbayar' : ' - Tidak Berbayar')"></option>
                        </template>
                    </select>
                    <button type="button" @click="openLeaveTypeModal()"
                        class="px-3 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex-shrink-0 transition-colors"
                        title="Tambah Jenis Cuti">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
                <template x-if="leaveTypes.length === 0">
                    <p class="mt-1 text-sm text-amber-600">
                        <i class="fa-solid fa-exclamation-triangle mr-1"></i>
                        Belum ada kebijakan cuti. Klik "+" untuk membuat kebijakan cuti baru.
                    </p>
                </template>
            </div>

            {{-- Policy Preview --}}
            <template x-if="formData.leave.policy_leave_type_id">
                <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                    <div class="flex items-center mb-2">
                        <i class="fa-solid fa-check-circle text-green-600 mr-2"></i>
                        <span class="font-medium text-green-800">Kebijakan akan diterapkan:</span>
                    </div>
                    <template x-if="selectedLeaveType">
                        <ul class="text-sm text-green-700 space-y-1">
                            <li><strong x-text="selectedLeaveType.name"></strong></li>
                            <li>Jatah default: <strong x-text="selectedLeaveType.default_days || 0"></strong> hari/tahun</li>
                            <li x-show="selectedLeaveType.is_paid">Jenis: <strong>Berbayar</strong></li>
                            <li x-show="!selectedLeaveType.is_paid">Jenis: <strong>Tidak Berbayar</strong></li>
                        </ul>
                    </template>
                </div>
            </template>
        </div>

        {{-- Manual Mode --}}
        <div x-show="formData.leave.mode === 'manual'" class="space-y-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-800">Daftar Hak Cuti</h4>
                <button type="button" @click="addEntitlement()"
                    class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Hak Cuti
                </button>
            </div>

            <template x-if="formData.leave.entitlements.length === 0">
                <div class="p-6 bg-gray-50 rounded-lg text-center">
                    <i class="fa-solid fa-calendar text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">Belum ada hak cuti. Klik "Tambah Hak Cuti" untuk menambahkan.</p>
                </div>
            </template>

            {{-- Entitlement Rows --}}
            <div class="space-y-3">
                <template x-for="(entitlement, index) in formData.leave.entitlements" :key="index">
                    <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Jenis Cuti</label>
                                <div class="flex gap-1">
                                    <select x-model="entitlement.leave_type_id"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Pilih</option>
                                        <template x-for="lt in leaveTypes" :key="lt.id">
                                            <option :value="lt.id" x-text="lt.name"></option>
                                        </template>
                                    </select>
                                    <button type="button" @click="openLeaveTypeModal()"
                                        class="px-2 py-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-200" title="Tambah Jenis Cuti">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Jatah (Hari)</label>
                                <input type="number" x-model="entitlement.days" min="0"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Unit</label>
                                <select x-model="entitlement.unit"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                    <option value="days">Hari</option>
                                    <option value="hours">Jam</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="removeEntitlement(index)"
                                    class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Common Fields --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Perhitungan Jatah Awal</label>
                <select x-model="formData.leave.calculation_mode"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="full">Penuh</option>
                    <option value="prorata">Prorata (berdasarkan tanggal masuk)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Efektif</label>
                <input type="date" name="leave[effective_date]" x-model="formData.leave.effective_date"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Prorata Preview --}}
        <div x-show="formData.leave.calculation_mode === 'prorata' && selectedPolicyLeaveType" class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-center mb-2">
                <i class="fa-solid fa-calculator text-blue-600 mr-2"></i>
                <span class="font-medium text-blue-800">Preview Perhitungan Prorata:</span>
            </div>
            <p class="text-sm text-blue-700">
                Jatah annual <strong x-text="selectedPolicyLeaveType.default_days || 0"></strong> hari akan dihitung prorata
                berdasarkan sisa hari dalam tahun.
            </p>
        </div>
    </div>

    <div x-show="formData.leave.skip" class="p-6 bg-gray-50 rounded-lg text-center">
        <i class="fa-solid fa-calendar text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">Hak cuti dapat diatur nanti melalui profil karyawan</p>
    </div>

    {{-- Toast Notification --}}
    <div x-show="showToastFlag" x-cloak
         class="fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 transition-all"
         :class="toastType === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
         style="display: none;">
        <i :class="toastType === 'success' ? 'fa-solid fa-check-circle' : 'fa-solid fa-exclamation-circle'"></i>
        <span x-text="toastMessage"></span>
    </div>

    {{-- Modal Tambah Jenis Cuti --}}
    <div x-show="showLeaveTypeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="fixed inset-0 bg-black/50" @click="closeLeaveTypeModal()"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 z-10 border border-gray-200 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fa-solid fa-calendar-plus text-indigo-500 mr-2"></i>Tambah Jenis Cuti Baru
                </h3>
                <button type="button" @click="closeLeaveTypeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jenis Cuti <span class="text-red-500">*</span></label>
                        <input type="text" x-model="leaveTypeForm.name" placeholder="Contoh: Cuti Tahunan"
                               class="w-full px-4 py-2.5 border rounded-lg"
                               :class="leaveTypeErrors.name ? 'border-red-500' : 'border-gray-300'">
                        <template x-if="leaveTypeErrors.name">
                            <p class="mt-1 text-sm text-red-500" x-text="leaveTypeErrors.name[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode</label>
                        <input type="text" x-model="leaveTypeForm.code" placeholder="Contoh: CT"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jatah Default (Hari)</label>
                        <input type="number" x-model="leaveTypeForm.default_days" value="12" min="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Warna</label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="leaveTypeForm.color" value="#10B981"
                                   class="w-10 h-10 rounded cursor-pointer">
                            <input type="text" x-model="leaveTypeForm.color" placeholder="#10B981"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="flex items-center cursor-pointer mt-6">
                            <input type="checkbox" x-model="leaveTypeForm.is_paid" checked
                                class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <span class="ml-3 text-gray-700">Berbayar</span>
                        </label>
                    </div>
                    <div>
                        <label class="flex items-center cursor-pointer mt-6">
                            <input type="checkbox" x-model="leaveTypeForm.requires_document"
                                class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <span class="ml-3 text-gray-700">Memerlukan Lampiran</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Maks. Hari Berurutan</label>
                        <input type="number" x-model="leaveTypeForm.max_consecutive_days" min="1" placeholder="Tidak terbatas"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min. Pengajuan (Hari Sebelumnya)</label>
                        <input type="number" x-model="leaveTypeForm.min_advance_days" min="0" placeholder="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click.prevent="closeLeaveTypeModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                <button type="button" @click.prevent="saveLeaveType()" :disabled="savingLeaveType"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg disabled:opacity-50 flex items-center gap-2">
                    <span x-show="savingLeaveType"><i class="fa-solid fa-spinner fa-spin"></i></span>
                    <span x-text="savingLeaveType ? 'Menyimpan...' : 'Simpan & Gunakan'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Debuggable AJAX handler - reuse from Step 2
 */
if (typeof wizardAjax === 'undefined') {
    function wizardAjax(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(function(response) {
            console.log('[WIZARD-STEP5] Response:', response.status);
            return response.json().then(function(json) {
                console.log('[WIZARD-STEP5] Full response:', json);
                console.group('[WIZARD-STEP5] Response');
                console.log('Success:', json.success);
                console.log('Message:', json.message);
                console.log('Errors:', json.errors);
                if (json.debug) console.log('Debug:', json.debug);
                console.groupEnd();
                if (!response.ok || !json.success) {
                    console.error('[WIZARD-STEP5] FAILED:', json);
                    if (json.debug && json.debug.exception) console.error('[WIZARD-STEP5] Exception:', json.debug.exception);
                    if (json.debug && json.debug.sql) console.error('[WIZARD-STEP5] SQL:', json.debug.sql);
                    throw json;
                }
                return json;
            });
        })
        .catch(function(error) {
            console.error('[WIZARD-STEP5] Fetch error:', error);
            throw error;
        });
    }
}

document.addEventListener('alpine:init', function() {
    Alpine.data('leaveStep', function() {
        return {
            leaveTypes: [],
            showLeaveTypeModal: false,
            savingLeaveType: false,
            leaveTypeErrors: {},
            leaveTypeForm: {
                name: '',
                code: '',
                color: '#10B981',
                default_days: 12,
                is_paid: true,
                requires_document: false,
                max_consecutive_days: '',
                min_advance_days: 0
            },
            toastMessage: '',
            toastType: 'success',
            toastTimeout: null,
            showToastFlag: false,

            init: function() {
                console.log('[WIZARD-STEP5] Initializing...');
                var rLeaveTypes = @json($wizardLeaveTypes);
                this.leaveTypes = rLeaveTypes.map(function(lt) {
                    return {
                        id: String(lt.id),
                        name: lt.name,
                        code: lt.code || '',
                        color: lt.color || '#10B981',
                        default_days: lt.default_days || 0,
                        is_paid: lt.is_paid !== undefined ? lt.is_paid : true
                    };
                });
                console.log('[WIZARD-STEP5] Loaded leave types:', this.leaveTypes.length);
            },

            get selectedLeaveType() {
                if (!this.formData.leave.policy_leave_type_id) return null;
                return this.leaveTypes.find(function(lt) {
                    return lt.id === this.formData.leave.policy_leave_type_id;
                }.bind(this)) || null;
            },

            get selectedPolicyLeaveType() {
                return this.selectedLeaveType;
            },

            addEntitlement: function() {
                this.formData.leave.entitlements.push({
                    leave_type_id: '',
                    days: 0,
                    unit: 'days',
                    effective_date: this.formData.leave.effective_date
                });
            },

            removeEntitlement: function(index) {
                this.formData.leave.entitlements.splice(index, 1);
            },

            showToast: function(message, type) {
                type = type || 'success';
                this.toastMessage = message;
                this.toastType = type;
                this.showToastFlag = true;
                if (this.toastTimeout) clearTimeout(this.toastTimeout);
                var self = this;
                this.toastTimeout = setTimeout(function() {
                    self.showToastFlag = false;
                }, 4000);
            },

            openLeaveTypeModal: function() {
                console.log('[WIZARD-STEP5] Opening modal');
                this.leaveTypeForm = {
                    name: '',
                    code: '',
                    color: '#10B981',
                    default_days: 12,
                    is_paid: true,
                    requires_document: false,
                    max_consecutive_days: '',
                    min_advance_days: 0
                };
                this.leaveTypeErrors = {};
                this.showLeaveTypeModal = true;
            },

            closeLeaveTypeModal: function() {
                console.log('[WIZARD-STEP5] Closing modal');
                this.showLeaveTypeModal = false;
                this.leaveTypeForm = {
                    name: '',
                    code: '',
                    color: '#10B981',
                    default_days: 12,
                    is_paid: true,
                    requires_document: false,
                    max_consecutive_days: '',
                    min_advance_days: 0
                };
                this.leaveTypeErrors = {};
            },

            saveLeaveType: function() {
                var self = this;
                console.log('[WIZARD-STEP5] saveLeaveType() called, data:', self.leaveTypeForm);

                self.savingLeaveType = true;
                self.leaveTypeErrors = {};

                wizardAjax('{{ route("administrasi.data_karyawan.wizard.quick-leave-type") }}', self.leaveTypeForm)
                .then(function(response) {
                    console.log('[WIZARD-STEP5] Success:', response);
                    var newLeaveType = response.data;
                    console.log('[WIZARD-STEP5] New leave type:', newLeaveType);
                    self.leaveTypes.push(newLeaveType);
                    // If in manual mode with existing entitlements, update the last one
                    if (self.formData.leave.mode === 'manual' && self.formData.leave.entitlements.length > 0) {
                        var lastEntitlement = self.formData.leave.entitlements[self.formData.leave.entitlements.length - 1];
                        if (!lastEntitlement.leave_type_id) {
                            lastEntitlement.leave_type_id = String(newLeaveType.id);
                        }
                    }
                    self.closeLeaveTypeModal();
                    self.showToast('Jenis Cuti berhasil dibuat dan otomatis dipilih');
                })
                .catch(function(error) {
                    console.error('[WIZARD-STEP5] Error:', error);
                    var errorMessage = 'Gagal menyimpan jenis cuti';
                    if (error.errors) {
                        self.leaveTypeErrors = error.errors;
                        var firstError = Object.values(error.errors)[0];
                        if (Array.isArray(firstError) && firstError[0]) {
                            errorMessage = firstError[0];
                        }
                    } else if (error.message) {
                        errorMessage = error.message;
                    }
                    self.showToast(errorMessage, 'error');
                })
                .finally(function() {
                    self.savingLeaveType = false;
                });
            }
        };
    });
});
</script>
@endpush
