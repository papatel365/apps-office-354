{{-- Step 4: Shift Kerja --}}
<div x-data="shiftStep" class="bg-white rounded-xl border border-gray-200 p-6">
    <style>[x-cloak] { display: none !important; }</style>
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold mr-3">4</span>
        <i class="fa-solid fa-clock mr-2 text-indigo-600"></i>
        Shift Kerja
    </h3>

    <div class="mb-6">
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" name="shift[skip]" value="1" x-model="formData.shift.skip"
                class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
            <span class="ml-3 text-gray-700">Lewati langkah ini - Atur Nanti</span>
        </label>
    </div>

    <div x-show="!formData.shift.skip" class="space-y-6">
        {{-- Pilih Shift --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Shift Kerja</label>
            <div class="mb-3">
                <button type="button" @click="openShiftModal()"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2 transition-colors">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Shift Baru
                </button>
            </div>
            <template x-if="shifts.length === 0">
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-sm text-amber-700">
                        <i class="fa-solid fa-exclamation-triangle mr-1"></i>
                        Belum ada shift yang dibuat. Klik "Tambah Shift Baru" untuk membuat shift pertama.
                    </p>
                </div>
            </template>
            <div x-show="shifts.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="shift in shifts" :key="shift.id">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="shift[shift_id]" :value="shift.id" x-model="formData.shift.shift_id"
                            class="peer sr-only">
                        <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-indigo-300 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition-all">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-gray-900" x-text="shift.name"></span>
                                <template x-if="shift.color">
                                    <span class="w-4 h-4 rounded-full" :style="'background-color: ' + shift.color"></span>
                                </template>
                            </div>
                            <div class="text-sm text-gray-500 space-y-1">
                                <p class="flex items-center">
                                    <i class="fa-solid fa-sign-in-alt w-5 text-green-500"></i>
                                    <span x-text="shift.start_time || '--:--'"></span>
                                </p>
                                <p class="flex items-center">
                                    <i class="fa-solid fa-sign-out-alt w-5 text-red-500"></i>
                                    <span x-text="shift.end_time || '--:--'"></span>
                                </p>
                            </div>
                            <template x-if="shift.is_night_shift">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 mt-2">
                                    <i class="fa-solid fa-moon mr-1"></i> Shift Malam
                                </span>
                            </template>
                        </div>
                    </label>
                </template>
            </div>
        </div>

        {{-- Tanggal Mulai Shift --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai Shift</label>
                <input type="date" name="shift[start_date]" x-model="formData.shift.start_date"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Hari Otomatis</label>
                <input type="number" x-model="formData.shift.schedule_days" min="1" max="365"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="mt-1 text-xs text-gray-500">Shift akan di-assign otomatis untuk jumlah hari ini</p>
            </div>
        </div>

        <input type="hidden" name="shift[schedule_days]" :value="formData.shift.schedule_days">
    </div>

    <div x-show="formData.shift.skip" class="p-6 bg-gray-50 rounded-lg text-center">
        <i class="fa-solid fa-clock text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">Shift dapat diatur nanti melalui profil karyawan</p>
    </div>

    {{-- Toast Notification --}}
    <div x-show="showToastFlag" x-cloak
         class="fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 transition-all"
         :class="toastType === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
         style="display: none;">
        <i :class="toastType === 'success' ? 'fa-solid fa-check-circle' : 'fa-solid fa-exclamation-circle'"></i>
        <span x-text="toastMessage"></span>
    </div>

    {{-- Modal Tambah Shift --}}
    <div x-show="showShiftModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="fixed inset-0 bg-black/50" @click="closeShiftModal()"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 z-10 border border-gray-200 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fa-solid fa-clock text-indigo-500 mr-2"></i>Tambah Shift Baru
                </h3>
                <button type="button" @click="closeShiftModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Shift <span class="text-red-500">*</span></label>
                        <input type="text" x-model="shiftForm.name" placeholder="Contoh: Shift Pagi"
                               class="w-full px-4 py-2.5 border rounded-lg"
                               :class="shiftErrors.name ? 'border-red-500' : 'border-gray-300'">
                        <template x-if="shiftErrors.name">
                            <p class="mt-1 text-sm text-red-500" x-text="shiftErrors.name[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Shift</label>
                        <input type="text" x-model="shiftForm.code" placeholder="Contoh: S1"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jam Masuk <span class="text-red-500">*</span></label>
                        <input type="time" x-model="shiftForm.start_time"
                               class="w-full px-4 py-2.5 border rounded-lg"
                               :class="shiftErrors.start_time ? 'border-red-500' : 'border-gray-300'">
                        <template x-if="shiftErrors.start_time">
                            <p class="mt-1 text-sm text-red-500" x-text="shiftErrors.start_time[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jam Pulang <span class="text-red-500">*</span></label>
                        <input type="time" x-model="shiftForm.end_time"
                               class="w-full px-4 py-2.5 border rounded-lg"
                               :class="shiftErrors.end_time ? 'border-red-500' : 'border-gray-300'">
                        <template x-if="shiftErrors.end_time">
                            <p class="mt-1 text-sm text-red-500" x-text="shiftErrors.end_time[0]"></p>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Toleransi Terlambat (menit)</label>
                        <input type="number" x-model="shiftForm.late_tolerance_minutes" value="15" min="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Toleransi Pulang Awal (menit)</label>
                        <input type="number" x-model="shiftForm.early_out_tolerance_minutes" value="0" min="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" x-model="shiftForm.is_night_shift"
                            class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="ml-3 text-gray-700">Shift Lintas Hari (Overnight)</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500 ml-8">Aktifkan jika jam pulang lebih dari tengah malam (contoh: 22:00 - 06:00)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Warna</label>
                    <div class="flex items-center gap-3">
                        <input type="color" x-model="shiftForm.color" value="#4F46E5"
                               class="w-12 h-10 rounded cursor-pointer">
                        <span class="text-sm text-gray-500">Pilih warna untuk identifikasi shift</span>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click.prevent="closeShiftModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                <button type="button" @click.prevent="saveShift()" :disabled="savingShift"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg disabled:opacity-50 flex items-center gap-2">
                    <span x-show="savingShift"><i class="fa-solid fa-spinner fa-spin"></i></span>
                    <span x-text="savingShift ? 'Menyimpan...' : 'Simpan & Gunakan'"></span>
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
            console.log('[WIZARD-STEP4] Response:', response.status);
            return response.json().then(function(json) {
                console.log('[WIZARD-STEP4] Full response:', json);
                console.group('[WIZARD-STEP4] Response');
                console.log('Success:', json.success);
                console.log('Message:', json.message);
                console.log('Errors:', json.errors);
                if (json.debug) console.log('Debug:', json.debug);
                console.groupEnd();
                if (!response.ok || !json.success) {
                    console.error('[WIZARD-STEP4] FAILED:', json);
                    if (json.debug && json.debug.exception) console.error('[WIZARD-STEP4] Exception:', json.debug.exception);
                    if (json.debug && json.debug.sql) console.error('[WIZARD-STEP4] SQL:', json.debug.sql);
                    throw json;
                }
                return json;
            });
        })
        .catch(function(error) {
            console.error('[WIZARD-STEP4] Fetch error:', error);
            throw error;
        });
    }
}

