@extends('layouts.app')

@section('title', $placement->name)

@section('page-title', $placement->name)
@section('page-subtitle', 'Detail lokasi penempatan')

@push('page-actions')
    <a href="{{ route('administrasi.placements.edit', $placement->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
        <i class="fa-solid fa-pen mr-2"></i>
        Edit
    </a>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Placement Info Card --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center">
                    <i class="fa-solid fa-map-marker-alt text-indigo-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $placement->name }}</h3>
                    @if($placement->code)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                        {{ $placement->code }}
                    </span>
                    @endif
                </div>
            </div>
            <div>
                @if($placement->is_active)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                    Aktif
                </span>
                @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                    Tidak Aktif
                </span>
                @endif
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Alamat</p>
                    <p class="text-gray-900">{{ $placement->address ?? '-' }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Kota</p>
                    <p class="text-gray-900">{{ $placement->city ?? '-' }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Radius Absensi</p>
                    <p class="text-gray-900">
                        @if($placement->radius_meters)
                        <span class="inline-flex items-center">
                            <i class="fa-solid fa-ruler text-gray-400 mr-1"></i>
                            {{ $placement->radius_meters }} meter
                        </span>
                        @else
                        -
                        @endif
                    </p>
                </div>

                @if($placement->latitude && $placement->longitude)
                <div>
                    <p class="text-sm text-gray-500">Koordinat</p>
                    <p class="text-gray-900 font-mono text-sm">
                        {{ $placement->latitude }}, {{ $placement->longitude }}
                    </p>
                </div>
                @endif

                <div>
                    <p class="text-sm text-gray-500">Karyawan Aktif</p>
                    <p class="text-gray-900 text-xl font-bold">{{ $employees->count() }} orang</p>
                </div>
            </div>

            @if($placement->description)
            <div class="mt-6 pt-6 border-t border-gray-100">
                <p class="text-sm text-gray-500 mb-1">Deskripsi</p>
                <p class="text-gray-700">{{ $placement->description }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Map Placeholder --}}
    @if($placement->latitude && $placement->longitude)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">
                <i class="fa-solid fa-map text-indigo-600 mr-2"></i>
                Lokasi di Peta
            </h3>
        </div>
        <div class="h-64 bg-gray-100 flex items-center justify-center">
            <div class="text-center text-gray-500">
                <i class="fa-solid fa-map text-4xl mb-2 text-gray-300"></i>
                <p>Koordinat: {{ $placement->latitude }}, {{ $placement->longitude }}</p>
                <p class="text-sm mt-1">Radius: {{ $placement->radius_meters }} meter</p>
                <a href="https://www.google.com/maps?q={{ $placement->latitude }},{{ $placement->longitude }}"
                    target="_blank"
                    class="inline-flex items-center mt-3 px-3 py-1.5 text-sm bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100">
                    <i class="fa-solid fa-external-link-alt mr-2"></i>
                    Buka di Google Maps
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Employees List --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">
                <i class="fa-solid fa-users text-indigo-600 mr-2"></i>
                Karyawan di Lokasi Ini
            </h3>
        </div>

        @if($employees->count() > 0)
        <div class="divide-y divide-gray-100">
            @foreach($employees as $employee)
            <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-indigo-600 font-semibold">
                            {{ strtoupper(substr($employee->user->name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $employee->user->name ?? 'Unknown' }}</p>
                        <p class="text-sm text-gray-500">{{ $employee->nik }} • {{ $employee->position?->name ?? '-' }}</p>
                    </div>
                </div>
                <a href="{{ route('administrasi.data_karyawan.show', $employee->id) }}"
                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                    Detail
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div class="px-6 py-8 text-center text-gray-500">
            <i class="fa-solid fa-users text-4xl mb-3 text-gray-300"></i>
            <p>Belum ada karyawan yang ditugaskan ke lokasi ini</p>
        </div>
        @endif
    </div>
</div>
@endsection
