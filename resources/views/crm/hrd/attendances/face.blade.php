@extends('layouts.app')

@section('title', 'Absensi')
@section('page-title', 'Absensi')

@php
    use App\Services\Permission\UserPermissionService;

    $user = auth()->user();
    $permissionService = UserPermissionService::forUser($user);
    $canViewAll = $permissionService->isGlobalScope('attendances');
@endphp

@section('content')
<div class="max-w-2xl mx-auto" x-data="attendanceApp()">

    {{-- CONDITION 1: Staff without employee and no pending request --}}
    @if(!$employee && !$pendingRequest && !$canViewAll)
    <div class="bg-white rounded-xl border border-amber-200 p-8 text-center">
        <div class="w-16 h-16 mx-auto bg-amber-100 rounded-full flex items-center justify-center mb-4">
            <i class="fa-solid fa-user-clock text-3xl text-amber-600"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-3">Profil Karyawan Belum Terhubung</h2>
        <p class="text-gray-600 mb-6 max-w-md mx-auto">
            Akun Anda belum terhubung dengan profil karyawan.<br>
            <strong>Ajukan penghubungan akun</strong> agar Admin atau Director dapat menghubungkan akun Anda.
        </p>
        <button @click="submitRequest()"
                :disabled="submitting"
                class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 inline-flex items-center">
            <template x-if="submitting">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i>
            </template>
            <template x-if="!submitting">
                <i class="fa-solid fa-paper-plane mr-2"></i>
            </template>
            <span x-text="submitting ? 'Mengirim...' : 'Ajukan Penghubungan Akun'"></span>
        </button>
        <p class="mt-4 text-sm text-gray-500">Menunggu persetujuan Admin atau Director</p>
    </div>

    {{-- CONDITION 2: Has view_all permission without employee --}}
    @elseif(!$employee && !$pendingRequest && $canViewAll)
    <div class="bg-white rounded-xl border border-indigo-200 p-8 text-center">
        <div class="w-16 h-16 mx-auto bg-indigo-100 rounded-full flex items-center justify-center mb-4">
            <i class="fa-solid fa-link text-3xl text-indigo-600"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-3">Hubungkan Akun Anda</h2>
        <p class="text-gray-600 mb-6 max-w-md mx-auto">
            Pilih profil karyawan yang akan dihubungkan dengan akun Anda.
        </p>
        <button @click="openLinkModal()"
                class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors inline-flex items-center">
            <i class="fa-solid fa-user-plus mr-2"></i>
            Hubungkan Akun
        </button>
    </div>

    {{-- CONDITION 3: Has pending request --}}
    @elseif($pendingRequest)
    <div class="bg-white rounded-xl border border-amber-200 p-8 text-center">
        <div class="w-16 h-16 mx-auto bg-amber-100 rounded-full flex items-center justify-center mb-4">
            <i class="fa-solid fa-hourglass text-3xl text-amber-600"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-3">Pengajuan Sedang Diproses</h2>
        <p class="text-gray-600 mb-4 max-w-md mx-auto">
            Permintaan penghubungan akun Anda sedang menunggu<br>
            <strong>tindakan Admin atau Director.</strong>
        </p>
        <div class="inline-block bg-amber-50 border border-amber-200 rounded-lg px-4 py-2 mb-6">
            <p class="text-sm text-amber-800">
                <i class="fa-solid fa-clock mr-1"></i>
                Diajukan: {{ $pendingRequest->created_at->format('d M Y H:i') }}
            </p>
        </div>
        <br>
        <button @click="cancelRequest({{ $pendingRequest->id }})"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 text-sm underline">
            Batalkan Pengajuan
        </button>
    </div>

    {{-- CONDITION 4: Employee is inactive --}}
    @elseif($employee && !$employee->is_active)
    <div class="bg-white rounded-xl border border-red-200 p-8 text-center">
        <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
            <i class="fa-solid fa-user-xmark text-3xl text-red-600"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-3">Profil Nonaktif</h2>
        <p class="text-gray-600">Hubungi Staff untuk mengaktifkan profil Anda.</p>
    </div>

    {{-- CONDITION 5: Active employee with Face Attendance --}}
    @else
    {{-- Attendance Status Card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-semibold text-gray-800">{{ now()->format('d F Y') }}</h3>
            </div>
            @if($todayAttendance && $todayAttendance->check_in_time && $todayAttendance->check_out_time)
            <span class="px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                <i class="fa-solid fa-check mr-1"></i>Selesai
            </span>
            @elseif($todayAttendance && $todayAttendance->check_in_time)
            <span class="px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                <i class="fa-solid fa-sign-in-alt mr-1"></i>Sudah Check In
            </span>
            @else
            <span class="px-4 py-2 bg-gray-100 text-gray-600 rounded-full text-sm font-medium">
                <i class="fa-solid fa-clock mr-1"></i>Belum Absen
            </span>
            @endif
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-500 mb-1">Check In</p>
                <p class="text-2xl font-bold {{ $todayAttendance && $todayAttendance->check_in_time ? 'text-green-600' : 'text-gray-400' }}">
                    {{ $todayAttendance && $todayAttendance->check_in_time ? $todayAttendance->check_in_formatted : '--:--:--' }}
                </p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-500 mb-1">Check Out</p>
                <p class="text-2xl font-bold {{ $todayAttendance && $todayAttendance->check_out_time ? 'text-red-600' : 'text-gray-400' }}">
                    {{ $todayAttendance && $todayAttendance->check_out_time ? $todayAttendance->check_out_formatted : '--:--:--' }}
                </p>
            </div>
        </div>
        @if($todayAttendance && $todayAttendance->late_minutes > 0)
        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-center text-red-700 text-sm">
            <i class="fa-solid fa-exclamation-circle mr-1"></i>
            Terlambat {{ $todayAttendance->late_minutes }} menit
        </div>
        @endif
    </div>

    {{-- Camera & Action Card --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b">
            <h3 class="font-semibold text-gray-800">
                <i class="fa-solid fa-camera text-blue-600 mr-2"></i>
                <span x-text="getCardTitle()"></span>
            </h3>
        </div>
        <div class="p-6">
            {{-- Camera Preview --}}
            <div class="bg-gray-900 rounded-xl aspect-video mb-4 relative" id="cameraBox">
                <video id="videoEl" autoplay playsinline class="w-full h-full object-cover rounded-xl"></video>
                <div id="cameraOff" class="absolute inset-0 flex items-center justify-center bg-gray-800 rounded-xl">
                    <div class="text-center text-gray-400">
                        <i class="fa-solid fa-camera text-4xl mb-2 animate-pulse"></i>
                        <p>Kamera nonaktif</p>
                    </div>
                </div>
            </div>

            {{-- GPS Location Info --}}
            <div class="bg-gray-50 rounded-lg p-4 text-sm mb-4">
                <h4 class="font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-map-marker-alt text-red-500 mr-1"></i>Lokasi
                </h4>
                <div class="grid grid-cols-2 gap-2 text-gray-600">
                    <div>Lat: <span id="latEl">Mendeteksi...</span></div>
                    <div>Lng: <span id="lngEl">Mendeteksi...</span></div>
                    <div>Akurasi: <span id="accEl">...</span></div>
                    <div>Alamat: <span id="addrEl">...</span></div>
                </div>
            </div>

            {{-- Dynamic Action Button with Press-and-Hold Fingerprint --}}
            <div class="flex justify-center mb-4">
                {{-- Check In Button (STATE 1) --}}
                <template x-if="attendanceState === 'not_checked_in'">
                    <div class="flex flex-col items-center">
                        {{-- Fingerprint Press-and-Hold Button --}}
                        <button type="button"
                                x-ref="checkInBtn"
                                id="fingerprint-btn"
                                class="relative w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 shadow-lg flex items-center justify-center transition-all duration-200 focus:outline-none"
                                :class="{
                                    'scale-105 shadow-xl': isHolding && holdType === 'check_in',
                                    'opacity-50 cursor-not-allowed': !isCameraReady || !isLocationReady || isProcessing
                                }"
                                :disabled="!isCameraReady || !isLocationReady || isProcessing"
                                @pointerdown.prevent="startHold('check_in')"
                                @pointerup="cancelHold()"
                                @pointerleave="cancelHold()"
                                @pointercancel="cancelHold()"
                                @contextmenu.prevent
                                style="touch-action: none; user-select: none; -webkit-user-select: none;">

                            {{-- SVG Circular Progress --}}
                            <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 128 128">
                                {{-- Background Circle --}}
                                <circle cx="64" cy="64" r="60"
                                        fill="none"
                                        stroke="rgba(255,255,255,0.2)"
                                        stroke-width="6"/>
                                {{-- Progress Circle --}}
                                <circle x-ref="checkInProgress"
                                        cx="64" cy="64" r="60"
                                        fill="none"
                                        stroke="white"
                                        stroke-width="6"
                                        stroke-linecap="round"
                                        stroke-dasharray="377"
                                        stroke-dashoffset="377"
                                        class="transition-all duration-100"
                                        x-show="isHolding && holdType === 'check_in'"
                                        x-transition:enter="transition ease-out"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"/>
                            </svg>

                            {{-- Touch/Tap Icon --}}
                            <div class="relative z-10 flex flex-col items-center justify-center">
                                <template x-if="!isHolding || holdType !== 'check_in'">
                                    <div class="flex flex-col items-center">
                                        {{-- Hand Finger Touch Image --}}
                                        <img src="{{ asset('images/attendance/press-finger.png') }}"
                                             alt="Tekan untuk Check In"
                                             class="w-14 h-14 object-contain"
                                             style="filter: brightness(0) invert(1);">
                                        <span class="text-white text-xs font-medium mt-1">Check In</span>
                                    </div>
                                </template>
                                <template x-if="isHolding && holdType === 'check_in' && !holdCompleted">
                                    <div class="flex flex-col items-center">
                                        {{-- Hand Finger Touch Image - Holding --}}
                                        <img src="{{ asset('images/attendance/press-finger.png') }}"
                                             alt="Menahan..."
                                             class="w-14 h-14 object-contain animate-pulse"
                                             style="filter: brightness(0) invert(1);">
                                        <span class="text-white text-xs font-medium mt-1" x-text="holdCountdown > 0 ? 'Tahan ' + holdCountdown + ' detik...' : 'Memproses...'"></span>
                                    </div>
                                </template>
                                <template x-if="holdCompleted && holdType === 'check_in' && !isProcessing">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                        </svg>
                                        <span class="text-white text-xs font-medium mt-1">Berhasil!</span>
                                    </div>
                                </template>
                            </div>
                        </button>

                        {{-- Helper Text --}}
                        <p class="mt-3 text-sm text-center"
                           :class="{
                               'text-gray-500': !isHolding && (!isCameraReady || !isLocationReady || isProcessing),
                               'text-blue-600 font-medium': isHolding && holdType === 'check_in',
                               'text-gray-500': isHolding && holdType !== 'check_in'
                           }">
                            <template x-if="!isCameraReady || !isLocationReady">
                                <span>Aktifkan kamera &amp; lokasi</span>
                            </template>
                            <template x-if="isCameraReady && isLocationReady && !isProcessing && (!isHolding || holdType !== 'check_in')">
                                <span>Tekan &amp; tahan 3 detik</span>
                            </template>
                            <template x-if="isCameraReady && isLocationReady && isProcessing">
                                <span>Memproses...</span>
                            </template>
                        </p>
                    </div>
                </template>

                {{-- Check Out Button (STATE 2) --}}
                <template x-if="attendanceState === 'checked_in'">
                    <div class="flex flex-col items-center">
                        {{-- Fingerprint Press-and-Hold Button --}}
                        <button type="button"
                                x-ref="checkOutBtn"
                                id="fingerprint-btn-checkout"
                                class="relative w-32 h-32 rounded-full bg-gradient-to-br from-red-500 to-red-700 shadow-lg flex items-center justify-center transition-all duration-200 focus:outline-none"
                                :class="{
                                    'scale-105 shadow-xl': isHolding && holdType === 'check_out',
                                    'opacity-50 cursor-not-allowed': !isCameraReady || !isLocationReady || isProcessing
                                }"
                                :disabled="!isCameraReady || !isLocationReady || isProcessing"
                                @pointerdown.prevent="startHold('check_out')"
                                @pointerup="cancelHold()"
                                @pointerleave="cancelHold()"
                                @pointercancel="cancelHold()"
                                @contextmenu.prevent
                                style="touch-action: none; user-select: none; -webkit-user-select: none;">

                            {{-- SVG Circular Progress --}}
                            <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 128 128">
                                {{-- Background Circle --}}
                                <circle cx="64" cy="64" r="60"
                                        fill="none"
                                        stroke="rgba(255,255,255,0.2)"
                                        stroke-width="6"/>
                                {{-- Progress Circle --}}
                                <circle x-ref="checkOutProgress"
                                        cx="64" cy="64" r="60"
                                        fill="none"
                                        stroke="white"
                                        stroke-width="6"
                                        stroke-linecap="round"
                                        stroke-dasharray="377"
                                        stroke-dashoffset="377"
                                        class="transition-all duration-100"
                                        x-show="isHolding && holdType === 'check_out'"
                                        x-transition:enter="transition ease-out"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"/>
                            </svg>

                            {{-- Touch/Tap Icon --}}
                            <div class="relative z-10 flex flex-col items-center justify-center">
                                <template x-if="!isHolding || holdType !== 'check_out'">
                                    <div class="flex flex-col items-center">
                                        {{-- Hand Finger Touch Image --}}
                                        <img src="{{ asset('images/attendance/press-finger.png') }}"
                                             alt="Tekan untuk Check Out"
                                             class="w-14 h-14 object-contain"
                                             style="filter: brightness(0) invert(1);">
                                        <span class="text-white text-xs font-medium mt-1">Check Out</span>
                                    </div>
                                </template>
                                <template x-if="isHolding && holdType === 'check_out' && !holdCompleted">
                                    <div class="flex flex-col items-center">
                                        {{-- Hand Finger Touch Image - Holding --}}
                                        <img src="{{ asset('images/attendance/press-finger.png') }}"
                                             alt="Menahan..."
                                             class="w-14 h-14 object-contain animate-pulse"
                                             style="filter: brightness(0) invert(1);">
                                        <span class="text-white text-xs font-medium mt-1" x-text="holdCountdown > 0 ? 'Tahan ' + holdCountdown + ' detik...' : 'Memproses...'"></span>
                                    </div>
                                </template>
                                <template x-if="holdCompleted && holdType === 'check_out' && !isProcessing">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                        </svg>
                                        <span class="text-white text-xs font-medium mt-1">Berhasil!</span>
                                    </div>
                                </template>
                            </div>
                        </button>

                        {{-- Helper Text --}}
                        <p class="mt-3 text-sm text-center"
                           :class="{
                               'text-gray-500': !isHolding && (!isCameraReady || !isLocationReady || isProcessing),
                               'text-red-600 font-medium': isHolding && holdType === 'check_out',
                               'text-gray-500': isHolding && holdType !== 'check_out'
                           }">
                            <template x-if="!isCameraReady || !isLocationReady">
                                <span>Aktifkan kamera &amp; lokasi</span>
                            </template>
                            <template x-if="isCameraReady && isLocationReady && !isProcessing && (!isHolding || holdType !== 'check_out')">
                                <span>Tekan &amp; tahan 3 detik</span>
                            </template>
                            <template x-if="isCameraReady && isLocationReady && isProcessing">
                                <span>Memproses...</span>
                            </template>
                        </p>
                    </div>
                </template>

                {{-- Completed State (STATE 3) --}}
                <template x-if="attendanceState === 'completed'">
                    <div class="text-center">
                        <div class="px-8 py-4 bg-green-100 text-green-700 text-lg font-bold rounded-xl inline-flex items-center gap-2">
                            <i class="fa-solid fa-check-circle"></i>
                            Absensi Hari Ini Selesai
                        </div>
                        <p class="mt-3 text-sm text-gray-500">
                            Anda dapat melakukan absensi kembali besok.
                        </p>
                    </div>
                </template>
            </div>

            {{-- State Info --}}
            <template x-if="attendanceState === 'not_checked_in'">
                <p class="text-center text-sm text-gray-500">
                    Pastikan kamera dan lokasi GPS aktif untuk melakukan absensi.
                </p>
            </template>
        </div>
    </div>

    {{-- Hidden Form for submission --}}
    <form id="attForm" method="POST" action="{{ route('administrasi.absen.face.submit') }}" class="hidden">
        @csrf
        <input name="type" id="typeEl" value="check_in">
        <input name="photo" id="photoEl">
        <input name="latitude" id="latFormEl">
        <input name="longitude" id="lngFormEl">
        <input name="address" id="addrFormEl">
        <input name="gps_accuracy" id="accFormEl">
        {{-- Timezone info --}}
        <input name="timezone" id="addrTimezone" value="Asia/Jakarta">
        <input name="province" id="addrProvince" value="">
        <input name="city" id="addrCity" value="">
    </form>
    @endif

    {{-- Link Modal (Admin/Director) --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full relative z-10">
            <div class="p-4 border-b">
                <h3 class="font-semibold text-lg">
                    <i class="fa-solid fa-link text-indigo-600 mr-2"></i>Hubungkan Akun
                </h3>
            </div>
            <div class="p-4">
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 mb-4">
                    <p class="text-sm font-medium text-indigo-800 mb-1">Akun Login</p>
                    <p class="text-sm text-indigo-600">{{ auth()->user()->name }} ({{ auth()->user()->email }})</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Profil Karyawan</label>
                    <select x-model="selectedEmployeeId" class="w-full border rounded-lg p-2.5">
                        <option value="">-- Pilih --</option>
                        <template x-for="emp in employees" :key="emp.id">
                            <option :value="emp.id" x-text="emp.display"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="p-4 border-t flex justify-end gap-2">
                <button @click="showModal = false" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
                <button @click="submitLink()" :disabled="!selectedEmployeeId || submittingLink"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg disabled:opacity-50">
                    <span x-text="submittingLink ? 'Memproses...' : 'Hubungkan'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function attendanceApp() {
    return {
        showModal: false,
        submitting: false,
        submittingLink: false,
        employees: [],
        selectedEmployeeId: '',
        isCameraReady: false,
        isLocationReady: false,
        isSubmitting: false,

        // Press-and-Hold State
        isHolding: false,
        holdType: null, // 'check_in' or 'check_out'
        holdCountdown: 3,
        holdStartTime: null,
        holdCompleted: false,
        holdTimer: null,
        holdDuration: 3000, // 3 seconds
        holdAnimationFrame: null,

        // Alias for template binding
        get isProcessing() {
            return this.isSubmitting;
        },

        // Attendance state from server
        @if($todayAttendance && $todayAttendance->check_in_time && $todayAttendance->check_out_time)
        attendanceState: 'completed',
        @elseif($todayAttendance && $todayAttendance->check_in_time)
        attendanceState: 'checked_in',
        @else
        attendanceState: 'not_checked_in',
        @endif

        getCardTitle() {
            if (this.attendanceState === 'completed') return 'Absensi Selesai';
            if (this.attendanceState === 'checked_in') return 'Check Out';
            return 'Check In';
        },

        // =============================================
        // PRESS-AND-HOLD FINGERPRINT FUNCTIONS
        // =============================================

        startHold(type) {
            // Prevent if already processing, camera/location not ready, or already holding
            if (this.isProcessing || !this.isCameraReady || !this.isLocationReady) {
                return;
            }
            if (this.isHolding) {
                return;
            }

            // Start hold
            this.isHolding = true;
            this.holdType = type;
            this.holdCountdown = 3;
            this.holdStartTime = Date.now();
            this.holdCompleted = false;

            // Start the animation and countdown
            this.runHoldProgress(type);
        },

        runHoldProgress(type) {
            const elapsed = Date.now() - this.holdStartTime;
            const progress = Math.min(elapsed / this.holdDuration, 1);
            const remainingSeconds = Math.ceil((this.holdDuration - elapsed) / 1000);

            // Update countdown display
            this.holdCountdown = Math.max(remainingSeconds, 0);

            // Update SVG progress circle
            const progressCircle = document.querySelector(
                type === 'check_in' ? '#fingerprint-btn circle:nth-child(2)' : '#fingerprint-btn-checkout circle:nth-child(2)'
            );
            if (progressCircle) {
                // circumference = 2 * PI * r = 2 * 3.14159 * 60 ≈ 377
                const circumference = 377;
                const offset = circumference * (1 - progress);
                progressCircle.style.strokeDashoffset = offset;
            }

            // Check if hold is complete
            if (progress >= 1) {
                this.completeHold(type);
                return;
            }

            // Continue animation
            this.holdAnimationFrame = requestAnimationFrame(() => {
                this.runHoldProgress(type);
            });
        },

        completeHold(type) {
            // Cancel animation frame
            if (this.holdAnimationFrame) {
                cancelAnimationFrame(this.holdAnimationFrame);
                this.holdAnimationFrame = null;
            }

            // Mark as completed
            this.holdCompleted = true;
            this.holdCountdown = 0;

            // Reset progress circle to full
            const progressCircle = document.querySelector(
                type === 'check_in' ? '#fingerprint-btn circle:nth-child(2)' : '#fingerprint-btn-checkout circle:nth-child(2)'
            );
            if (progressCircle) {
                progressCircle.style.strokeDashoffset = '0';
            }

            // Trigger the attendance action after a brief visual feedback
            setTimeout(() => {
                // Reset hold state
                this.isHolding = false;
                this.holdCompleted = false;

                // Call the appropriate action
                if (type === 'check_in') {
                    this.performCheckIn();
                } else if (type === 'check_out') {
                    this.performCheckOut();
                }
            }, 300);
        },

        cancelHold() {
            // Don't cancel if not holding or already completed
            if (!this.isHolding || this.holdCompleted) {
                return;
            }

            // Cancel animation frame
            if (this.holdAnimationFrame) {
                cancelAnimationFrame(this.holdAnimationFrame);
                this.holdAnimationFrame = null;
            }

            // Reset progress circle
            const progressCircle = document.querySelector(
                this.holdType === 'check_in' ? '#fingerprint-btn circle:nth-child(2)' : '#fingerprint-btn-checkout circle:nth-child(2)'
            );
            if (progressCircle) {
                progressCircle.style.strokeDashoffset = '377';
            }

            // Reset hold state
            this.isHolding = false;
            this.holdType = null;
            this.holdCountdown = 3;
            this.holdStartTime = null;
        },

        async submitRequest() {
            if (this.submitting) return;
            this.submitting = true;
            try {
                const resp = await fetch('{{ route('administrasi.absen.face.submit-request') }}', {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
                });
                const data = await resp.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Gagal');
                }
            } catch (e) { alert('Error'); }
            finally { this.submitting = false; }
        },

        async cancelRequest(id) {
            if (!confirm('Batalkan pengajuan?')) return;
            await fetch('{{ route('administrasi.absen.face.cancel-request') }}?id=' + id, {
                method: 'DELETE',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
            });
            location.reload();
        },

        async openLinkModal() {
            this.showModal = true;
            const resp = await fetch('{{ route('administrasi.absen.face.linkable-employees') }}');
            const data = await resp.json();
            this.employees = data.employees || [];
        },

        async submitLink() {
            if (!this.selectedEmployeeId || this.submittingLink) return;
            this.submittingLink = true;
            try {
                const resp = await fetch('{{ route('administrasi.absen.face.link') }}', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
                    body: JSON.stringify({employee_id: this.selectedEmployeeId})
                });
                const data = await resp.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Gagal');
                }
            } catch (e) { alert('Error'); }
            finally { this.submittingLink = false; }
        },

        performCheckIn() {
            if (this.isSubmitting) return;
            if (!this.isCameraReady) {
                alert('Kamera belum siap. Mohon tunggu beberapa saat.');
                return;
            }
            if (!this.isLocationReady) {
                alert('Lokasi GPS belum terdeteksi. Mohon aktifkan GPS dan tunggu beberapa saat.');
                return;
            }

            this.isSubmitting = true;
            document.getElementById('typeEl').value = 'check_in';
            this.captureAndSubmit();
        },

        performCheckOut() {
            if (this.isSubmitting) return;
            if (!this.isCameraReady) {
                alert('Kamera belum siap. Mohon tunggu beberapa saat.');
                return;
            }
            if (!this.isLocationReady) {
                alert('Lokasi GPS belum terdeteksi. Mohon aktifkan GPS dan tunggu beberapa saat.');
                return;
            }

            this.isSubmitting = true;
            document.getElementById('typeEl').value = 'check_out';
            this.captureAndSubmit();
        },

        captureAndSubmit() {
            // Get current GPS for this action
            const lat = parseFloat(document.getElementById('latFormEl').value);
            const lng = parseFloat(document.getElementById('lngFormEl').value);
            const addr = document.getElementById('addrFormEl').value || null;
            const acc = parseFloat(document.getElementById('accFormEl').value);
            const type = document.getElementById('typeEl').value;

            // Capture fresh photo (not reuse from check-in)
            const video = document.getElementById('videoEl');

            // Validate video dimensions
            if (!video.videoWidth || !video.videoHeight) {
                alert('Kamera belum siap. Mohon tunggu beberapa saat.');
                this.isSubmitting = false;
                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            // Convert canvas to base64 image
            const photoData = canvas.toDataURL('image/jpeg', 0.8);

            // Validate photo data
            if (!photoData || photoData.length < 100) {
                alert('Gambar tidak valid. Mohon coba lagi.');
                this.isSubmitting = false;
                return;
            }

            // Ensure coordinates are valid numbers
            const latitude = (lat && !isNaN(lat)) ? lat : null;
            const longitude = (lng && !isNaN(lng)) ? lng : null;
            const gps_accuracy = (acc && !isNaN(acc)) ? acc : null;

            // Prepare data for AJAX submission (include timezone info)
            const formData = {
                type: type,
                photo: photoData,
                latitude: latitude,
                longitude: longitude,
                address: addr,
                gps_accuracy: gps_accuracy,
                // Timezone info from browser
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                timezone_offset: String(new Date().getTimezoneOffset()),
                province: document.getElementById('addrProvince')?.value || null,
                city: document.getElementById('addrCity')?.value || null,
            };

            // Debug: Log the data being sent
            console.log('=== ATTENDANCE SUBMISSION DEBUG ===');
            console.log('Type:', formData.type);
            console.log('Photo length:', formData.photo ? formData.photo.length : 'null');
            console.log('Photo preview:', formData.photo ? formData.photo.substring(0, 100) + '...' : 'null');
            console.log('Latitude:', formData.latitude, '(type:', typeof formData.latitude, ')');
            console.log('Longitude:', formData.longitude, '(type:', typeof formData.longitude, ')');
            console.log('Address:', formData.address);
            console.log('GPS Accuracy:', formData.gps_accuracy, '(type:', typeof formData.gps_accuracy, ')');
            console.log('Timezone:', formData.timezone);
            console.log('Timezone Offset:', formData.timezone_offset, '(type:', typeof formData.timezone_offset, ')');
            console.log('Province:', formData.province);
            console.log('City:', formData.city);
            console.log('=====================================');

            // Submit via AJAX fetch (no page redirect)
            fetch('{{ route('administrasi.absen.face.submit') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(async response => {
                // Clone the response first so we can read it multiple times
                const data = await response.json();

                console.log('=== SERVER RESPONSE ===');
                console.log('Status:', response.status);
                console.log('Response data:', JSON.stringify(data, null, 2));
                console.log('========================');

                // Handle different status codes
                if (response.ok) {
                    // Success (HTTP 200-299)
                    return data;
                } else if (response.status === 422) {
                    // Validation Error - Extract all error messages
                    const errorMessages = [];
                    if (data.errors) {
                        // Flatten all validation errors
                        for (const [field, messages] of Object.entries(data.errors)) {
                            if (Array.isArray(messages)) {
                                messages.forEach(msg => {
                                    errorMessages.push(msg);
                                });
                            }
                        }
                    }
                    const errorText = errorMessages.length > 0
                        ? errorMessages.join('\n')
                        : (data.message || 'Data tidak valid.');

                    throw new Error(errorText);
                } else if (response.status === 403) {
                    // Forbidden / Not authorized
                    throw new Error(data.message || data.code || 'Akses ditolak. ' + (data.code === 'EMPLOYEE_ACCOUNT_NOT_LINKED' ? 'Akun Anda belum terhubung dengan profil karyawan.' : 'Anda tidak memiliki izin untuk melakukan aksi ini.'));
                } else if (response.status === 400) {
                    // Bad Request (business logic errors like ALREADY_CHECKED_IN, NOT_CHECKED_IN, etc.)
                    throw new Error(data.message || data.code || 'Permintaan tidak dapat diproses.');
                } else {
                    // Other errors
                    throw new Error(data.message || `Terjadi kesalahan (HTTP ${response.status})`);
                }
            })
            .then(data => {
                // Success handler
                if (data.success) {
                    // Update UI based on action type
                    if (type === 'check_in') {
                        // Update check-in time display
                        this.attendanceState = 'checked_in';
                        // Update the displayed time if returned
                        if (data.attendance && data.attendance.check_in_time) {
                            const checkInEl = document.querySelector('.bg-gray-50.rounded-lg .text-2xl:first-child');
                            if (checkInEl) {
                                checkInEl.textContent = data.attendance.check_in_time;
                                checkInEl.classList.remove('text-gray-400');
                                checkInEl.classList.add('text-green-600');
                            }
                        }
                    } else if (type === 'check_out') {
                        // Update check-out time display
                        this.attendanceState = 'completed';
                        // Update the displayed time if returned
                        if (data.attendance && data.attendance.check_out_time) {
                            const checkOutEl = document.querySelector('.bg-gray-50.rounded-lg .text-2xl:last-child');
                            if (checkOutEl) {
                                checkOutEl.textContent = data.attendance.check_out_time;
                                checkOutEl.classList.remove('text-gray-400');
                                checkOutEl.classList.add('text-red-600');
                            }
                        }
                    }

                    // Show success message
                    alert(data.message || 'Absensi berhasil!');

                    // Reload page to get fresh data
                    location.reload();
                } else {
                    alert(data.message || 'Terjadi kesalahan. Silakan coba lagi.');
                }
            })
            .catch(error => {
                console.error('Submission error:', error);
                // Show the actual error message
                alert(error.message || 'Terjadi kesalahan koneksi. Silakan coba lagi.');
            })
            .finally(() => {
                this.isSubmitting = false;
                // Reset hold state
                this.isHolding = false;
                this.holdType = null;
                this.holdCountdown = 3;
                this.holdCompleted = false;

                // Reset progress circles
                const progressCircle1 = document.querySelector('#fingerprint-btn circle:nth-child(2)');
                const progressCircle2 = document.querySelector('#fingerprint-btn-checkout circle:nth-child(2)');
                if (progressCircle1) progressCircle1.style.strokeDashoffset = '377';
                if (progressCircle2) progressCircle2.style.strokeDashoffset = '377';
            });
        }
    }
}

