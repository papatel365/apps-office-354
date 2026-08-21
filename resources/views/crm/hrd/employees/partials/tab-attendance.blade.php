{{-- Tab Attendance --}}
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <i class="fa-solid fa-calendar-check mr-2 text-indigo-600"></i>
        Riwayat Absensi
    </h3>

    {{-- Calendar Component --}}
    @include('crm.hrd.employees.partials.calendar-attendance', ['employeeId' => $employee->id])
</div>
