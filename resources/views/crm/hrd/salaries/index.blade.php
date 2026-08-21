{{-- resources/views/crm/staff/salaries/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Data Gaji')

@section('page-title', 'Data Gaji Karyawan')

@push('page-actions')
    <button type="button"
            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
        <i class="fa-solid fa-plus mr-2"></i>
        Tambah Gaji
    </button>
@endpush

@section('content')
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gaji Pokok</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tunjangan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Potongan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Gaji</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Bayar</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($salaries as $salary)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">
                                {{ strtoupper(substr($salary->user->name ?? 'N', 0, 2)) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $salary->user->name ?? 'N/A' }}</div>
                                <div class="text-sm text-gray-500">{{ $salary->user->email ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        Rp {{ number_format($salary->basic_salary, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        Rp {{ number_format($salary->allowances ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        Rp {{ number_format($salary->deductions ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        Rp {{ number_format($salary->total_salary, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $salary->payment_date ? $salary->payment_date->format('d M Y') : '-' }}
                    </td>
                    <td class="px-6 py-4">
                        @if($salary->payment_status === 'paid')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Lunas</span>
                        @elseif($salary->payment_status === 'pending')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Menunggu</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">{{ $salary->payment_status ?? '-' }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button type="button" class="text-blue-600 hover:text-blue-800 mr-2">Ubah</button>
                        <button type="button" class="text-red-600 hover:text-red-800">Hapus</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <i class="fa-solid fa-money-bills text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Belum ada data gaji</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($salaries->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $salaries->links() }}
        </div>
    @endif
</div>
@endsection
