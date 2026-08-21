@extends('layouts.app')

@section('title', 'Organisasi')

@section('page-title', 'Struktur Organisasi')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Struktur Organisasi</h3>

        @if($departments->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <i class="fa-solid fa-sitemap text-4xl mb-2"></i>
            <p>Belum ada data struktur organisasi</p>
            <p class="text-sm">Silakan tambah departemen terlebih dahulu</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <div class="flex flex-col items-center">
                {{-- CEO / Top Level --}}
                <div class="text-center mb-8">
                    <div class="w-32 h-32 rounded-full bg-purple-100 border-2 border-purple-300 flex items-center justify-center mx-auto">
                        <span class="text-2xl font-bold text-purple-600">CEO</span>
                    </div>
                    <p class="mt-2 font-medium text-gray-900">Direksi</p>
                </div>

                {{-- Connection Line --}}
                <div class="w-px h-8 bg-gray-300"></div>

                {{-- Departments --}}
                <div class="flex flex-wrap justify-center gap-8">
                    @foreach($departments as $department)
                    <div class="text-center">
                        <div class="w-40 h-24 rounded-lg bg-blue-100 border-2 border-blue-300 flex items-center justify-center p-2">
                            <div>
                                <p class="font-medium text-blue-900 text-sm">{{ $department->name }}</p>
                                <p class="text-xs text-blue-600">{{ $department->employees->count() }} karyawan</p>
                            </div>
                        </div>
                        <p class="mt-2 text-sm font-medium text-gray-700">{{ $department->name }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Department List --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Daftar Departemen</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($departments as $department)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $department->name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $department->employees->count() }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full {{ $department->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $department->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                            Tidak ada data departemen
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
