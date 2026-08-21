@extends('layouts.app')

@section('title', 'Staff')

@section('page-title', 'Staff')

@php
    // Helper function for notification colors
    $getNotificationColor = function($type, $suffix = '') {
        $colors = [
            'danger' => 'red',
            'warning' => 'amber',
            'success' => 'green',
            'info' => 'blue',
        ];
        $color = $colors[$type] ?? 'blue';
        return $color . $suffix;
    };
@endphp

@section('content')
<div class="space-y-6">
    {{-- Smart Notifications --}}
    @if(count($notifications) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($notifications as $notification)
        @php
            $notifColor = $getNotificationColor($notification['type']);
            $notifColorText = $getNotificationColor($notification['type'], '-600');
        @endphp
        <div class="bg-{{ $notifColor }}-50 border border-{{ $notifColor }}-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <div class="p-2 bg-white rounded-lg">
                    <i class="fa-solid {{ $notification['icon'] }} text-{{ $notifColorText }}"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-800">{{ $notification['title'] }}</h4>
                    <p class="text-sm text-gray-600 mt-1">{{ $notification['message'] }}</p>
                    <a href="{{ $notification['action'] }}" class="text-sm text-{{ $notifColorText }} hover:underline mt-2 inline-block">
                        Lihat Detail <i class="fa-solid fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Employee Stats Row - Simplified --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total Karyawan</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($totalEmployees) }}</p>
                </div>
                <i class="fa-solid fa-users text-3xl text-blue-200"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Karyawan Aktif</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($activeEmployees) }}</p>
                </div>
                <i class="fa-solid fa-user-check text-3xl text-green-200"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Baru Bulan Ini</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($newEmployeesThisMonth) }}</p>
                </div>
                <i class="fa-solid fa-user-plus text-3xl text-purple-200"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm">Kontrak Akan Habis</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($expiringContracts) }}</p>
                </div>
                <i class="fa-solid fa-clock text-3xl text-amber-200"></i>
            </div>
        </div>
    </div>

    {{-- Recent Activity - Simplified Layout --}}
    <div class="grid grid-cols-1 gap-6">
        {{-- Recent Attendances - Full Width --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">
                    <i class="fa-solid fa-clipboard-list mr-2 text-blue-600"></i>
                    Absensi Terbaru
                </h3>
                <a href="{{ route('administrasi.absen.index') }}" class="text-sm text-blue-600 hover:underline">Lihat Semua</a>
            </div>
            <div class="divide-y">
                @forelse($recentAttendances as $att)
                <div class="p-4 flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-sm font-bold text-gray-500">
                        {{ strtoupper(substr($att->employee?->full_name ?? 'N', 0, 2)) }}
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">{{ $att->employee?->full_name ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-500">{{ $att->employee?->department?->name ?? '-' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium {{ $att->late_minutes > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $att->check_in_formatted }}
                        </p>
                        @if($att->late_minutes > 0)
                        <p class="text-xs text-red-500">Terlambat {{ $att->late_minutes }} menit</p>
                        @endif
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-400">
                    <i class="fa-solid fa-clipboard text-4xl mb-3"></i>
                    <p>Belum ada data absensi hari ini</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
