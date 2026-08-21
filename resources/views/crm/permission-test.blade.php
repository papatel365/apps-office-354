{{-- resources/views/crm/permission-test.blade.php --}}
@extends('layouts.app')

@section('title', 'Test Izin Sidebar')

@section('page-title', 'Test Izin Sidebar')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fa-solid fa-user-shield text-blue-500 mr-2"></i>
            Info User
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Nama</p>
                <p class="font-medium">{{ $user->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Role</p>
                <p class="font-medium">{{ $user->display_role }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Division</p>
                <p class="font-medium">
                    @if($user->division)
                        {{ $user->division->name }}
                        @if($user->division->is_active)
                            <span class="text-green-600 text-xs">(Aktif)</span>
                        @else
                            <span class="text-red-600 text-xs">(Nonaktif)</span>
                        @endif
                    @else
                        <span class="text-gray-400">Tidak ada divisi</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Is Developer</p>
                <p class="font-medium {{ $user->is_developer ? 'text-green-600' : 'text-gray-400' }}">
                    {{ $user->is_developer ? 'YES' : 'NO' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Division Permissions --}}
    @if($user->division)
    <div class="bg-purple-50 border border-purple-200 rounded-xl p-6 mb-6">
        <h3 class="text-lg font-semibold text-purple-900 mb-4">
            <i class="fa-solid fa-sitemap mr-2"></i>
            Izin dari Divisi: {{ $user->division->name }}
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @forelse($user->division->sidebar_permissions ?? [] as $perm)
                <div class="flex items-center p-2 bg-white rounded-lg border border-purple-200">
                    <i class="fa-solid fa-check-circle text-green-500 mr-2"></i>
                    <span class="text-sm">{{ $allPermissions[$perm] ?? $perm }}</span>
                </div>
            @empty
                <div class="col-span-full text-purple-700">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    Default: Beranda, Klien, Proyek, Tugas
                </div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- User Individual Permissions --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-4">
            <i class="fa-solid fa-user mr-2"></i>
            Izin Individual User
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @forelse($user->sidebar_permissions ?? [] as $perm)
                <div class="flex items-center p-2 bg-white rounded-lg border border-blue-200">
                    <i class="fa-solid fa-check text-blue-500 mr-2"></i>
                    <span class="text-sm">{{ $allPermissions[$perm] ?? $perm }}</span>
                </div>
            @empty
                <div class="col-span-full text-blue-700">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    Menggunakan default dari role: {{ $user->display_role }}
                </div>
            @endforelse
        </div>
    </div>

    {{-- Effective Permissions (Test Results) --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fa-solid fa-clipboard-check text-green-500 mr-2"></i>
            Hasil Test: hasSidebarPermission()
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @foreach($results as $key => $result)
                <div class="flex items-center p-3 rounded-lg border {{ $result['can_access'] ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' }}">
                    <i class="fa-solid {{ $result['can_access'] ? 'fa-check-circle text-green-500' : 'fa-times-circle text-gray-400' }} mr-2"></i>
                    <span class="text-sm {{ $result['can_access'] ? 'text-green-700 font-medium' : 'text-gray-500' }}">
                        {{ $result['label'] }}
                    </span>
                    <span class="ml-auto text-xs {{ $result['can_access'] ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $result['can_access'] ? '✓' : '✗' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
