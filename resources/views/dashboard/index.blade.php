{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Beranda')
@section('page-title', 'Beranda')

@php
    // Staff module checks
    $hasStaffDashboard = \App\Helpers\SidebarHelper::canAccess('karyawan.dashboard');
    $hasStaffEmployees = \App\Helpers\SidebarHelper::canAccess('staff.employees');
    $hasStaffAttendances = \App\Helpers\SidebarHelper::canAccess('staff.attendances');
    $hasStaffReports = \App\Helpers\SidebarHelper::canAccess('staff.reports');

    // Check if user has ANY module access (beyond dashboard)
    $hasAnyModule = $hasStaffDashboard || $hasStaffEmployees || $hasStaffAttendances || $hasStaffReports;
@endphp

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Welcome Header --}}
    <div class="mb-8 animate-fadeIn">
        <div class="md:flex md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Selamat Datang, {{ $displayName }}!</h1>
                <p class="mt-1 text-sm text-gray-500">
                    <span class="ml-2 text-xs text-gray-400">
                        <i class="fa-solid fa-clock mr-1"></i>
                        {{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                    </span>
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center gap-3">
                <span class="inline-flex items-center rounded-md bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                    <span class="flex h-2 w-2 mr-2">
                        <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    Online
                </span>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- EMPTY STATE: User only has dashboard permission --}}
    {{-- ============================================================ --}}
    @if(!$hasAnyModule)
        <x-dashboard.empty-state-widget />
    @endif

    {{-- ============================================================ --}}
    {{-- BAGIAN STAFF --}}
    {{-- ============================================================ --}}
    @if($hasStaffDashboard || $hasStaffEmployees)
        <x-dashboard.hrd-widget
            :stats="$stats"
            :recentEmployees="$recentEmployees"
        />
    @endif

    {{-- ============================================================ --}}
    {{-- SECONDARY ROW: Quick Actions, Attendance --}}
    {{-- ============================================================ --}}
    @if($hasAnyModule)
    <div class="grid grid-cols-1 lg:grid-cols-{{ $hasStaffAttendances ? '2' : '1' }} gap-6 mb-6">
        {{-- Quick Actions --}}
        <div class="{{ $hasStaffAttendances ? 'lg:col-span-1' : 'col-span-1' }}">
            <x-dashboard.quick-action-widget />
        </div>

        {{-- Attendance Widget (if has attendance permission) --}}
        @if($hasStaffAttendances)
        <div class="lg:col-span-1">
            <x-dashboard.attendance-widget
                :attendanceStats="$attendanceStats"
            />
        </div>
        @endif
    </div>
    @endif

    {{-- ============================================================ --}}
    {{-- ACTIVITY SECTION --}}
    {{-- ============================================================ --}}
    @if($hasAnyModule)
    <div class="mb-6">
        <x-dashboard.activity-widget
            :recentActivities="$recentActivities"
        />
    </div>
    @endif
</div>
@endsection
