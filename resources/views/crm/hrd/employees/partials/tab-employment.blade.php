{{-- Tab Employment --}}
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <i class="fa-solid fa-briefcase mr-2 text-indigo-600"></i>
        Data Pekerjaan
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
            <label class="block text-sm text-gray-500">Departemen</label>
            <p class="font-medium text-gray-900">{{ $employee->department?->name ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Divisi</label>
            <p class="font-medium text-gray-900">{{ $employee->division?->name ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Posisi / Jabatan</label>
            <p class="font-medium text-gray-900">{{ $employee->position?->name ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Atasan Langsung</label>
            <p class="font-medium text-gray-900">{{ $employee->supervisor?->full_name ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Tipe Karyawan</label>
            <p class="font-medium text-gray-900">{{ $employee->employeeType?->name ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Status Karyawan</label>
            <p class="font-medium">
                @if($employee->is_active)
                    <span class="text-green-600">Aktif</span>
                @else
                    <span class="text-red-600">Resign</span>
                @endif
            </p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Tanggal Bergabung</label>
            <p class="font-medium text-gray-900">{{ $employee->join_date?->format('d M Y') ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Mulai Kerja</label>
            <p class="font-medium text-gray-900">{{ $employee->contract_start?->format('d M Y') ?? '-' }}</p>
        </div>
    </div>

</div>
