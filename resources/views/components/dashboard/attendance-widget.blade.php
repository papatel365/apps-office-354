{{-- resources/views/components/dashboard/attendance-widget.blade.php --}}
{{--
    Attendance Widget
    Shows today's attendance stats - only visible if user has 'staff.attendances' permission
--}}

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900">
            <i class="fa-solid fa-calendar-check text-green-500 mr-2"></i>
            Absensi Hari Ini
        </h3>
        <a href="{{ route('administrasi.absen.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Detail</a>
    </div>
    <div class="p-5">
        {{-- Attendance Stats --}}
        <div class="grid grid-cols-3 gap-4 mb-4">
            {{-- Present --}}
            <div class="text-center">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-2">
                    <i class="fa-solid fa-check text-green-600 text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-green-600">{{ number_format($attendanceStats['present'] ?? 0) }}</p>
                <p class="text-xs text-gray-500">Hadir</p>
            </div>

            {{-- Late --}}
            <div class="text-center">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-2">
                    <i class="fa-solid fa-clock text-red-600 text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-red-600">{{ number_format($attendanceStats['late'] ?? 0) }}</p>
                <p class="text-xs text-gray-500">Terlambat</p>
            </div>

            {{-- Absent --}}
            <div class="text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-2">
                    <i class="fa-solid fa-xmark text-gray-600 text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-gray-600">{{ number_format($attendanceStats['absent'] ?? 0) }}</p>
                <p class="text-xs text-gray-500">Absen</p>
            </div>
        </div>

        {{-- Quick Action Buttons --}}
        <div class="flex gap-2 pt-4 border-t border-gray-100">
            <a href="{{ route('administrasi.absen.index') }}" class="flex-1 px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 text-center">
                <i class="fa-solid fa-clipboard-list mr-1"></i>
                Lihat Absensi
            </a>
            <a href="{{ route('administrasi.absen.face') }}" class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 text-center">
                <i class="fa-solid fa-camera mr-1"></i>
                Absen
            </a>
        </div>
    </div>
</div>
