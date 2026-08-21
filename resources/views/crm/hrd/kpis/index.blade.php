@extends('layouts.app')

@section('title', 'Pengaturan KPI')

@section('page-title', 'Pengaturan KPI')

@push('page-actions')
    <div class="flex gap-2">
        <a href="{{ route('staff.kpis.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fa-solid fa-plus mr-2"></i>Tambah KPI
        </a>
    </div>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
            <p class="text-sm text-gray-500">Total KPI</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</p>
            <p class="text-sm text-gray-500">Aktif</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['by_position'] }}</p>
            <p class="text-sm text-gray-500">Per Posisi</p>
        </div>
    </div>

    {{-- KPI Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Daftar KPI</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">KPI</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posisi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bobot</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($kpis as $kpi)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $kpi->name }}</p>
                            <p class="text-sm text-gray-500">{{ $kpi->category ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $kpi->position?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ number_format($kpi->target, 2) }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $kpi->target_unit ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ number_format($kpi->weight, 1) }}%
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" class="text-blue-600 hover:text-blue-800">
                                <i class="fa-solid fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <i class="fa-solid fa-chart-line text-3xl mb-2"></i>
                            <p>Belum ada data KPI</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
