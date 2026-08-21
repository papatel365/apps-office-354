{{-- resources/views/crm/hrd/reports/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('page-title-actions')
    <div class="flex gap-2">
        <a href="{{ route('laporan.absensi') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fa-solid fa-calendar-check mr-2"></i>Lihat Semua Laporan
        </a>
    </div>
@endsection

@section('content')
{{-- Header Info --}}
<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-900">Seluruh Laporan Perusahaan</h2>
    <p class="text-sm text-gray-500">
        Dicetak: {{ $generatorInfo['generated_at'] }}
        <span class="mx-2">|</span>
        Oleh: {{ $generatorInfo['generated_by'] }}
    </p>
</div>


{{-- Report Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

    {{-- Employee Report Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-indigo-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-600 flex items-center justify-center">
                        <i class="fa-solid fa-users text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Laporan Karyawan</h3>
                        <p class="text-sm text-gray-600">Data Karyawan</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($employeeSummary['permanent']) }}</p>
                    <p class="text-xs text-gray-500">Karyawan Tetap</p>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-2xl font-bold text-amber-600">{{ number_format($employeeSummary['contract']) }}</p>
                    <p class="text-xs text-gray-500">Kontrak</p>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($employeeSummary['probation']) }}</p>
                    <p class="text-xs text-gray-500">Masa Percobaan</p>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-2xl font-bold text-gray-600">{{ number_format($employeeSummary['resigned']) }}</p>
                    <p class="text-xs text-gray-500">Resign</p>
                </div>
            </div>
            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                <span>Rata-rata Masa Kerja</span>
                <span class="font-bold text-indigo-600">{{ $employeeSummary['avg_tenure'] }} tahun</span>
            </div>
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>Rata-rata Usia</span>
                <span class="font-bold text-indigo-600">{{ $employeeSummary['avg_age'] }} tahun</span>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <div class="flex gap-2">
                <a href="{{ route('laporan.karyawan') }}" class="flex-1 px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 text-center">
                    <i class="fa-solid fa-eye mr-1"></i>Lihat
                </a>
                <a href="{{ route('administrasi.laporan.export.pdf', 'employees') }}" class="px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">
                    <i class="fa-solid fa-file-pdf"></i>
                </a>
                <a href="{{ route('administrasi.laporan.export.excel', 'employees') }}" class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                    <i class="fa-solid fa-file-excel"></i>
                </a>
                <a href="{{ route('administrasi.laporan.export.word', 'employees') }}" class="px-3 py-2 bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-800">
                    <i class="fa-solid fa-file-word"></i>
                </a>
            </div>
        </div>
    </div>


    {{-- Attendance Report Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center">
                        <i class="fa-solid fa-calendar-check text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Laporan Absensi</h3>
                        <p class="text-sm text-gray-600">{{ $monthName }} {{ $currentYear }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-2xl font-bold text-green-600">{{ number_format($attendanceSummary['present']) }}</p>
                    <p class="text-xs text-gray-500">Hadir</p>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-2xl font-bold text-gray-600">{{ number_format($attendanceSummary['absent']) }}</p>
                    <p class="text-xs text-gray-500">Tidak Hadir</p>
                </div>
            </div>
            <div class="space-y-3">

            <div class="flex items-center justify-between text-sm text-gray-600">
                <span>Rate Kehadiran</span>
                <span class="font-bold text-green-600">
                    {{ $attendanceSummary['attendance_rate'] }}%
                </span>
            </div>

            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full"
                    style="width: {{ $attendanceSummary['attendance_rate'] }}%">
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 space-y-2">

                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>Total Hari Kerja</span>
                    <span class="font-semibold text-blue-600">
                        {{ $attendanceSummary['working_days'] ?? '-' }} Hari
                    </span>
                </div>

                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>Total Absensi</span>
                    <span class="font-semibold text-blue-600">
                        {{ $attendanceSummary['present'] + $attendanceSummary['absent'] }}
                    </span>
                </div>

            </div>

        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <div class="flex gap-2">
                <a href="{{ route('laporan.absensi') }}" class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 text-center">
                    <i class="fa-solid fa-eye mr-1"></i>Lihat
                </a>
                <a href="{{ route('administrasi.laporan.export.pdf', 'attendance') }}" class="px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">
                    <i class="fa-solid fa-file-pdf"></i>
                </a>
                <a href="{{ route('administrasi.laporan.export.excel', 'attendance') }}" class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                    <i class="fa-solid fa-file-excel"></i>
                </a>
                <a href="{{ route('administrasi.laporan.export.word', 'attendance') }}" class="px-3 py-2 bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-800">
                    <i class="fa-solid fa-file-word"></i>
                </a>
            </div>
        </div>
    </div>

</div>


@endsection

@push('styles')
<style>
@media print {
    aside, header, button, a { display: none !important; }
    .max-w-7xl { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
    body { background: white !important; }
    .shadow-sm, .shadow-lg { box-shadow: none !important; }
    .rounded-xl { border-radius: 0 !important; }
    .border { border: 1px solid #ddd !important; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(attendanceCtx, {
        type: 'bar',
        data: {
            labels: ['Hadir', 'Terlambat', 'Tidak Hadir', 'Izin', 'Sakit', 'WFH'],
            datasets: [{
                label: 'Jumlah',
                data: [
                    {{ $attendanceSummary['present'] }},
                    {{ $attendanceSummary['late'] }},
                    {{ $attendanceSummary['absent'] }},
                    {{ $attendanceSummary['permit'] }},
                    {{ $attendanceSummary['sick'] }},
                    {{ $attendanceSummary['wfh'] }}
                ],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(59, 130, 246, 0.8)'
                ],
                borderWidth: 0,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Employee Status Chart
    const employeeCtx = document.getElementById('employeeStatusChart').getContext('2d');
    new Chart(employeeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Karyawan Tetap', 'Kontrak', 'Masa Percobaan', 'Resign'],
            datasets: [{
                data: [
                    {{ $employeeSummary['permanent'] }},
                    {{ $employeeSummary['contract'] }},
                    {{ $employeeSummary['probation'] }},
                    {{ $employeeSummary['resigned'] }}
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(107, 114, 128, 0.8)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
@endpush
