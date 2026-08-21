{{--
    Quick Action Widget
    Shows quick action buttons based on user permissions
    Each action is only shown if user has the corresponding permission
--}}

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-base font-semibold text-gray-900">Aksi Cepat</h3>
    </div>
    <div class="p-4 grid grid-cols-2 gap-3">

        {{-- Add Employee - Only if has 'staff.employees' permission --}}
        @canSidebar('staff.employees')
        <a href="{{ route('administrasi.data_karyawan.wizard.create') ?? '#' }}" class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-dashed border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all group">
            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center mb-2 group-hover:bg-indigo-200 transition-colors">
                <i class="fa-solid fa-user-plus text-indigo-600"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">Tambah Karyawan</span>
        </a>
        @endcanSidebar

        {{-- Absen - Only if has 'staff.attendances' permission --}}
        @canSidebar('staff.attendances')
        {{-- <a href="{{ route('staff.face-attendance') }}" class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-dashed border-gray-200 hover:border-green-300 hover:bg-green-50 transition-all group"> --}}
            <!-- <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mb-2 group-hover:bg-green-200 transition-colors">
                <i class="fa-solid fa-camera text-green-600"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">Absen</span>
        </a> -->
        @endcanSidebar

        {{-- Download Report - Only if has 'staff.reports' permission --}}
        @canSidebar('staff.reports')
        <a href="{{ route('administrasi.laporan.index') }}" class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-dashed border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all group">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mb-2 group-hover:bg-blue-200 transition-colors">
                <i class="fa-solid fa-file-arrow-down text-blue-600"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">Download Laporan</span>
        </a>
        @endcanSidebar
    </div>
</div>
