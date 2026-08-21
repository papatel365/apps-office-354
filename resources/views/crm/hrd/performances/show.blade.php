@extends('layouts.app')

@section('title', 'Detail Evaluasi Kinerja')

@section('page-title', 'Detail Evaluasi Kinerja')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center gap-4 mb-6 pb-6 border-b">
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="text-blue-600 text-xl font-bold">{{ substr($review->employee?->full_name ?? 'N', 0, 1) }}</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $review->employee?->full_name ?? 'N/A' }}</h2>
                <p class="text-gray-500">{{ $review->employee?->position?->name ?? '-' }} - {{ $review->employee?->department?->name ?? '-' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ strtoupper($review->period) }}</p>
                <p class="text-sm text-gray-500">Periode</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $review->year }}</p>
                <p class="text-sm text-gray-500">Tahun</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $review->review_date?->format('d M Y') ?? '-' }}</p>
                <p class="text-sm text-gray-500">Tanggal Review</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                @php
                    $statusClass = match($review->status) {
                        'completed' => 'bg-green-100 text-green-700',
                        'draft' => 'bg-gray-100 text-gray-700',
                        default => 'bg-yellow-100 text-yellow-700'
                    };
                @endphp
                <span class="px-3 py-1 text-sm rounded-full {{ $statusClass }}">{{ ucfirst($review->status) }}</span>
            </div>
        </div>

        @if($review->overall_score)
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Skor Keseluruhan</h3>
            <div class="flex items-center gap-4">
                <div class="flex-1 bg-gray-200 rounded-full h-4">
                    <div class="bg-green-500 h-4 rounded-full" style="width: {{ $review->overall_score }}%"></div>
                </div>
                <span class="text-2xl font-bold text-green-600">{{ number_format($review->overall_score, 1) }}</span>
            </div>
        </div>
        @endif

        <div class="space-y-6">
            @if($review->achievements)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Pencapaian</h3>
                <p class="text-gray-700 bg-green-50 rounded-lg p-4">{{ $review->achievements }}</p>
            </div>
            @endif

            @if($review->improvements)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Peningkatan yang Dibutuhkan</h3>
                <p class="text-gray-700 bg-yellow-50 rounded-lg p-4">{{ $review->improvements }}</p>
            </div>
            @endif

            @if($review->goals_next_period)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Target Periode Berikutnya</h3>
                <p class="text-gray-700 bg-blue-50 rounded-lg p-4">{{ $review->goals_next_period }}</p>
            </div>
            @endif

            @if($review->recommendations)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Rekomendasi</h3>
                <p class="text-gray-700 bg-purple-50 rounded-lg p-4">{{ $review->recommendations }}</p>
            </div>
            @endif
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-6 border-t">
            <a href="{{ route('staff.performances.index') }}" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                Kembali
            </a>
            @if($review->status === 'draft')
            <form action="{{ route('staff.performances.submit', $review) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Submit Review
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
