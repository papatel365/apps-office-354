@extends('layouts.app')

@section('title', 'Evaluasi Kinerja')

@section('page-title', 'Evaluasi Kinerja Karyawan')

@push('page-actions')
    <div class="flex gap-2">
        <a href="{{ route('staff.performances.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fa-solid fa-plus mr-2"></i>Buat Evaluasi Baru
        </a>
    </div>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
            <p class="text-sm text-gray-500">Total Evaluasi</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['completed'] }}</p>
            <p class="text-sm text-gray-500">Selesai</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
            <p class="text-sm text-gray-500">Menunggu</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['avg_score'], 1) }}</p>
            <p class="text-sm text-gray-500">Rata-rata Skor</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="year" class="w-full rounded-lg border-gray-300">
                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                <select name="period" class="w-full rounded-lg border-gray-300">
                    <option value="all" {{ $period == 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="annual" {{ $period == 'annual' ? 'selected' : '' }}>Tahunan</option>
                    <option value="q1" {{ $period == 'q1' ? 'selected' : '' }}>Q1</option>
                    <option value="q2" {{ $period == 'q2' ? 'selected' : '' }}>Q2</option>
                    <option value="q3" {{ $period == 'q3' ? 'selected' : '' }}>Q3</option>
                    <option value="q4" {{ $period == 'q4' ? 'selected' : '' }}>Q4</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fa-solid fa-filter mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    {{-- Reviews Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Daftar Evaluasi Kinerja</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Skor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($reviews as $review)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-blue-600 font-bold">{{ substr($review->employee?->full_name ?? 'N', 0, 1) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $review->employee?->full_name ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-500">{{ $review->employee?->position?->name ?? '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-gray-700">{{ strtoupper($review->period) }}</span>
                            <span class="text-gray-500 text-sm ml-1">{{ $review->year }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $review->review_date?->format('d M Y') ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($review->overall_score)
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 w-20">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $review->overall_score }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-700">{{ number_format($review->overall_score, 1) }}</span>
                            </div>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusClass = match($review->status) {
                                    'completed' => 'bg-green-100 text-green-700',
                                    'final' => 'bg-green-100 text-green-700',
                                    'draft' => 'bg-gray-100 text-gray-700',
                                    default => 'bg-yellow-100 text-yellow-700'
                                };
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full {{ $statusClass }}">
                                {{ ucfirst($review->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('staff.performances.show', $review->id) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <i class="fa-solid fa-clipboard-list text-3xl mb-2"></i>
                            <p>Belum ada data evaluasi kinerja</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reviews->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $reviews->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