let stream = null;
let locationWatchId = null;

document.addEventListener('DOMContentLoaded', () => {
    initCamera();
    initLocation();
});

// Initialize camera
async function initCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        document.getElementById('videoEl').srcObject = stream;
        document.getElementById('cameraOff').style.display = 'none';

        // Mark camera as ready for Alpine - use Alpine.$data for Alpine v3
        const el = document.querySelector('[x-data="attendanceApp()"]');
        if (el) {
            const app = Alpine.$data(el);
            if (app && typeof app.isCameraReady !== 'undefined') {
                app.isCameraReady = true;
            }
        }
    } catch(e) {
        console.log('Camera error:', e);
    }
}

// Initialize GPS location
function initLocation() {
    if (!navigator.geolocation) {
        document.getElementById('latEl').textContent = 'Tidak didukung';
        return;
    }

    // Watch position for continuous updates
    locationWatchId = navigator.geolocation.watchPosition(
        (position) => {
            const coords = position.coords;
            document.getElementById('latEl').textContent = coords.latitude.toFixed(6);
            document.getElementById('lngEl').textContent = coords.longitude.toFixed(6);
            document.getElementById('accEl').textContent = coords.accuracy.toFixed(0) + 'm';

            // Update form values
            document.getElementById('latFormEl').value = coords.latitude;
            document.getElementById('lngFormEl').value = coords.longitude;
            document.getElementById('accFormEl').value = coords.accuracy;

            // Get address with province/city from Nominatim
            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${coords.latitude}&lon=${coords.longitude}&format=json&addressdetails=1`)
                .then(r => r.json())
                .then(d => {
                    const addr = d.display_name || 'Tidak ditemukan';
                    document.getElementById('addrEl').textContent = addr.substring(0, 50);
                    document.getElementById('addrFormEl').value = addr;

                    // Extract province and city for timezone mapping
                    const province = d.address?.state || d.address?.county || d.address?.city || null;
                    const city = d.address?.city || d.address?.town || d.address?.municipality || d.address?.village || null;

                    // Store province and city in hidden fields
                    if (document.getElementById('addrProvince')) {
                        document.getElementById('addrProvince').value = province || '';
                    }
                    if (document.getElementById('addrCity')) {
                        document.getElementById('addrCity').value = city || '';
                    }

                    // Determine timezone from province/city
                    const timezone = determineTimezone(province, city, addr);
                    document.getElementById('addrTimezone').value = timezone;

                    // Update timezone display
                    updateTimezoneDisplay(timezone);
                })
                .catch(() => {
                    document.getElementById('addrEl').textContent = 'Error';
                });

            // Mark location as ready - use Alpine.$data for Alpine v3
            const el = document.querySelector('[x-data="attendanceApp()"]');
            if (el) {
                const app = Alpine.$data(el);
                if (app && typeof app.isLocationReady !== 'undefined') {
                    app.isLocationReady = true;
                }
            }
        },
        (error) => {
            console.log('Location error:', error);
            document.getElementById('latEl').textContent = 'Error';
            document.getElementById('lngEl').textContent = 'Error';
            document.getElementById('accEl').textContent = 'Error';
            document.getElementById('addrEl').textContent = 'Tidak ditemukan';
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

// Determine timezone from province/city/address
function determineTimezone(province, city, address) {
    const text = [province, city, address]
        .filter(Boolean)
        .map(s => s.toLowerCase())
        .join(' ');

    // WIT provinces (UTC+9)
    if (text.includes('papua') || text.includes('irian')) {
        return 'Asia/Jayapura'; // WIT (UTC+9)
    }

    // WITA provinces (UTC+8)
    const witaKeywords = [
        'bali', 'ntb', 'ntt', 'lombok', 'sulawesi', 'sulawesi selatan',
        'sulawesi tengah', 'sulawesi tenggara', 'sulawesi barat', 'sulawesi utara',
        'gorontalo', 'kalimantan timur', 'kalimantan utara', 'kaltim', 'kaltara'
    ];
    for (const keyword of witaKeywords) {
        if (text.includes(keyword)) {
            return 'Asia/Makassar'; // WITA (UTC+8)
        }
    }

    // Default to WIB (UTC+7) - covers Java, Sumatra, Kalimantan
    return 'Asia/Jakarta'; // WIB (UTC+7)
}

// Update timezone display badge
function updateTimezoneDisplay(timezone) {
    const tzBadge = document.getElementById('tzBadge');
    const tzNameEl = document.getElementById('tzName');
    if (tzBadge && tzNameEl) {
        const tzNames = {
            'Asia/Jakarta': 'WIB',
            'Asia/Makassar': 'WITA',
            'Asia/Jayapura': 'WIT'
        };
        const tzOffsets = {
            'Asia/Jakarta': 'UTC+7',
            'Asia/Makassar': 'UTC+8',
            'Asia/Jayapura': 'UTC+9'
        };
        tzNameEl.textContent = tzNames[timezone] || 'WIB';
        document.getElementById('tzOffset').textContent = tzOffsets[timezone] || 'UTC+7';
        document.getElementById('tzCity').textContent = document.getElementById('addrCity')?.value || '';
    }
}

// Cleanup on page leave
window.addEventListener('beforeunload', () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
    if (locationWatchId) {
        navigator.geolocation.clearWatch(locationWatchId);
    }
});
</script>
@endsection
