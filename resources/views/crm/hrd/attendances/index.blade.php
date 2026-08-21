{{-- resources/views/crm/hrd/attendances/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Data Absensi')

@section('page-title')
    <i></i>Absensi
@endsection

@push('page-actions')
    <a href="{{ route('administrasi.absen.face') }}"
       class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-medium text-sm shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 flex items-center gap-2">
        <i class="fa-solid fa-camera"></i>
        <span>Absen</span>
    </a>
@endpush

{{-- No page-actions for Staff module --}}

@section('content')
<div x-data="attendanceCalendar()" x-init="initCalendarComponent()">
    {{-- Store reference to this component globally --}}
    {{-- Tab Navigation --}}
    <div class="bg-white rounded-xl border border-gray-200 p-1 mb-6">
        <div class="flex gap-1">
            <button @click="activeTab = 'list'"
                    :class="activeTab === 'list' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                    class="px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                <i class="fa-solid fa-list mr-2"></i>Daftar Absensi
            </button>
            <button @click="activeTab = 'calendar'; initChart()"
                    :class="activeTab === 'calendar' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                    class="px-6 py-2 py-2.5 rounded-lg font-medium text-sm transition-colors">
                <i class="fa-solid fa-calendar-alt mr-2"></i>Kalender
            </button>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- CALENDAR VIEW                                              --}}
    {{-- ======================================================= --}}
    <div x-show="activeTab === 'calendar'" x-transition x-cloak>
        {{-- Calendar Controls --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            {{-- Employee Filter (Admin/Director only - not shown for own scope) --}}
            @if($canViewAll && $employees->count() > 0)
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Filter Karyawan</label>
                <select x-model="selectedEmployeeId"
                        @change="loadCalendarData()"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-full md:w-auto">
                    <!-- <option value="">Semua Karyawan</option> -->
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name ?? $emp->user?->name ?? 'ID: ' . $emp->id }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Period Filter & Navigation --}}
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <button @click="prevPeriod()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <h2 class="text-lg font-semibold text-gray-800 min-w-[140px] text-center" x-text="periodLabel"></h2>
                    <button @click="nextPeriod()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>

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
                    <button @click="period = 'year'; loadCalendarData()"
                            :class="period === 'year' ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            class="px-3 py-1.5 text-sm border rounded-lg transition-colors">
                        Tahun
                    </button>
                </div>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
            {{-- Month/Week Calendar --}}
            <div x-show="period === 'month' || period === 'week'" x-transition>
                {{-- Day Headers - Sunday First (Indonesian Standard) --}}
                <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
                    <template x-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']" :key="day">
                        <div class="px-2 py-3 text-center text-sm font-medium text-gray-600" x-text="day"></div>
                    </template>
                </div>

                {{-- Calendar Days - Proper Grid Alignment --}}
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

                            {{-- Day Number & Status --}}
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
                                          'bg-gray-400': day.status === 'weekend',
                                          'bg-gray-300': day.status === 'not_yet'
                                      }"></span>
                            </div>

                            {{-- Status Text --}}
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
                            <div x-show="day.status === 'future' && !day.is_padding" class="text-xs text-gray-400">
                                -
                            </div>
                            <div x-show="day.status === 'not_yet' && !day.attendance && !day.is_padding" class="text-xs text-gray-400">
                                <i class="fa-solid fa-circle-minus mr-1"></i>Alpha
                            </div>
                            <div x-show="day.is_padding" class="text-xs text-gray-200">
                                <i class="fa-solid fa-ellipsis"></i>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Loading --}}
                <div x-show="calendarData.length === 0" class="p-8 text-center">
                    <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-400"></i>
                    <p class="mt-2 text-gray-500">Memuat data...</p>
                </div>
            </div>

            {{-- Year View --}}
            <div x-show="period === 'year'" x-transition class="p-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <template x-for="monthData in yearMonths" :key="monthData.month">
                        <div @click="goToMonth(monthData.month)"
                             class="bg-gray-50 hover:bg-gray-100 rounded-lg p-4 cursor-pointer transition-colors">
                            <h3 class="font-semibold text-gray-800" x-text="monthData.label"></h3>
                            <div class="mt-2 flex gap-2 flex-wrap">
                                <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full">
                                    <span x-text="monthData.present"></span> Hadir
                                </span>
                                <span class="text-xs px-2 py-0.5 bg-red-100 text-red-700 rounded-full">
                                    <span x-text="monthData.notPresent"></span> Tidak Hadir
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Employee Info --}}
        <div x-show="currentEmployee" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-indigo-200 flex items-center justify-center text-indigo-700 font-bold text-lg">
                    <span x-text="currentEmployee?.name ? currentEmployee.name.charAt(0).toUpperCase() : '?'"></span>
                </div>
                <div>
                    <h3 class="font-semibold text-indigo-900" x-text="currentEmployee?.name || 'Karyawan'"></h3>
                    <p class="text-sm text-indigo-600">
                        <span x-text="currentEmployee?.nik || '-'"></span>
                        <span x-show="currentEmployee?.department"> - <span x-text="currentEmployee?.department"></span></span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Attendance Statistics Chart --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fa-solid fa-chart-bar mr-2 text-blue-600"></i>
                    Statistik Kehadiran
                </h3>
            </div>
            <div class="p-6">
                <div class="flex flex-col lg:flex-row gap-6 items-center">
                    {{-- Chart --}}
                    <div class="w-full lg:w-1/2 flex justify-center">
                        <div class="relative w-full" style="height: 250px; min-height: 250px;">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                    {{-- Legend Cards - Clickable with same logic as List View --}}
                    <div class="flex-1 grid grid-cols-2 gap-3">
                        {{-- Hadir Card - Clickable --}}
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center cursor-pointer hover:shadow-md hover:bg-green-100 transition-all"
                             @if($canViewAll) @click="$refs.presentModal.openPresentModal()" @endif>
                            <p class="text-3xl font-bold text-green-600" x-text="calendarStats.presentCount">0</p>
                            <p class="text-sm text-green-700 mt-1">Hadir</p>
                            @if($canViewAll)
                            <p class="text-xs text-green-600 mt-2 opacity-70">Klik untuk lihat daftar</p>
                            @endif
                        </div>
                        {{-- Tidak Hadir Card - Clickable --}}
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center cursor-pointer hover:shadow-md hover:bg-red-100 transition-all"
                             @if($canViewAll) @click="$refs.notPresentModal.openNotPresentModal()" @endif>
                            <p class="text-3xl font-bold text-red-600" x-text="calendarStats.notPresentCount">0</p>
                            <p class="text-sm text-red-700 mt-1">Tidak Hadir</p>
                            @if($canViewAll)
                            <p class="text-xs text-red-600 mt-2 opacity-70">Klik untuk lihat daftar</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- CALENDAR POPUP MODALS - REUSE FROM LIST VIEW          --}}
        {{-- ======================================================= --}}
        {{-- Hadir Popup Modal for Calendar Tab --}}
        <div x-data="calendarPresentModal()" x-ref="presentModal">
            <div x-show="showPresentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                <div x-show="showPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-black/50" @click="showPresentModal = false"></div>
                <div x-show="showPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden z-10 flex flex-col">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 px-6 py-4 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-user-check text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Karyawan Hadir</h3>
                                <p class="text-green-100 text-sm" x-text="selectedPeriod"></p>
                            </div>
                        </div>
                        <button @click="showPresentModal = false" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors">
                            <i class="fa-solid fa-times text-white"></i>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4">
                        <div x-show="presentLoading" class="text-center py-8">
                            <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-400"></i>
                            <p class="mt-2 text-gray-500">Memuat data...</p>
                        </div>
                        <div x-show="!presentLoading && presentList.length === 0" class="text-center py-8">
                            <i class="fa-solid fa-user-slash text-4xl text-gray-300"></i>
                            <p class="mt-2 text-gray-500">Tidak ada karyawan yang hadir</p>
                        </div>
                        <div x-show="!presentLoading && presentList.length > 0" class="space-y-3">
                            <template x-for="emp in presentList" :key="emp.id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-lg shrink-0 overflow-hidden">
                                        <template x-if="emp.photo">
                                            <img :src="'/storage/' + emp.photo" class="w-full h-full object-cover" alt="">
                                        </template>
                                        <template x-if="!emp.photo">
                                            <span x-text="emp.name ? emp.name.charAt(0).toUpperCase() : '?'"></span>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate" x-text="emp.name"></p>
                                        <p class="text-xs text-gray-500" x-text="emp.employee_number + ' - ' + emp.department"></p>
                                        <template x-if="emp.total_days">
                                            <p class="text-xs text-green-600 mt-1" x-text="emp.total_days + ' hari hadir'"></p>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 shrink-0">
                        <p class="text-sm text-gray-500 text-center" x-text="presentList.length + ' karyawan hadir'"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tidak Hadir Popup Modal for Calendar Tab --}}
        <div x-data="calendarNotPresentModal()" x-ref="notPresentModal">
            <div x-show="showNotPresentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                <div x-show="showNotPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-black/50" @click="showNotPresentModal = false"></div>
                <div x-show="showNotPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden z-10 flex flex-col">
                    <div class="bg-gradient-to-r from-red-500 to-rose-500 px-6 py-4 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-user-xmark text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Karyawan Tidak Hadir</h3>
                                <p class="text-red-100 text-sm" x-text="selectedPeriod"></p>
                            </div>
                        </div>
                        <button @click="showNotPresentModal = false" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors">
                            <i class="fa-solid fa-times text-white"></i>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4">
                        <div x-show="notPresentLoading" class="text-center py-8">
                            <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-400"></i>
                            <p class="mt-2 text-gray-500">Memuat data...</p>
                        </div>
                        <div x-show="!notPresentLoading && notPresentList.length === 0" class="text-center py-8">
                            <i class="fa-solid fa-check-circle text-4xl text-green-300"></i>
                            <p class="mt-2 text-gray-500">Semua karyawan hadir!</p>
                        </div>
                        <div x-show="!notPresentLoading && notPresentList.length > 0" class="space-y-3">
                            <template x-for="emp in notPresentList" :key="emp.id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-lg shrink-0 overflow-hidden">
                                        <template x-if="emp.photo">
                                            <img :src="'/storage/' + emp.photo" class="w-full h-full object-cover" alt="">
                                        </template>
                                        <template x-if="!emp.photo">
                                            <span x-text="emp.name ? emp.name.charAt(0).toUpperCase() : '?'"></span>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate" x-text="emp.name"></p>
                                        <p class="text-xs text-gray-500" x-text="emp.employee_number + ' - ' + emp.department"></p>
                                    </div>
                                    <div class="shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <i class="fa-solid fa-circle-minus mr-1"></i>
                                            Alpha
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 shrink-0">
                        <p class="text-sm text-gray-500 text-center" x-text="notPresentList.length + ' karyawan tidak hadir'"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- LIST VIEW (EXISTING)                                 --}}
    {{-- ======================================================= --}}
    <div x-show="activeTab === 'list'" x-transition x-data="attendanceStatsPopup()">
        {{-- Stats Cards - Clickable --}}
        <div class="grid grid-cols-2 gap-4 mb-6">
            {{-- Hadir Card --}}
            <div class="bg-green-50 border border-green-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:bg-green-100 transition-all cursor-pointer"
                 @if($canViewAll) @click="openPresentModal()" @endif>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-green-700">Hadir</p>
                        <p class="text-3xl font-bold text-green-600 mt-1" x-text="presentCount">{{ $stats['present'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                        <i class="fa-solid fa-user-check text-xl text-green-600"></i>
                    </div>
                </div>
                @if($canViewAll)
                <p class="text-xs text-green-600 mt-2 opacity-70">Klik untuk melihat daftar</p>
                @endif
            </div>

            {{-- Tidak Hadir Card --}}
            <div class="bg-red-50 border border-red-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:bg-red-100 transition-all cursor-pointer"
                 @if($canViewAll) @click="openNotPresentModal()" @endif>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-red-700">Tidak Hadir</p>
                        <p class="text-3xl font-bold text-red-600 mt-1" x-text="notPresentCount">{{ $stats['total'] - $stats['present'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                        <i class="fa-solid fa-user-xmark text-xl text-red-600"></i>
                    </div>
                </div>
                @if($canViewAll)
                <p class="text-xs text-red-600 mt-2 opacity-70">Klik untuk melihat daftar</p>
                @endif
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex-shrink-0">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}"
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                @if($departments->count() > 0)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                    <select name="department" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="all">Semua</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $department == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="all">Semua</option>
                        <option value="present" {{ $status == 'present' ? 'selected' : '' }}>Hadir</option>
                        <option value="not_present" {{ $status == 'not_present' ? 'selected' : '' }}>Tidak Hadir</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
                        <i class="fa-solid fa-search mr-1"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        {{-- Attendance Table --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check In</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check Out</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($attendances as $emp)
                            @php
                                $attendance = $emp->attendance;
                                $hasAttendance = $emp->has_attendance ?? false;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-medium">
                                            {{ strtoupper(substr($emp->full_name ?? $emp->user?->name ?? 'N', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $emp->full_name ?? $emp->user?->name ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">{{ $emp->nik ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @if($hasAttendance && $attendance->check_in_formatted)
                                        <div>{{ $attendance->check_in_formatted }}</div>
                                        @if($attendance->is_outside_radius)
                                            <span class="text-xs text-orange-600">
                                                <i class="fa-solid fa-map-marker-alt"></i>
                                                {{ $attendance->distance_formatted }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @if($hasAttendance && $attendance->check_out_formatted)
                                        {{ $attendance->check_out_formatted }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @if($hasAttendance && $attendance->placement)
                                        <span class="inline-flex items-center gap-1">
                                            <i class="fa-solid fa-map-marker-alt text-gray-400"></i>
                                            {{ $attendance->placement->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($hasAttendance)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $attendance->status_badge_class }}">
                                            {{ $attendance->status_label }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            Belum Absen
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($hasAttendance)
                                        <div class="flex items-center gap-2">
                                            @if($attendance->is_face_verified)
                                                <span class="inline-flex items-center gap-1 text-xs text-green-600">
                                                    <i class="fa-solid fa-face-smile"></i>
                                                    Wajah
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                                    <i class="fa-solid fa-face-meh"></i>
                                                    -
                                                </span>
                                            @endif
                                            @if($attendance->is_location_verified === false)
                                                <span class="inline-flex items-center gap-1 text-xs text-orange-600">
                                                    <i class="fa-solid fa-map-location-dot"></i>
                                                    GPS
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fa-solid fa-calendar-check text-4xl text-gray-300 mb-3"></i>
                                        <p class="text-gray-500">Belum ada data absensi</p>
                                        <a href="{{ route('administrasi.absen.face') }}" class="mt-3 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                            <i class="fa-solid fa-camera mr-2"></i>
                                            Mulai Absensi
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($attendances->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $attendances->links() }}
                </div>
            @endif

            {{-- ======================================================= --}}
            {{-- HADIR POPUP MODAL                                     --}}
            {{-- ======================================================= --}}
            <div x-show="showPresentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                <div x-show="showPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-black/50" @click="showPresentModal = false"></div>
                <div x-show="showPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden z-10 flex flex-col">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 px-6 py-4 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-user-check text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Karyawan Hadir</h3>
                                <p class="text-green-100 text-sm" x-text="selectedDate"></p>
                            </div>
                        </div>
                        <button @click="showPresentModal = false" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors">
                            <i class="fa-solid fa-times text-white"></i>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4">
                        <div x-show="presentLoading" class="text-center py-8">
                            <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-400"></i>
                            <p class="mt-2 text-gray-500">Memuat data...</p>
                        </div>
                        <div x-show="!presentLoading && presentList.length === 0" class="text-center py-8">
                            <i class="fa-solid fa-user-slash text-4xl text-gray-300"></i>
                            <p class="mt-2 text-gray-500">Tidak ada karyawan yang hadir</p>
                        </div>
                        <div x-show="!presentLoading && presentList.length > 0" class="space-y-3">
                            <template x-for="emp in presentList" :key="emp.id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-lg shrink-0 overflow-hidden">
                                        <template x-if="emp.photo">
                                            <img :src="'/storage/' + emp.photo" class="w-full h-full object-cover" alt="">
                                        </template>
                                        <template x-if="!emp.photo">
                                            <span x-text="emp.name ? emp.name.charAt(0).toUpperCase() : '?'"></span>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate" x-text="emp.name"></p>
                                        <p class="text-xs text-gray-500" x-text="emp.employee_number + ' - ' + emp.department"></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                              :class="emp.status === 'late' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'">
                                            <span x-text="emp.status === 'late' ? emp.late_minutes + ' menit' : 'Hadir'"></span>
                                        </span>
                                        <p class="text-xs text-gray-500 mt-1" x-text="emp.check_in"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 shrink-0">
                        <p class="text-sm text-gray-500 text-center" x-text="presentList.length + ' karyawan hadir'"></p>
                    </div>
                </div>
            </div>

            {{-- ======================================================= --}}
            {{-- TIDAK HADIR POPUP MODAL                               --}}
            {{-- ======================================================= --}}
            <div x-show="showNotPresentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                <div x-show="showNotPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-black/50" @click="showNotPresentModal = false"></div>
                <div x-show="showNotPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden z-10 flex flex-col">
                    <div class="bg-gradient-to-r from-red-500 to-rose-500 px-6 py-4 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-user-xmark text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Karyawan Tidak Hadir</h3>
                                <p class="text-red-100 text-sm" x-text="selectedDate"></p>
                            </div>
                        </div>
                        <button @click="showNotPresentModal = false" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors">
                            <i class="fa-solid fa-times text-white"></i>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4">
                        <div x-show="notPresentLoading" class="text-center py-8">
                            <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-400"></i>
                            <p class="mt-2 text-gray-500">Memuat data...</p>
                        </div>
                        <div x-show="!notPresentLoading && notPresentList.length === 0" class="text-center py-8">
                            <i class="fa-solid fa-check-circle text-4xl text-green-300"></i>
                            <p class="mt-2 text-gray-500">Semua karyawan hadir!</p>
                        </div>
                        <div x-show="!notPresentLoading && notPresentList.length > 0" class="space-y-3">
                            <template x-for="emp in notPresentList" :key="emp.id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-lg shrink-0 overflow-hidden">
                                        <template x-if="emp.photo">
                                            <img :src="'/storage/' + emp.photo" class="w-full h-full object-cover" alt="">
                                        </template>
                                        <template x-if="!emp.photo">
                                            <span x-text="emp.name ? emp.name.charAt(0).toUpperCase() : '?'"></span>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate" x-text="emp.name"></p>
                                        <p class="text-xs text-gray-500" x-text="emp.employee_number + ' - ' + emp.department"></p>
                                    </div>
                                    <div class="shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <i class="fa-solid fa-circle-minus mr-1"></i>
                                            Belum Absen
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 shrink-0">
                        <p class="text-sm text-gray-500 text-center" x-text="notPresentList.length + ' karyawan tidak hadir'"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- ATTENDANCE DETAIL MODAL - ENHANCED                    --}}
    {{-- ======================================================= --}}
    <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div x-show="showDetailModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-black/50" @click="showDetailModal = false"></div>
        <div x-show="showDetailModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden z-10 flex flex-col">
            {{-- Modal Header --}}
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
            {{-- Modal Content --}}
            <div class="flex-1 overflow-y-auto p-6" x-show="selectedDay">
                <template x-if="selectedDay">
                    <div class="space-y-6">
                        {{-- Status & Basic Info --}}
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
                                        {{-- Hari Libur badge = Minggu dengan attendance data --}}
                                        <template x-if="selectedDay.day_of_week === 0 && selectedDay.attendance">
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                                <i class="fa-solid fa-bed mr-0.5"></i>Libur
                                            </span>
                                        </template>
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
                        {{-- Check In & Check Out Photos --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-100">
                                <h4 class="font-bold text-blue-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-sign-in-alt"></i> Check In</h4>
                                <template x-if="selectedDay.attendance?.check_in_photo">
                                    <div class="relative group cursor-pointer mb-4" @click="openPhotoModal(selectedDay.attendance.check_in_photo)">
                                        <img :src="'/storage/' + selectedDay.attendance.check_in_photo" class="w-full h-48 object-cover rounded-xl shadow-lg">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 rounded-xl transition-colors flex items-center justify-center"><i class="fa-solid fa-expand text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity"></i></div>
                                    </div>
                                </template>
                                <template x-if="!selectedDay.attendance?.check_in_photo && selectedDay.status !== 'weekend'">
                                    <div class="w-full h-48 bg-gray-200 rounded-xl flex items-center justify-center mb-4">
                                        <div class="text-center text-gray-400"><i class="fa-solid fa-camera-slash text-4xl mb-2"></i><p class="text-sm">Belum Check In</p></div>
                                    </div>
                                </template>
                                {{-- Weekend tanpa attendance = Hari Libur kosong --}}
                                <template x-if="selectedDay.status === 'weekend' && !selectedDay.attendance">
                                    <div class="w-full h-48 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                                        <div class="text-center text-purple-400"><i class="fa-solid fa-bed text-4xl mb-2"></i><p class="text-sm">Hari Libur</p></div>
                                    </div>
                                </template>
                                {{-- Minggu dengan attendance tapi tanpa foto = Belum Check In --}}
                                <template x-if="selectedDay.day_of_week === 0 && selectedDay.attendance && !selectedDay.attendance?.check_in_photo">
                                    <div class="w-full h-48 bg-gray-200 rounded-xl flex items-center justify-center mb-4">
                                        <div class="text-center text-gray-400"><i class="fa-solid fa-camera-slash text-4xl mb-2"></i><p class="text-sm">Belum Check In</p></div>
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
                                <template x-if="selectedDay.attendance?.check_in_latitude && selectedDay.attendance?.check_in_longitude">
                                    <div class="mt-4">
                                        <iframe :src="'https://www.openstreetmap.org/export/embed.html?bbox=' + (parseFloat(selectedDay.attendance.check_in_longitude) - 0.005) + ',' + (parseFloat(selectedDay.attendance.check_in_latitude) - 0.005) + ',' + (parseFloat(selectedDay.attendance.check_in_longitude) + 0.005) + ',' + (parseFloat(selectedDay.attendance.check_in_latitude) + 0.005) + '&layer=mapnik&marker=' + selectedDay.attendance.check_in_latitude + ',' + selectedDay.attendance.check_in_longitude" class="w-full h-40 rounded-lg border-0" style="pointer-events:none;"></iframe>
                                    </div>
                                </template>
                            </div>
                            <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl p-5 border border-red-100">
                                <h4 class="font-bold text-red-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-sign-out-alt"></i> Check Out</h4>
                                <template x-if="selectedDay.attendance?.check_out_photo">
                                    <div class="relative group cursor-pointer mb-4" @click="openPhotoModal(selectedDay.attendance.check_out_photo)">
                                        <img :src="'/storage/' + selectedDay.attendance.check_out_photo" class="w-full h-48 object-cover rounded-xl shadow-lg">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 rounded-xl transition-colors flex items-center justify-center"><i class="fa-solid fa-expand text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity"></i></div>
                                    </div>
                                </template>
                                <template x-if="!selectedDay.attendance?.check_out_photo && selectedDay.status !== 'weekend'">
                                    <div class="w-full h-48 bg-gray-200 rounded-xl flex items-center justify-center mb-4">
                                        <div class="text-center text-gray-400"><i class="fa-solid fa-camera-slash text-4xl mb-2"></i><p class="text-sm">Belum Check Out</p></div>
                                    </div>
                                </template>
                                {{-- Minggu tanpa attendance = Hari Libur kosong --}}
                                <template x-if="selectedDay.status === 'weekend' && !selectedDay.attendance">
                                    <div class="w-full h-48 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                                        <div class="text-center text-purple-400"><i class="fa-solid fa-bed text-4xl mb-2"></i><p class="text-sm">Hari Libur</p></div>
                                    </div>
                                </template>
                                {{-- Minggu dengan attendance tapi tanpa foto = Belum Check Out --}}
                                <template x-if="selectedDay.day_of_week === 0 && selectedDay.attendance && !selectedDay.attendance?.check_out_photo">
                                    <div class="w-full h-48 bg-gray-200 rounded-xl flex items-center justify-center mb-4">
                                        <div class="text-center text-gray-400"><i class="fa-solid fa-camera-slash text-4xl mb-2"></i><p class="text-sm">Belum Check Out</p></div>
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
                                <template x-if="selectedDay.attendance?.check_out_latitude && selectedDay.attendance?.check_out_longitude">
                                    <div class="mt-4">
                                        <iframe :src="'https://www.openstreetmap.org/export/embed.html?bbox=' + (parseFloat(selectedDay.attendance.check_out_longitude) - 0.005) + ',' + (parseFloat(selectedDay.attendance.check_out_latitude) - 0.005) + ',' + (parseFloat(selectedDay.attendance.check_out_longitude) + 0.005) + ',' + (parseFloat(selectedDay.attendance.check_out_latitude) + 0.005) + '&layer=mapnik&marker=' + selectedDay.attendance.check_out_latitude + ',' + selectedDay.attendance.check_out_longitude" class="w-full h-40 rounded-lg border-0" style="pointer-events:none;"></iframe>
                                    </div>
                                </template>
                            </div>
                        </div>
                        {{-- Verification & Device Info --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border border-green-100">
                                <h4 class="font-bold text-green-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-face-smile"></i> Verifikasi Wajah</h4>
                                {{-- Tampilkan data jika ada attendance (termasuk weekend dengan attendance) --}}
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
                                {{-- Weekend tanpa attendance --}}
                                <template x-if="!selectedDay.attendance">
                                    <div class="text-center text-gray-400 py-4">
                                        <i class="fa-solid fa-circle-minus text-2xl mb-2"></i>
                                        <p class="text-sm">Tidak Ada Data</p>
                                    </div>
                                </template>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-xl p-5 border border-purple-100">
                                <h4 class="font-bold text-purple-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-mobile-screen"></i> Perangkat</h4>
                                {{-- Tampilkan data jika ada attendance (termasuk weekend dengan attendance) --}}
                                <template x-if="selectedDay.attendance">
                                    <div class="mb-4">
                                        <p class="text-xs text-gray-500 mb-2 flex items-center gap-1"><i class="fa-solid fa-sign-in-alt text-green-500"></i> Check In</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="bg-white rounded-lg p-2 shadow-sm"><p class="text-xs text-gray-500">Browser</p><p class="text-sm font-medium" x-text="selectedDay.attendance?.check_in_browser || '-'"></p></div>
                                            <div class="bg-white rounded-lg p-2 shadow-sm"><p class="text-xs text-gray-500">OS</p><p class="text-sm font-medium" x-text="selectedDay.attendance?.check_in_os || '-'"></p></div>
                                            <div class="bg-white rounded-lg p-2 shadow-sm col-span-2"><p class="text-xs text-gray-500">IP Address</p><p class="text-sm font-mono" x-text="maskIP(selectedDay.attendance?.check_in_ip)"></p></div>
                                        </div>
                                    </div>
                                    <template x-if="selectedDay.attendance?.check_out_browser">
                                        <div>
                                            <p class="text-xs text-gray-500 mb-2 flex items-center gap-1"><i class="fa-solid fa-sign-out-alt text-red-500"></i> Check Out</p>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div class="bg-white rounded-lg p-2 shadow-sm"><p class="text-xs text-gray-500">Browser</p><p class="text-sm font-medium" x-text="selectedDay.attendance?.check_out_browser || '-'"></p></div>
                                                <div class="bg-white rounded-lg p-2 shadow-sm"><p class="text-xs text-gray-500">OS</p><p class="text-sm font-medium" x-text="selectedDay.attendance?.check_out_os || '-'"></p></div>
                                                <div class="bg-white rounded-lg p-2 shadow-sm col-span-2"><p class="text-xs text-gray-500">IP Address</p><p class="text-sm font-mono" x-text="maskIP(selectedDay.attendance?.check_out_ip)"></p></div>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                                {{-- Weekend tanpa attendance --}}
                                <template x-if="!selectedDay.attendance">
                                    <div class="text-center text-gray-400 py-4">
                                        <i class="fa-solid fa-circle-minus text-2xl mb-2"></i>
                                        <p class="text-sm">Tidak Ada Data</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        {{-- Timezone Info --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-if="selectedDay.attendance?.check_in_timezone">
                                <div class="bg-gradient-to-br from-cyan-50 to-blue-50 rounded-xl p-4 border border-cyan-100">
                                    <h5 class="text-sm font-bold text-cyan-800 mb-2 flex items-center gap-2"><i class="fa-solid fa-globe"></i> Zona Waktu Check In</h5>
                                    <p class="text-lg font-bold text-gray-800" x-text="selectedDay.attendance?.check_in_timezone_name || selectedDay.attendance?.check_in_timezone"></p>
                                    <p class="text-sm text-gray-600" x-text="selectedDay.attendance?.check_in_city + ', ' + selectedDay.attendance?.check_in_province"></p>
                                </div>
                            </template>
                            <template x-if="selectedDay.attendance?.check_out_timezone">
                                <div class="bg-gradient-to-br from-rose-50 to-pink-50 rounded-xl p-4 border border-rose-100">
                                    <h5 class="text-sm font-bold text-rose-800 mb-2 flex items-center gap-2"><i class="fa-solid fa-globe"></i> Zona Waktu Check Out</h5>
                                    <p class="text-lg font-bold text-gray-800" x-text="selectedDay.attendance?.check_out_timezone_name || selectedDay.attendance?.check_out_timezone"></p>
                                    <p class="text-sm text-gray-600" x-text="selectedDay.attendance?.check_out_city + ', ' + selectedDay.attendance?.check_out_province"></p>
                                </div>
                            </template>
                        </div>
                        {{-- Warnings --}}
                        <template x-if="selectedDay.attendance?.is_outside_radius">
                            <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-5 border border-orange-200">
                                <h4 class="font-bold text-orange-800 mb-3 flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i> Peringatan</h4>
                                <div class="flex items-center gap-2 text-orange-700"><i class="fa-solid fa-map-pin"></i><span>Berada <span x-text="selectedDay.attendance?.distance"></span> dari lokasi yang ditentukan</span></div>
                            </div>
                        </template>
                        {{-- Notes --}}
                        <div class="bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl p-5 border border-gray-200">
                            <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-sticky-note"></i> Catatan</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><p class="text-xs text-gray-500 mb-1">Catatan</p><p class="text-sm text-gray-800 bg-white rounded-lg p-3" x-text="selectedDay.attendance?.notes || 'Tidak ada catatan.'"></p></div>
                                <div><p class="text-xs text-gray-500 mb-1">Catatan Persetujuan</p><p class="text-sm text-gray-800 bg-white rounded-lg p-3" x-text="selectedDay.attendance?.approval_notes || 'Tidak ada catatan.'"></p></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    {{-- Photo Preview Modal --}}
    <div x-show="showPhotoModal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80" style="display: none;" @click="showPhotoModal = false">
        <div x-show="showPhotoModal" x-transition class="relative max-w-4xl w-full">
            <button @click="showPhotoModal = false" class="absolute -top-12 right-0 w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors"><i class="fa-solid fa-times text-white text-lg"></i></button>
            <img :src="photoPreviewUrl" class="w-full rounded-xl shadow-2xl" @click.stop>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- HADIR POPUP MODAL                                     --}}
    {{-- ======================================================= --}}
    <div x-show="showPresentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div x-show="showPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-black/50" @click="showPresentModal = false"></div>
        <div x-show="showPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden z-10 flex flex-col">
            <div class="bg-gradient-to-r from-green-500 to-emerald-500 px-6 py-4 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-user-check text-white text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Karyawan Hadir</h3>
                        <p class="text-green-100 text-sm" x-text="selectedDate"></p>
                    </div>
                </div>
                <button @click="showPresentModal = false" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-times text-white"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div x-show="presentLoading" class="text-center py-8">
                    <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-400"></i>
                    <p class="mt-2 text-gray-500">Memuat data...</p>
                </div>
                <div x-show="!presentLoading && presentList.length === 0" class="text-center py-8">
                    <i class="fa-solid fa-user-slash text-4xl text-gray-300"></i>
                    <p class="mt-2 text-gray-500">Tidak ada karyawan yang hadir</p>
                </div>
                <div x-show="!presentLoading && presentList.length > 0" class="space-y-3">
                    <template x-for="emp in presentList" :key="emp.id">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-lg shrink-0 overflow-hidden">
                                <template x-if="emp.photo">
                                    <img :src="'/storage/' + emp.photo" class="w-full h-full object-cover" alt="">
                                </template>
                                <template x-if="!emp.photo">
                                    <span x-text="emp.name.charAt(0).toUpperCase()"></span>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate" x-text="emp.name"></p>
                                <p class="text-xs text-gray-500" x-text="emp.employee_number + ' - ' + emp.department"></p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                      :class="emp.status === 'late' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'">
                                    <span x-text="emp.status === 'late' ? emp.late_minutes + ' menit' : 'Hadir'"></span>
                                </span>
                                <p class="text-xs text-gray-500 mt-1" x-text="emp.check_in"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 shrink-0">
                <p class="text-sm text-gray-500 text-center" x-text="presentList.length + ' karyawan hadir'"></p>
            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- TIDAK HADIR POPUP MODAL                               --}}
    {{-- ======================================================= --}}
    <div x-show="showNotPresentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div x-show="showNotPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-black/50" @click="showNotPresentModal = false"></div>
        <div x-show="showNotPresentModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden z-10 flex flex-col">
            <div class="bg-gradient-to-r from-red-500 to-rose-500 px-6 py-4 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-user-xmark text-white text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Karyawan Tidak Hadir</h3>
                        <p class="text-red-100 text-sm" x-text="selectedDate"></p>
                    </div>
                </div>
                <button @click="showNotPresentModal = false" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-times text-white"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div x-show="notPresentLoading" class="text-center py-8">
                    <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-400"></i>
                    <p class="mt-2 text-gray-500">Memuat data...</p>
                </div>
                <div x-show="!notPresentLoading && notPresentList.length === 0" class="text-center py-8">
                    <i class="fa-solid fa-check-circle text-4xl text-green-300"></i>
                    <p class="mt-2 text-gray-500">Semua karyawan hadir!</p>
                </div>
                <div x-show="!notPresentLoading && notPresentList.length > 0" class="space-y-3">
                    <template x-for="emp in notPresentList" :key="emp.id">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-lg shrink-0 overflow-hidden">
                                <template x-if="emp.photo">
                                    <img :src="'/storage/' + emp.photo" class="w-full h-full object-cover" alt="">
                                </template>
                                <template x-if="!emp.photo">
                                    <span x-text="emp.name.charAt(0).toUpperCase()"></span>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate" x-text="emp.name"></p>
                                <p class="text-xs text-gray-500" x-text="emp.employee_number + ' - ' + emp.department"></p>
                            </div>
                            <div class="shrink-0">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    <i class="fa-solid fa-circle-minus mr-1"></i>
                                    Belum Absen
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 shrink-0">
                <p class="text-sm text-gray-500 text-center" x-text="notPresentList.length + ' karyawan tidak hadir'"></p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function attendanceCalendar() {
    return {
        activeTab: 'list',
        period: 'month',
        selectedEmployeeId: '{{ $currentEmployee?->id ?? '' }}',
        calendarData: [],
        summary: { work_days: 0, present: 0, late: 0, absent: 0, leave: 0, sick: 0, not_yet: 0 },
        currentEmployee: null,
        // Use explicit date for consistent timezone handling (Asia/Jakarta)
        currentYear: {{ \Carbon\Carbon::now('Asia/Jakarta')->year }},
        currentMonth: {{ \Carbon\Carbon::now('Asia/Jakarta')->month }},
        currentDay: {{ \Carbon\Carbon::now('Asia/Jakarta')->day }},
        showDetailModal: false,
        selectedDay: null,
        showPhotoModal: false,
        photoPreviewUrl: '',
        chartInitialized: false,

        init() {
            this.loadCalendarData();
            this.initCalendarComponent();
        },

        // Initialize calendar component reference for other components
        initCalendarComponent() {
            window.attendanceCalendarComponent = this;
        },

        // Calendar Stats - Same logic as List View
        calendarStats: {
            presentCount: 0,
            notPresentCount: 0,
        },

        // Watch for tab changes and init chart when calendar tab is shown
        initChart() {
            if (this.activeTab === 'calendar') {
                // Initialize chart when calendar tab is shown
                setTimeout(() => {
                    this.updateChart();
                }, 100);
            }
        },

        get periodLabel() {
            if (this.period === 'week') {
                const start = this.getWeekStart(this.currentYear, this.currentMonth, this.currentDay);
                const end = new Date(start);
                end.setDate(end.getDate() + 6);
                return `${this.formatDate(start)} - ${this.formatDate(end)}`;
            } else if (this.period === 'month') {
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                return `${months[this.currentMonth - 1]} ${this.currentYear}`;
            } else if (this.period === 'year') {
                return `Tahun ${this.currentYear}`;
            }
            return '';
        },

        yearMonths() {
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const data = [];
            for (let m = 1; m <= 12; m++) {
                const monthData = this.calendarData.filter(d => {
                    const date = new Date(d.date);
                    return date.getFullYear() === this.currentYear && date.getMonth() + 1 === m;
                });

                // Hadir = present (tidak termasuk late)
                const present = monthData.filter(d => d.status === 'present' || d.status === 'late').length;

                // Tidak Hadir = Alpha (absent) + hari kerja tanpa absensi (not_yet)
                // Tidak termasuk: weekend, future, leave, sick, padding
                const notPresent = monthData.filter(d =>
                    d.status === 'absent' || d.status === 'not_yet'
                ).length;

                data.push({
                    month: m,
                    label: months[m - 1],
                    present: present,
                    notPresent: notPresent,
                });
            }
            return data;
        },

        formatDate(date) {
            const d = new Date(date);
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            return `${day}/${month}`;
        },

        getWeekStart(year, month, day) {
            // Sunday as first day of week (matching monthly calendar)
            // Calendar columns: Min(0) | Sen(1) | Sel(2) | Rab(3) | Kam(4) | Jum(5) | Sab(6)
            const date = new Date(year, month - 1, day);
            const dayOfWeek = date.getDay(); // 0=Sunday, 1=Monday, ..., 6=Saturday

            // If today is Sunday, week starts today
            // Otherwise, go back to previous Sunday
            if (dayOfWeek === 0) {
                return date;
            } else {
                // Go back (dayOfWeek) days to reach Sunday
                const sunday = new Date(date);
                sunday.setDate(sunday.getDate() - dayOfWeek);
                return sunday;
            }
        },

        prevPeriod() {
            if (this.period === 'month') {
                if (this.currentMonth === 1) {
                    this.currentMonth = 12;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
            } else if (this.period === 'year') {
                this.currentYear--;
            } else if (this.period === 'week') {
                const monday = this.getWeekStart(this.currentYear, this.currentMonth, this.currentDay);
                monday.setDate(monday.getDate() - 7);
                this.currentYear = monday.getFullYear();
                this.currentMonth = monday.getMonth() + 1;
                this.currentDay = monday.getDate();
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
            } else if (this.period === 'year') {
                this.currentYear++;
            } else if (this.period === 'week') {
                const monday = this.getWeekStart(this.currentYear, this.currentMonth, this.currentDay);
                monday.setDate(monday.getDate() + 7);
                this.currentYear = monday.getFullYear();
                this.currentMonth = monday.getMonth() + 1;
                this.currentDay = monday.getDate();
            }
            this.loadCalendarData();
        },

        goToToday() {
            // Use explicit date for consistent timezone handling (Asia/Jakarta)
            this.currentYear = {{ \Carbon\Carbon::now('Asia/Jakarta')->year }};
            this.currentMonth = {{ \Carbon\Carbon::now('Asia/Jakarta')->month }};
            this.currentDay = {{ \Carbon\Carbon::now('Asia/Jakarta')->day }};
            this.loadCalendarData();
        },

        goToMonth(month) {
            this.currentMonth = month;
            this.period = 'month';
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
                if (this.selectedEmployeeId) {
                    params.append('employee_id', this.selectedEmployeeId);
                }

                const response = await fetch(`/administrasi/absen/calendar-data?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.calendarData = data.days;
                    this.summary = data.summary;
                    this.currentEmployee = data.employee;

                    // Update calendar stats from the same data source as calendar
                    // This ensures chart, cards, and calendar are always in sync
                    this.updateCalendarStats(data.summary);

                    // Reinitialize chart with new data
                    this.updateChart();
                }
            } catch (error) {
                console.error('Failed to load calendar data:', error);
            }
        },

        // Update calendar stats from summary data
        updateCalendarStats(summary) {
            // Hadir = present (ontime) + late
            const hadirCount = (summary.present || 0);
            // Tidak Hadir = absent (Alpha) + not_yet (belum absen tapi hari kerja sudah lewat)
            // Tidak termasuk: leave, sick, weekend, future
            const tidakHadirCount = (summary.absent || 0) + (summary.not_yet || 0);

            this.calendarStats.presentCount = hadirCount;
            this.calendarStats.notPresentCount = tidakHadirCount;
        },

        // Update chart with current calendar data
        updateChart() {
            const canvas = document.getElementById('attendanceChart');
            if (!canvas) return;

            // Destroy existing chart
            if (window.attendanceChart && typeof window.attendanceChart.destroy === 'function') {
                window.attendanceChart.destroy();
            }

            const ctx = canvas.getContext('2d');

            // Use data from calendar stats (same source as cards)
            const hadirData = this.calendarStats.presentCount || 0;
            const tidakHadirData = this.calendarStats.notPresentCount || 0;

            // If both are 0, show placeholder
            const hasData = hadirData > 0 || tidakHadirData > 0;

            window.attendanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hasData ? ['Hadir', 'Tidak Hadir'] : ['No Data'],
                    datasets: [{
                        data: hasData ? [hadirData, tidakHadirData] : [1],
                        backgroundColor: hasData
                            ? [
                                'rgba(34,197,94,.8)',
                                'rgba(239,68,68,.8)'
                              ]
                            : ['rgba(156,163,175,.3)'],
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: hasData
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            display: hasData,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            display: hasData
                        }
                    }
                }
            });
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

        statusBadge(status) {
            const badges = {
                'present': 'bg-green-100 text-green-700',
                'late': 'bg-yellow-100 text-yellow-700',
                'leave': 'bg-blue-100 text-blue-700',
                'sick': 'bg-cyan-100 text-cyan-700',
                'absent': 'bg-red-100 text-red-700',
                'not_yet': 'bg-gray-100 text-gray-600',
                'weekend': 'bg-gray-100 text-gray-500',
                'future': 'bg-gray-100 text-gray-400',
            };
            return badges[status] || 'bg-gray-100 text-gray-600';
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

/**
 * Calendar Present Modal - Opens present list for calendar period
 */
function calendarPresentModal() {
    return {
        presentList: [],
        presentLoading: false,
        showPresentModal: false,
        selectedPeriod: 'Bulan ini',

        openPresentModal() {
            this.showPresentModal = true;
            this.presentLoading = true;
            // Get period info from calendar component
            const calendarData = window.attendanceCalendarComponent;
            if (calendarData) {
                this.selectedPeriod = calendarData.periodLabel || 'Bulan ini';
            }
            this.loadPresentList();
        },

        async loadPresentList() {
            try {
                // Get current calendar context
                const calendarData = window.attendanceCalendarComponent;
                if (!calendarData) {
                    this.presentList = [];
                    this.presentLoading = false;
                    return;
                }

                const params = new URLSearchParams({
                    period: calendarData.period,
                    year: calendarData.currentYear,
                    month: calendarData.currentMonth,
                    day: calendarData.currentDay,
                });
                if (calendarData.selectedEmployeeId) {
                    params.append('employee_id', calendarData.selectedEmployeeId);
                }

                const response = await fetch(`/administrasi/absen/calendar-present-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.presentList = data.data || [];
                } else {
                    this.presentList = [];
                    console.error('Failed to load present list:', data.message);
                }
            } catch (error) {
                console.error('Failed to load present list:', error);
                this.presentList = [];
            } finally {
                this.presentLoading = false;
            }
        }
    };
}

/**
 * Calendar Not Present Modal - Opens not present list for calendar period
 */
function calendarNotPresentModal() {
    return {
        notPresentList: [],
        notPresentLoading: false,
        showNotPresentModal: false,
        selectedPeriod: 'Bulan ini',

        openNotPresentModal() {
            this.showNotPresentModal = true;
            this.notPresentLoading = true;
            // Get period info from calendar component
            const calendarData = window.attendanceCalendarComponent;
            if (calendarData) {
                this.selectedPeriod = calendarData.periodLabel || 'Bulan ini';
            }
            this.loadNotPresentList();
        },

        async loadNotPresentList() {
            try {
                // Get current calendar context
                const calendarData = window.attendanceCalendarComponent;
                if (!calendarData) {
                    this.notPresentList = [];
                    this.notPresentLoading = false;
                    return;
                }

                const params = new URLSearchParams({
                    period: calendarData.period,
                    year: calendarData.currentYear,
                    month: calendarData.currentMonth,
                    day: calendarData.currentDay,
                });
                if (calendarData.selectedEmployeeId) {
                    params.append('employee_id', calendarData.selectedEmployeeId);
                }

                const response = await fetch(`/administrasi/absen/calendar-not-present-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.notPresentList = data.data || [];
                } else {
                    this.notPresentList = [];
                    console.error('Failed to load not present list:', data.message);
                }
            } catch (error) {
                console.error('Failed to load not present list:', error);
                this.notPresentList = [];
            } finally {
                this.notPresentLoading = false;
            }
        }
    };
}

function attendanceStatsPopup() {
    return {
        // Card state
        presentCount: {{ $stats['present'] }},
        notPresentCount: {{ $stats['total'] - $stats['present'] }},

        // Modal state
        presentList: [],
        presentLoading: false,
        showPresentModal: false,

        notPresentList: [],
        notPresentLoading: false,
        showNotPresentModal: false,

        // Shared state
        selectedDate: '{{ $date }}',

        // Present modal
        async openPresentModal() {
            this.showPresentModal = true;
            this.presentLoading = true;

            try {
                const params = new URLSearchParams({
                    date: '{{ $date }}',
                    department: '{{ $department }}',
                });

                const response = await fetch(`/administrasi/absen/present-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.presentList = data.data || [];
                    this.presentCount = data.total || 0;
                } else {
                    this.presentList = [];
                    console.error('Failed to load present list:', data.message);
                }
            } catch (error) {
                console.error('Failed to load present list:', error);
                this.presentList = [];
            } finally {
                this.presentLoading = false;
            }
        },

        // Not present modal
        async openNotPresentModal() {
            this.showNotPresentModal = true;
            this.notPresentLoading = true;

            try {
                const params = new URLSearchParams({
                    date: '{{ $date }}',
                    department: '{{ $department }}',
                });

                const response = await fetch(`/administrasi/absen/not-present-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.notPresentList = data.data || [];
                    this.notPresentCount = data.total || 0;
                } else {
                    this.notPresentList = [];
                    console.error('Failed to load not present list:', data.message);
                }
            } catch (error) {
                console.error('Failed to load not present list:', error);
                this.notPresentList = [];
            } finally {
                this.notPresentLoading = false;
            }
        }
    };
}

/**
 * Present Card Component (legacy - kept for reference)
 * @deprecated Use attendanceStatsPopup() instead
 */
function presentCard() {
    return {
        presentCount: {{ $stats['present'] }},
        presentList: [],
        presentLoading: false,
        showPresentModal: false,
        selectedDate: '{{ $date }}',

        async openPresentModal() {
            this.showPresentModal = true;
            this.presentLoading = true;

            try {
                const params = new URLSearchParams({
                    date: '{{ $date }}',
                    department: '{{ $department }}',
                });

                const response = await fetch(`/administrasi/absen/present-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.presentList = data.data || [];
                    this.presentCount = data.total || 0;
                }
            } catch (error) {
                console.error('Failed to load present list:', error);
                this.presentList = [];
            } finally {
                this.presentLoading = false;
            }
        }
    };
}

/**
 * Not Present Card Component (legacy - kept for reference)
 * @deprecated Use attendanceStatsPopup() instead
 */
function notPresentCard() {
    return {
        notPresentCount: {{ $stats['total'] - $stats['present'] }},
        notPresentList: [],
        notPresentLoading: false,
        showNotPresentModal: false,
        selectedDate: '{{ $date }}',

        async openNotPresentModal() {
            this.showNotPresentModal = true;
            this.notPresentLoading = true;

            try {
                const params = new URLSearchParams({
                    date: '{{ $date }}',
                    department: '{{ $department }}',
                });

                const response = await fetch(`/administrasi/absen/not-present-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.notPresentList = data.data || [];
                    this.notPresentCount = data.total || 0;
                }
            } catch (error) {
                console.error('Failed to load not present list:', error);
                this.notPresentList = [];
            } finally {
                this.notPresentLoading = false;
            }
        }
    };
}

// Initialize attendance when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Chart will be initialized via updateChart() when calendar data is loaded
});
</script>
@endpush
