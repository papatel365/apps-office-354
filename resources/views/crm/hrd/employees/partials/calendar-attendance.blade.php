{{-- Employee Attendance Calendar Component --}}
@props(['employeeId' => null])

@php
    $timezone = 'Asia/Jakarta';
    $now = now()->timezone($timezone);
@endphp

<div x-data="employeeAttendanceCalendar('{{ $employeeId }}')" class="space-y-4">
    {{-- Calendar Controls --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <button @click="prevPeriod()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <h2 class="text-lg font-semibold text-gray-800 min-w-[140px] text-center" x-text="periodLabel"></h2>
                <button @click="nextPeriod()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button @click="goToToday()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium transition-colors">
                    Hari Ini
                </button>

                <div class="flex items-center gap-1">
                    <button @click="period = 'week'; loadCalendarData()"
                            :class="period === 'week' ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            class="px-3 py-1.5 text-sm border rounded-lg transition-colors">
                        Minggu
                    </button>
                    <button @click="period = 'month'; loadCalendarData()"
                            :class="period === 'month' ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            class="px-3 py-1.5 text-sm border rounded-lg transition-colors">
                        Bulan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Calendar Grid --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
            <template x-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']" :key="day">
                <div class="px-2 py-3 text-center text-sm font-medium text-gray-600" x-text="day"></div>
            </template>
        </div>

        <div class="grid grid-cols-7" x-show="calendarData.length > 0">
            <template x-for="(day, idx) in calendarData" :key="day.date">
                <div @click="day.is_padding ? null : showDetail(day)"
                     :class="{
                         'border-b border-r border-gray-200': idx % 7 !== 6,
                         'border-b border-gray-200': idx % 7 === 6,
                         'bg-indigo-50 border-2 border-indigo-500': day.is_today && !day.is_padding,
                         'opacity-30': day.is_padding,
                         'opacity-50': day.is_padding || day.status === 'future',
                         'hover:bg-gray-50 cursor-pointer': !day.is_padding,
                         'cursor-default': day.is_padding
                     }"
                     class="min-h-[100px] p-2 transition-colors">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium"
                              :class="{
                                  'text-indigo-600': day.is_today && !day.is_padding,
                                  'text-gray-300': day.is_padding,
                                  'text-gray-700': !day.is_today && !day.is_padding
                              }"
                              x-text="day.day"></span>
                        <span x-show="!day.is_padding && day.status !== 'future'"
                              class="w-2 h-2 rounded-full"
                              :class="{
                                  'bg-green-500': day.status === 'present',
                                  'bg-yellow-500': day.status === 'late',
                                  'bg-blue-500': day.status === 'leave' || day.status === 'sick',
                                  'bg-red-500': day.status === 'absent',
                                  'bg-purple-500': day.status === 'weekend',
                                  'bg-gray-400': day.status === 'not_yet',
                                  'bg-gray-300': day.status === 'future'
                              }"></span>
                    </div>
                    <div x-show="day.attendance && !day.is_padding" class="text-xs">
                        <div class="font-medium truncate" :class="statusColor(day.status)" x-text="statusLabel(day.status)"></div>
                        <div class="text-gray-500 truncate" x-text="day.attendance?.check_in || '-'"></div>
                    </div>
                    <div x-show="day.status === 'weekend' && !day.attendance && !day.is_padding" class="text-xs text-purple-500">
                        <i class="fa-solid fa-bed mr-1"></i>Libur
                    </div>
                    <div x-show="day.day_of_week === 0 && day.attendance && !day.is_padding" class="text-xs">
                        <span class="inline-flex items-center px-1.5 py-0.5 bg-purple-100 text-purple-600 rounded text-[10px] font-medium">
                            <i class="fa-solid fa-bed mr-0.5"></i>Libur
                        </span>
                    </div>
                    <div x-show="day.status === 'future' && !day.is_padding" class="text-xs text-gray-400">-</div>
                    <div x-show="day.status === 'not_yet' && !day.attendance && !day.is_padding" class="text-xs text-gray-400">
                        <i class="fa-solid fa-circle-minus mr-1"></i>Alpha
                    </div>
                    <div x-show="day.is_padding" class="text-xs text-gray-200">
                        <i class="fa-solid fa-ellipsis"></i>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="calendarData.length === 0" class="p-8 text-center">
            <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-400"></i>
            <p class="mt-2 text-gray-500">Memuat data...</p>
        </div>

        <div x-show="calendarData.length > 0 && hasNoAttendance" class="p-8 text-center">
            <i class="fa-solid fa-calendar-xmark text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Tidak ada data absensi untuk periode ini</p>
        </div>
    </div>

    {{-- ATTENDANCE DETAIL MODAL --}}
    <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div x-show="showDetailModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-black/50" @click="showDetailModal = false"></div>
        <div x-show="showDetailModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden z-10 flex flex-col">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-calendar-check text-white text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Detail Absensi</h3>
                        <p class="text-indigo-200 text-sm" x-text="selectedDay?.day_name + ', ' + selectedDay?.date"></p>
                    </div>
                </div>
                <button @click="showDetailModal = false" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-times text-white"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6" x-show="selectedDay">
                <template x-if="selectedDay">
                    <div class="space-y-6">
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-5">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold shadow-sm" :class="{
                                        'bg-green-100 text-green-700': selectedDay.status === 'present',
                                        'bg-yellow-100 text-yellow-700': selectedDay.status === 'late',
                                        'bg-blue-100 text-blue-700': selectedDay.status === 'leave' || selectedDay.status === 'sick',
                                        'bg-red-100 text-red-700': selectedDay.status === 'absent',
                                        'bg-gray-100 text-gray-600': selectedDay.status === 'not_yet' || !selectedDay.attendance,
                                        'bg-indigo-100 text-indigo-700': selectedDay.status === 'future'
                                    }">
                                        <span x-text="selectedDay.attendance ? statusLabel(selectedDay.status) : (selectedDay.status === 'weekend' ? 'Libur' : (selectedDay.status === 'future' ? 'Akan Datang' : 'Tidak Ada Absensi'))"></span>
                                    </span>
                                    <div class="flex gap-6">
                                        <div class="text-center">
                                            <p class="text-xs text-gray-500 mb-1">Check In</p>
                                            <p class="text-xl font-bold text-gray-800" x-text="selectedDay.attendance?.check_in || '-'"></p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xs text-gray-500 mb-1">Check Out</p>
                                            <p class="text-xl font-bold text-gray-800" x-text="selectedDay.attendance?.check_out || '-'"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <template x-if="selectedDay.attendance?.working_hours">
                                        <div class="bg-white rounded-lg px-4 py-2 text-center shadow-sm">
                                            <p class="text-xs text-gray-500">Durasi</p>
                                            <p class="font-bold text-indigo-600" x-text="selectedDay.attendance?.working_hours + ' jam'"></p>
                                        </div>
                                    </template>
                                    <template x-if="selectedDay.attendance?.late_minutes > 0">
                                        <div class="bg-red-50 rounded-lg px-4 py-2 text-center border border-red-200">
                                            <p class="text-xs text-red-500">Terlambat</p>
                                            <p class="font-bold text-red-600" x-text="selectedDay.attendance?.late_minutes + ' menit'"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-100">
                                <h4 class="font-bold text-blue-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-sign-in-alt"></i> Check In</h4>
                                <template x-if="selectedDay.attendance?.check_in_photo">
                                    <div class="relative group cursor-pointer mb-4" @click="openPhotoModal(selectedDay.attendance.check_in_photo)">
                                        <img :src="'/storage/' + selectedDay.attendance.check_in_photo" class="w-full h-48 object-cover rounded-xl shadow-lg">
                                    </div>
                                </template>
                                <template x-if="!selectedDay.attendance?.check_in_photo && selectedDay.status !== 'weekend'">
                                    <div class="w-full h-48 bg-gray-200 rounded-xl flex items-center justify-center mb-4">
                                        <div class="text-center text-gray-400"><i class="fa-solid fa-camera-slash text-4xl mb-2"></i><p class="text-sm">Belum Check In</p></div>
                                    </div>
                                </template>
                                <template x-if="selectedDay.status === 'weekend' && !selectedDay.attendance">
                                    <div class="w-full h-48 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                                        <div class="text-center text-purple-400"><i class="fa-solid fa-bed text-4xl mb-2"></i><p class="text-sm">Hari Libur</p></div>
                                    </div>
                                </template>
                                <div class="space-y-2">
                                    <div class="flex items-start gap-2"><i class="fa-solid fa-map-marker-alt text-red-500 mt-1"></i><div class="flex-1"><p class="text-xs text-gray-500">Alamat</p><p class="text-sm font-medium text-gray-800" x-text="selectedDay.attendance?.check_in_address || '-'"></p></div></div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="bg-white rounded-lg p-2 text-center shadow-sm"><p class="text-xs text-gray-500">Latitude</p><p class="text-sm font-mono font-medium" x-text="selectedDay.attendance?.check_in_latitude ? parseFloat(selectedDay.attendance.check_in_latitude).toFixed(6) : '-'"></p></div>
                                        <div class="bg-white rounded-lg p-2 text-center shadow-sm"><p class="text-xs text-gray-500">Longitude</p><p class="text-sm font-mono font-medium" x-text="selectedDay.attendance?.check_in_longitude ? parseFloat(selectedDay.attendance.check_in_longitude).toFixed(6) : '-'"></p></div>
                                        <div class="bg-white rounded-lg p-2 text-center shadow-sm"><p class="text-xs text-gray-500">Akurasi</p><p class="text-sm font-medium" x-text="selectedDay.attendance?.check_in_gps_accuracy ? selectedDay.attendance.check_in_gps_accuracy + 'm' : '-'"></p></div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl p-5 border border-red-100">
                                <h4 class="font-bold text-red-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-sign-out-alt"></i> Check Out</h4>
                                <template x-if="selectedDay.attendance?.check_out_photo">
                                    <div class="relative group cursor-pointer mb-4" @click="openPhotoModal(selectedDay.attendance.check_out_photo)">
                                        <img :src="'/storage/' + selectedDay.attendance.check_out_photo" class="w-full h-48 object-cover rounded-xl shadow-lg">
                                    </div>
                                </template>
                                <template x-if="!selectedDay.attendance?.check_out_photo && selectedDay.status !== 'weekend'">
                                    <div class="w-full h-48 bg-gray-200 rounded-xl flex items-center justify-center mb-4">
                                        <div class="text-center text-gray-400"><i class="fa-solid fa-camera-slash text-4xl mb-2"></i><p class="text-sm">Belum Check Out</p></div>
                                    </div>
                                </template>
                                <template x-if="selectedDay.status === 'weekend' && !selectedDay.attendance">
                                    <div class="w-full h-48 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                                        <div class="text-center text-purple-400"><i class="fa-solid fa-bed text-4xl mb-2"></i><p class="text-sm">Hari Libur</p></div>
                                    </div>
                                </template>
                                <div class="space-y-2">
                                    <div class="flex items-start gap-2"><i class="fa-solid fa-map-marker-alt text-red-500 mt-1"></i><div class="flex-1"><p class="text-xs text-gray-500">Alamat</p><p class="text-sm font-medium text-gray-800" x-text="selectedDay.attendance?.check_out_address || '-'"></p></div></div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="bg-white rounded-lg p-2 text-center shadow-sm"><p class="text-xs text-gray-500">Latitude</p><p class="text-sm font-mono font-medium" x-text="selectedDay.attendance?.check_out_latitude ? parseFloat(selectedDay.attendance.check_out_latitude).toFixed(6) : '-'"></p></div>
                                        <div class="bg-white rounded-lg p-2 text-center shadow-sm"><p class="text-xs text-gray-500">Longitude</p><p class="text-sm font-mono font-medium" x-text="selectedDay.attendance?.check_out_longitude ? parseFloat(selectedDay.attendance.check_out_longitude).toFixed(6) : '-'"></p></div>
                                        <div class="bg-white rounded-lg p-2 text-center shadow-sm"><p class="text-xs text-gray-500">Akurasi</p><p class="text-sm font-medium" x-text="selectedDay.attendance?.check_out_gps_accuracy ? selectedDay.attendance.check_out_gps_accuracy + 'm' : '-'"></p></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border border-green-100">
                                <h4 class="font-bold text-green-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-face-smile"></i> Verifikasi Wajah</h4>
                                <template x-if="selectedDay.attendance">
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">Status</span>
                                            <template x-if="selectedDay.attendance?.is_face_verified">
                                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium"><i class="fa-solid fa-check-circle"></i> Berhasil</span>
                                            </template>
                                            <template x-if="!selectedDay.attendance?.is_face_verified && selectedDay.attendance">
                                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium"><i class="fa-solid fa-times-circle"></i> Gagal</span>
                                            </template>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">Confidence</span>
                                            <span class="font-medium text-gray-800" x-text="selectedDay.attendance?.face_verification_score ? (selectedDay.attendance.face_verification_score * 100).toFixed(0) + '%' : '-'"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!selectedDay.attendance">
                                    <div class="text-center text-gray-400 py-4">
                                        <i class="fa-solid fa-circle-minus text-2xl mb-2"></i>
                                        <p class="text-sm">Tidak Ada Data</p>
                                    </div>
                                </template>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-xl p-5 border border-purple-100">
                                <h4 class="font-bold text-purple-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-mobile-screen"></i> Perangkat</h4>
                                <template x-if="selectedDay.attendance">
                                    <div class="space-y-3">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="bg-white rounded-lg p-2 shadow-sm"><p class="text-xs text-gray-500">Browser</p><p class="text-sm font-medium" x-text="selectedDay.attendance?.check_in_browser || '-'"></p></div>
                                            <div class="bg-white rounded-lg p-2 shadow-sm"><p class="text-xs text-gray-500">OS</p><p class="text-sm font-medium" x-text="selectedDay.attendance?.check_in_os || '-'"></p></div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!selectedDay.attendance">
                                    <div class="text-center text-gray-400 py-4">
                                        <i class="fa-solid fa-circle-minus text-2xl mb-2"></i>
                                        <p class="text-sm">Tidak Ada Data</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    <div x-show="showPhotoModal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80" style="display: none;" @click="showPhotoModal = false">
        <div x-show="showPhotoModal" x-transition class="relative max-w-4xl w-full">
            <button @click="showPhotoModal = false" class="absolute -top-12 right-0 w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors"><i class="fa-solid fa-times text-white text-lg"></i></button>
            <img :src="photoPreviewUrl" class="w-full rounded-xl shadow-2xl" @click.stop>
        </div>
    </div>
