{{-- Tab Leave --}}
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
            <i class="fa-solid fa-calendar-minus mr-2 text-indigo-600"></i>
            Hak Cuti & Riwayat
        </h3>
    </div>

    {{-- Leave Balance Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4">
            <p class="text-sm text-blue-600 font-medium">Jatah Cuti</p>
            <p class="text-2xl font-bold text-blue-700">{{ $employee->leave_balance ?? 0 }}</p>
            <p class="text-xs text-blue-500">hari</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4">
            <p class="text-sm text-green-600 font-medium">Terpakai</p>
            <p class="text-2xl font-bold text-green-700">{{ $employee->leaves->whereIn('status', ['approved', 'approved_supervisor'])->count() }}</p>
            <p class="text-xs text-green-500">kali</p>
        </div>
        <div class="bg-amber-50 rounded-xl p-4">
            <p class="text-sm text-amber-600 font-medium">Pending</p>
            <p class="text-2xl font-bold text-amber-700">{{ $employee->leaves->where('status', 'pending')->count() }}</p>
            <p class="text-xs text-amber-500">menunggu</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4">
            <p class="text-sm text-purple-600 font-medium">Sisa</p>
            <p class="text-2xl font-bold text-purple-700">{{ ($employee->leave_balance ?? 0) - $employee->leaves->whereIn('status', ['approved', 'approved_supervisor'])->count() }}</p>
            <p class="text-xs text-purple-500">hari</p>
        </div>
    </div>

    {{-- Leave History --}}
    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Riwayat Cuti</h4>
    @if($employee->leaves->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($employee->leaves->take(10) as $leave)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">
                            {{ $leave->leaveType?->name ?? $leave->leave_type ?? 'Cuti' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $leave->start_date?->format('d M') }} - {{ $leave->end_date?->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $leave->total_days }} hari
                    </td>
                    <td class="px-4 py-3">
                        @switch($leave->status)
                            @case('pending')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Menunggu</span>
                            @break
                            @case('approved_supervisor')
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">Disetujui SPV</span>
                            @break
                            @case('approved')
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Disetujui</span>
                            @break
                            @case('rejected')
                            <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Ditolak</span>
                            @break
                            @case('cancelled')
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Dibatalkan</span>
                            @break
                            @default
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">{{ ucfirst($leave->status) }}</span>
                        @endswitch
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ Str::limit($leave->reason ?? '-', 30) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-8">
        <div class="w-12 h-12 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-3">
            <i class="fa-solid fa-calendar text-gray-300"></i>
        </div>
        <p class="text-gray-500">Belum ada riwayat cuti</p>
    </div>
    @endif
</div>
