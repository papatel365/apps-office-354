@extends('layouts.app')

@section('title', 'Training')

@section('page-title', 'Manajemen Training')

@push('page-actions')
    <div class="flex gap-2">
        <a href="{{ route('administrasi.trainings.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fa-solid fa-plus mr-2"></i>Buat Training
        </a>
    </div>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
            <p class="text-sm text-gray-500">Total Training</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['upcoming'] }}</p>
            <p class="text-sm text-gray-500">Akan Datang</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['ongoing'] }}</p>
            <p class="text-sm text-gray-500">Sedang Berlangsung</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-purple-600">{{ $stats['participants'] }}</p>
            <p class="text-sm text-gray-500">Total Peserta</p>
        </div>
    </div>

    {{-- Training Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Daftar Training</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Judul</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Peserta</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Biaya</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($trainings as $training)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $training->title }}</p>
                            <p class="text-sm text-gray-500">{{ ucfirst($training->type) }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $training->start_date?->format('d M Y') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $training->participants_count ?? 0 }} / {{ $training->max_participants ?? '∞' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            Rp {{ number_format($training->cost ?? 0, 0) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full {{ $training->status === 'completed' ? 'bg-green-100 text-green-700' : ($training->status === 'ongoing' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($training->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('administrasi.trainings.show', $training->id) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <i class="fa-solid fa-graduation-cap text-3xl mb-2"></i>
                            <p>Belum ada data training</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