document.addEventListener('alpine:init', function() {
    Alpine.data('shiftStep', function() {
        return {
            shifts: [],
            showShiftModal: false,
            savingShift: false,
            shiftErrors: {},
            shiftForm: {
                name: '',
                code: '',
                start_time: '08:00',
                end_time: '17:00',
                late_tolerance_minutes: 15,
                early_out_tolerance_minutes: 0,
                is_night_shift: false,
                color: '#4F46E5'
            },
            toastMessage: '',
            toastType: 'success',
            toastTimeout: null,
            showToastFlag: false,

            init: function() {
                console.log('[WIZARD-STEP4] Initializing...');
                var rShifts = @json($wizardShifts);
                this.shifts = rShifts.map(function(s) {
                    return {
                        id: String(s.id),
                        name: s.name,
                        code: s.code || '',
                        start_time: s.start_time,
                        end_time: s.end_time,
                        is_night_shift: s.is_night_shift || false,
                        color: s.color || '#4F46E5'
                    };
                });
                console.log('[WIZARD-STEP4] Loaded shifts:', this.shifts.length);
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

            openShiftModal: function() {
                console.log('[WIZARD-STEP4] Opening modal');
                this.shiftForm = {
                    name: '',
                    code: '',
                    start_time: '08:00',
                    end_time: '17:00',
                    late_tolerance_minutes: 15,
                    early_out_tolerance_minutes: 0,
                    is_night_shift: false,
                    color: '#4F46E5'
                };
                this.shiftErrors = {};
                this.showShiftModal = true;
            },

            closeShiftModal: function() {
                console.log('[WIZARD-STEP4] Closing modal');
                this.showShiftModal = false;
                this.shiftForm = {
                    name: '',
                    code: '',
                    start_time: '08:00',
                    end_time: '17:00',
                    late_tolerance_minutes: 15,
                    early_out_tolerance_minutes: 0,
                    is_night_shift: false,
                    color: '#4F46E5'
                };
                this.shiftErrors = {};
            },

            saveShift: function() {
                var self = this;
                console.log('[WIZARD-STEP4] saveShift() called, data:', self.shiftForm);

                self.savingShift = true;
                self.shiftErrors = {};

                wizardAjax('{{ route("administrasi.data_karyawan.wizard.quick-shift") }}', self.shiftForm)
                .then(function(response) {
                    console.log('[WIZARD-STEP4] Success:', response);
                    var newShift = response.data;
                    console.log('[WIZARD-STEP4] New shift:', newShift);
                    self.shifts.push(newShift);
                    self.formData.shift.shift_id = String(newShift.id);
                    self.closeShiftModal();
                    self.showToast('Shift berhasil dibuat dan otomatis dipilih');
                })
                .catch(function(error) {
                    console.error('[WIZARD-STEP4] Error:', error);
                    var errorMessage = 'Gagal menyimpan shift';
                    if (error.errors) {
                        self.shiftErrors = error.errors;
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
                    self.savingShift = false;
                });
            }
        };
    });
});
</script>
@endpush
