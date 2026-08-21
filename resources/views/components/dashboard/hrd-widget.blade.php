{{-- resources/views/components/dashboard/hrd-widget.blade.php --}}

@php
    $positionLabels = [
        'Director' => 'Direktur',
        'Manager' => 'Manajer',
        'Supervisor' => 'Supervisor',
        'Staff' => 'Staff',
        'Administrator' => 'Administrator',
        'Admin' => 'Administrator',
        'Owner' => 'Pemilik',
        'Developer' => 'Developer',
    ];

    $translatePosition = function ($position) use ($positionLabels) {
        if (!$position) {
            return '-';
        }

        return $positionLabels[$position] ?? $position;
    };
@endphp

{{-- Staff Stats Row --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    {{-- Total Employees --}}
    <x-dashboard.stat-card
        title="Total Karyawan"
        :value="$stats['total_employees']"
        icon="users"
        color="indigo"
        :subtitle="'<i class=\'fa-solid fa-user-check mr-1\'></i>' . number_format($stats['active_employees']) . ' aktif'"
    />

    {{-- Total Users --}}
    <x-dashboard.stat-card
        title="Total Pengguna"
        :value="$stats['total_users']"
        icon="user"
        color="emerald"
        :subtitle="'<i class=\'fa-solid fa-user-check mr-1\'></i>' . number_format($stats['active_users']) . ' aktif'"
    />
</div>

{{-- Recent Employees Table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900">
            <i class="fa-solid fa-users text-indigo-500 mr-2"></i>
            Karyawan Terbaru
        </h3>

        <a href="{{ route('administrasi.data_karyawan.index') }}"
           class="text-sm text-blue-600 hover:text-blue-800 font-medium">
            Lihat Semua
        </a>
    </div>

    <div class="overflow-x-auto">
        @if($recentEmployees->count() > 0)
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                            Karyawan
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                            Jabatan
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                            Status
                        </th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($recentEmployees as $emp)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <img
                                        class="h-8 w-8 rounded-full"
                                        src="https://ui-avatars.com/api/?name={{ urlencode($emp->full_name) }}&background=667eea&color=fff"
                                        alt=""
                                    >

                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $emp->full_name }}
                                        </p>

                                        <p class="text-xs text-gray-500">
                                            {{ $translatePosition($emp->position?->name) }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $translatePosition($emp->position?->name) }}
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($emp->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-8 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-users text-gray-400"></i>
                </div>

                <p class="text-sm text-gray-500">
                    Belum ada karyawan
                </p>

                <a href="{{ route('administrasi.data_karyawan.wizard.create') ?? '#' }}"
                   class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                    <i class="fa-solid fa-plus mr-1"></i>
                    Tambah Karyawan
                </a>
            </div>
        @endif
    </div>
</div>