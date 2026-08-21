{{-- resources/views/crm/staff/laporan/attendance.blade.php --}}
@extends('layouts.app')

@section('title', 'Laporan Absensi')
@section('page-title', 'Laporan Absensi')
@section('page-title-actions')
    <div class="flex gap-2">
        <a href="{{ route('administrasi.laporan.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
            <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
            <i class="fa-solid fa-print mr-2"></i>Print
        </button>
    </div>
@endsection

@push('page-actions')
    <div class="flex gap-2">
        <a href="{{ route('laporan.absensi.export.pdf', 'attendance') }}?{{ $exportQuery ?? '' }}" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            <i class="fa-solid fa-file-pdf mr-1"></i> PDF
        </a>
        <a href="{{ route('laporan.absensi.export.excel', 'attendance') }}?{{ $exportQuery ?? '' }}" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fa-solid fa-file-excel mr-1"></i> Excel
        </a>
        <a href="{{ route('laporan.absensi.export.word', 'attendance') }}?{{ $exportQuery ?? '' }}" class="px-3 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800">
            <i class="fa-solid fa-file-word mr-1"></i> Word
        </a>
    </div>
@endpush

@section('content')
{{-- Filter Section --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <form method="GET" action="{{ route('laporan.absensi') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
            <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Semua Bulan</option>
                @foreach($filterOptions['months'] as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['month'] ?? now()->month) == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
            <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @foreach($filterOptions['years'] as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['year'] ?? now()->year) == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
            <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Semua Departemen</option>
                @foreach($filterOptions['departments'] as $dept)
                    <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Divisi</label>
            <select name="division_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Semua Divisi</option>
                @foreach($filterOptions['divisions'] as $div)
                    <option value="{{ $div->id }}" {{ ($filters['division_id'] ?? '') == $div->id ? 'selected' : '' }}>{{ $div->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-5 flex items-center gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fa-solid fa-filter mr-1"></i>Filter
            </button>
            <a href="{{ route('laporan.absensi') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fa-solid fa-rotate-left mr-1"></i>Reset
            </a>
        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">

    <div class="bg-white rounded-xl shadow border border-gray-200 p-6">
        <div class="text-center">
            <h2 class="text-4xl font-bold text-green-600">
                {{ number_format($summary['present']) }}
            </h2>
            <p class="mt-2 text-sm font-medium text-gray-500 uppercase">
                Hadir
            </p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-200 p-6">
        <div class="text-center">
            <h2 class="text-4xl font-bold text-red-600">
                {{ number_format($summary['absent']) }}
            </h2>
            <p class="mt-2 text-sm font-medium text-gray-500 uppercase">
                Tidak Hadir
            </p>
        </div>
    </div>

</div>

{{-- Report Info --}}
<div class="bg-blue-50 rounded-xl border border-blue-200 p-4 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-blue-900">Laporan Absensi</h3>
            <p class="text-sm text-blue-700">{{ $monthName ?? '' }} {{ $year ?? date('Y') }}</p>
        </div>
        <div class="text-right text-sm text-blue-700">
            <p>Dicetak: {{ $generatorInfo['generated_at'] }}</p>
            <p>Oleh: {{ $generatorInfo['generated_by'] }}</p>
        </div>
    </div>
</div>

{{-- Data Table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Karyawan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hari</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hadir</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tidak Hadir</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($byEmployee as $index => $emp)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($emp['employee_name']) }}&background=667eea&color=fff" alt="">
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $emp['employee_name'] }}</div>
                                <div class="text-sm text-gray-500">{{ $emp['position'] ?? '-' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $emp['department'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">{{ $emp['total_days'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $emp['present'] }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">{{ $emp['absent'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fa-solid fa-calendar-xmark text-gray-300 text-4xl mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">Belum ada data absensi</h3>
                            <p class="text-sm text-gray-500">Data absensi pada periode ini belum tersedia.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Filter Info --}}
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-right">
        @if($hasFilters)
            <div class="text-sm text-gray-600">
                <span class="font-medium">Filter:</span>
                @foreach($filterInfo as $info)
                    <span class="ml-2">{{ $info }}</span>
                    @if(!$loop->last)<span class="mx-1">|</span>@endif
                @endforeach
            </div>
        @else
            <div class="text-sm text-gray-500">
                <span class="font-medium">Filter:</span> Tidak menggunakan filter (Seluruh Data)
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    aside, header, button, a:not(.print-hide), form { display: none !important; }
    .max-w-7xl { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
    body { background: white !important; }
}
</style>
@endpush
