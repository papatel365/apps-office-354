{{-- resources/views/crm/hrd/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Data Staff')

@section('page-title', 'Data Staff')

@php
$currentUser = auth()->user();
$hasCompany = $currentUser->company_id || $currentUser->is_developer || $currentUser->is_pusat_admin;
@endphp

@push('page-actions')
    @if($currentUser->company_id)
    <a href="{{ route('companies.members.create', $currentUser->company_id) }}"
       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
        <i class="fa-solid fa-user-plus mr-2"></i>
        Tambah Karyawan
    </a>
    @endif
@endpush

@section('content')
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIK</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Divisi</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontak</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $user->nik ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ \App\Helpers\RoleHelper::label($user->company_role) }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($user->division)
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">{{ $user->division->name }}</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <div>{{ $user->phone ?? '-' }}</div>
                        <div class="text-xs text-gray-400">{{ $user->emergency_contact_phone ?? '' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($user->is_active)
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Aktif</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if($user->company_id)
                            <a href="{{ route('companies.members.edit', [$user->company_id, $user]) }}"
                               class="text-blue-600 hover:text-blue-800">Ubah</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <i class="fa-solid fa-users text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Belum ada data karyawan</p>
                        @if($currentUser->company_id)
                        <a href="{{ route('companies.members.create', $currentUser->company_id) }}"
                           class="mt-2 inline-block px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fa-solid fa-plus mr-2"></i>Tambah Karyawan
                        </a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($users->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
