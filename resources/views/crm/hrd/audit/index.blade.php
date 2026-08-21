@extends('layouts.app')

@section('title', 'Audit Attendance')
@section('page-title', 'Audit Attendance')
@section('page-title-actions')
    <div class="flex gap-2">
        <input type="date" value="{{ $date }}" onchange="location.href='?date=' + this.value"
               class="px-3 py-2 border border-gray-300 rounded-lg">
    </div>
@endsection

@push('page-actions')
    <div class="flex gap-2">
        <a href="{{ route('administrasi.audit.summary') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
            <i class="fa-solid fa-chart-bar mr-2"></i>Summary
        </a>
        <a href="{{ route('administrasi.absen.export', ['date' => $date]) }}"
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fa-solid fa-download mr-2"></i>Export Excel
        </a>
    </div>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
            <p class="text-sm text-gray-500">Total Absensi</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $stats['suspicious'] }}</p>
            <p class="text-sm text-gray-500">Mencurigakan</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</p>
            <p class="text-sm text-gray-500">Disetujui</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600">{{ $stats['pending'] }}</p>
            <p class="text-sm text-gray-500">Pending Review</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-4">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" id="showSuspiciousOnly" {{ $showSuspicious ? 'checked' : '' }}
                       onchange="location.href='?date={{ $date }}&suspicious=' + this.checked"
                       class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                <span class="ml-2 text-sm text-red-600 font-medium">Tampilkan hanya mencurigakan</span>
            </label>
        </div>
    </div>

    {{-- Attendance List --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Foto Masuk</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jam Masuk</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Foto Pulang</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jam Pulang</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Device</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($attendances as $att)
                    <tr class="{{ $att->is_suspicious ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-600">
                                    {{ strtoupper(substr($att->employee?->full_name ?? 'N', 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $att->employee?->full_name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500">{{ $att->employee?->department?->name ?? '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($att->check_in_photo)
                            <img src="{{ Storage::url($att->check_in_photo) }}"
                                 class="w-12 h-12 rounded-lg object-cover mx-auto cursor-pointer hover:ring-2 hover:ring-blue-500"
                                 onclick="showPhoto('{{ Storage::url($att->check_in_photo) }}')">
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-medium {{ $att->late_minutes > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $att->check_in_formatted }}
                            </span>
                            @if($att->late_minutes > 0)
                            <p class="text-xs text-red-500">+{{ $att->late_minutes }} menit</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($att->check_out_photo)
                            <img src="{{ Storage::url($att->check_out_photo) }}"
                                 class="w-12 h-12 rounded-lg object-cover mx-auto cursor-pointer hover:ring-2 hover:ring-blue-500"
                                 onclick="showPhoto('{{ Storage::url($att->check_out_photo) }}')">
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-medium text-gray-700">
                                {{ $att->check_out_formatted }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($att->check_in_latitude)
                            <div class="text-xs">
                                <p class="text-gray-600">
                                    <i class="fa-solid fa-map-marker-alt text-red-500 mr-1"></i>
                                    {{ Str::limit($att->check_in_address, 20) ?? 'Lokasi' }}
                                </p>
                                <p class="text-gray-400">
                                    {{ number_format($att->check_in_gps_accuracy, 0) }}m
                                </p>
                            </div>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="text-xs text-gray-500">
                                <p><i class="fa-solid fa-desktop mr-1"></i>{{ $att->check_in_device ?? '-' }}</p>
                                <p><i class="fa-solid fa-globe mr-1"></i>{{ $att->check_in_ip ?? '-' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($att->is_suspicious)
                            <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">
                                <i class="fa-solid fa-exclamation-triangle mr-1"></i>Mencurigakan
                            </span>
                            @elseif($att->approved_by)
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                                <i class="fa-solid fa-check mr-1"></i>Approved
                            </span>
                            @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                                Pending
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('administrasi.audit.show', $att->id) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if(!$att->approved_by)
                                <button onclick="approveAttendance({{ $att->id }})"
                                        class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Setujui">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- Suspicious Reasons --}}
                    @if($att->is_suspicious && !empty($att->suspicious_reasons))
                    <tr class="bg-red-50">
                        <td colspan="9" class="px-4 py-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <i class="fa-solid fa-exclamation-triangle text-red-500"></i>
                                <span class="text-sm text-red-700 font-medium">Alasan mencurigakan:</span>
                                @foreach($att->suspicious_reasons as $reason)
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded">{{ $reason }}</span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-clipboard-list text-5xl mb-4"></i>
                            <p class="text-lg">Tidak ada data absensi</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
        <div class="px-4 py-4 border-t">
            {{ $attendances->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Photo Modal --}}
<div id="photoModal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center">
    <img id="photoLarge" src="" class="max-w-[90vw] max-h-[90vh] rounded-xl">
    <button onclick="closePhoto()" class="absolute top-4 right-4 text-white text-2xl">
        <i class="fa-solid fa-times"></i>
    </button>
</div>

<script>
function showPhoto(url) {
    document.getElementById('photoLarge').src = url;
    document.getElementById('photoModal').classList.remove('hidden');
}

function closePhoto() {
    document.getElementById('photoModal').classList.add('hidden');
}

function approveAttendance(id) {
    if (confirm('Setujui absensi ini?')) {
        fetch(`/administrasi/audit/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        }).then(() => location.reload());
    }
}
</script>
@endsection