</div>

@push('scripts')
<script>
function employeeAttendanceCalendar(employeeId) {
    return {
        period: 'month',
        employeeId: employeeId,
        calendarData: [],
        hasNoAttendance: false,
        currentYear: {{ $now->year }},
        currentMonth: {{ $now->month }},
        currentDay: {{ $now->day }},
        showDetailModal: false,
        selectedDay: null,
        showPhotoModal: false,
        photoPreviewUrl: '',

        init() {
            this.loadCalendarData();
        },

        get periodLabel() {
            if (this.period === 'month') {
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                return months[this.currentMonth - 1] + ' ' + this.currentYear;
            }
            return 'Tahun ' + this.currentYear;
        },

        prevPeriod() {
            if (this.period === 'month') {
                if (this.currentMonth === 1) {
                    this.currentMonth = 12;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
            }
            this.loadCalendarData();
        },

        nextPeriod() {
            if (this.period === 'month') {
                if (this.currentMonth === 12) {
                    this.currentMonth = 1;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
            }
            this.loadCalendarData();
        },

        goToToday() {
            this.currentYear = {{ $now->year }};
            this.currentMonth = {{ $now->month }};
            this.currentDay = {{ $now->day }};
            this.loadCalendarData();
        },

        async loadCalendarData() {
            try {
                const params = new URLSearchParams({
                    period: this.period,
                    year: this.currentYear,
                    month: this.currentMonth,
                    day: this.currentDay,
                });
                if (this.employeeId) {
                    params.append('employee_id', this.employeeId);
                }

                const response = await fetch('/administrasi/absen/calendar-data?' + params);
                const data = await response.json();

                if (data.success) {
                    this.calendarData = data.days;
                    this.hasNoAttendance = data.days.filter(d => d.attendance && !d.is_padding).length === 0;
                }
            } catch (error) {
                console.error('Failed to load calendar data:', error);
            }
        },

        showDetail(day) {
            this.selectedDay = day;
            this.showDetailModal = true;
        },

        statusLabel(status) {
            const labels = {
                'present': 'Hadir',
                'late': 'Terlambat',
                'leave': 'Izin/Cuti',
                'sick': 'Sakit',
                'absent': 'Alpha',
                'not_yet': 'Belum Absen',
                'weekend': 'Libur',
                'future': 'Akan Datang',
            };
            return labels[status] || status;
        },

        statusColor(status) {
            const colors = {
                'present': 'text-green-600',
                'late': 'text-yellow-600',
                'leave': 'text-blue-600',
                'sick': 'text-cyan-600',
                'absent': 'text-red-600',
                'not_yet': 'text-gray-500',
                'weekend': 'text-gray-400',
                'future': 'text-gray-400',
            };
            return colors[status] || 'text-gray-600';
        },

        openPhotoModal(photoPath) {
            this.photoPreviewUrl = '/storage/' + photoPath;
            this.showPhotoModal = true;
        },

        maskIP(ip) {
            if (!ip) return '-';
            const parts = ip.split('.');
            if (parts.length === 4) {
                return parts[0] + '.' + parts[1] + '.xxx.xxx';
            }
            return ip.substring(0, 3) + 'xxx';
        }
    }
}
</script>
@endpush

<style>
[x-cloak] { display: none !important; }
</style>
