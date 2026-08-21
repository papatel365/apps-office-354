{{-- resources/views/crm/staff/leaves/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Data Cuti/Izin')

@section('page-title', 'Data Cuti dan Izin Karyawan')

@push('page-actions')
    <button type="button"
            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
        <i class="fa-solid fa-plus mr-2"></i>
        Tambah Cuti/Izin
    </button>
@endpush

@section('content')
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Mulai</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Selesai</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Hari</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($leaves as $leave)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">
                                {{ strtoupper(substr($leave->user->name ?? 'N', 0, 2)) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $leave->user->name ?? 'N/A' }}</div>
                                <div class="text-sm text-gray-500">{{ $leave->user->email ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($leave->leave_type === 'annual')
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Cuti Tahunan</span>
                        @elseif($leave->leave_type === 'sick')
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Sakit</span>
                        @elseif($leave->leave_type === 'emergency')
                            <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs">Darurat</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">{{ $leave->leave_type ?? '-' }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $leave->start_date ? $leave->start_date->format('d M Y') : '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $leave->end_date ? $leave->end_date->format('d M Y') : '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $leave->total_days ?? 0 }} hari
                    </td>
                    <td class="px-6 py-4">
                        @if($leave->status === 'approved')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Disetujui</span>
                        @elseif($leave->status === 'pending')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Menunggu</span>
                        @elseif($leave->status === 'rejected')
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Ditolak</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">{{ $leave->status ?? '-' }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ Str::limit($leave->reason ?? '-', 30) }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button type="button" class="text-blue-600 hover:text-blue-800 mr-2">Ubah</button>
                        <button type="button" class="text-red-600 hover:text-red-800">Hapus</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <i class="fa-solid fa-calendar-minus text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Belum ada data cuti/izin</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($leaves->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $leaves->links() }}
        </div>
    @endif
</div>
@endsection
