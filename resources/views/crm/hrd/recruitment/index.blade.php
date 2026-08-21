@extends('layouts.app')

@section('title', 'Recruitment')
@section('page-title', 'Recruitment Pipeline')

@push('page-actions')
    <div class="flex gap-2">
        <a href="{{ route('administrasi.recruitment.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fa-solid fa-user-plus mr-2"></i>Tambah Kandidat
        </a>
    </div>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
            <p class="text-sm text-gray-500">Total Kandidat</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['active'] }}</p>
            <p class="text-sm text-gray-500">Active Pipeline</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['interviews_today'] }}</p>
            <p class="text-sm text-gray-500">Interview Hari Ini</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-purple-600">{{ $stats['interviews_week'] }}</p>
            <p class="text-sm text-gray-500">Interview Minggu Ini</p>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div class="overflow-x-auto pb-4">
        <div class="flex gap-4 min-w-max">
            @foreach([
                'applied' => ['label' => 'Applied', 'color' => 'gray'],
                'screening' => ['label' => 'Screening', 'color' => 'blue'],
                'interview_hr' => ['label' => 'Interview HR', 'color' => 'purple'],
                'interview_user' => ['label' => 'Interview User', 'color' => 'indigo'],
                'offering' => ['label' => 'Offering', 'color' => 'amber'],
                'hiring' => ['label' => 'Hiring', 'color' => 'green'],
            ] as $stage => $config)
            <div class="w-80 flex-shrink-0">
                <div class="bg-gray-100 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-{{ $config['color'] }}-700">
                            <i class="fa-solid fa-circle mr-2 text-{{ $config['color'] }}-400"></i>
                            {{ $config['label'] }}
                        </h3>
                        <span class="px-2 py-1 bg-{{ $config['color'] }}-100 text-{{ $config['color'] }}-700 text-xs rounded-full font-medium">
                            {{ $stages[$stage]->count() }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        @forelse($stages[$stage] as $candidate)
                        <div class="bg-white rounded-lg border border-gray-200 p-4 cursor-pointer hover:shadow-md transition-all candidate-card"
                             onclick="showCandidateDetail({{ $candidate->id }})"
                             data-candidate-id="{{ $candidate->id }}">
                            <div class="flex items-start justify-between mb-2">
                                <div class="w-10 h-10 rounded-full bg-{{ $config['color'] }}-100 flex items-center justify-center text-sm font-bold text-{{ $config['color'] }}-600">
                                    {{ strtoupper(substr($candidate->applicant_name, 0, 2)) }}
                                </div>
                                <span class="text-xs text-gray-400">{{ $candidate->created_at->diffForHumans() }}</span>
                            </div>

                            <h4 class="font-medium text-gray-900">{{ $candidate->applicant_name }}</h4>
                            <p class="text-sm text-gray-500">{{ $candidate->position?->name ?? 'Posisi belum ditentukan' }}</p>

                            <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
                                <span><i class="fa-solid fa-envelope mr-1"></i>{{ Str::limit($candidate->email, 15) }}</span>
                            </div>

                            @if($candidate->interview_date && $candidate->interview_date->isToday())
                            <div class="mt-3 p-2 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-xs text-red-600 font-medium">
                                    <i class="fa-solid fa-calendar-check mr-1"></i>
                                    Interview Hari Ini {{ $candidate->interview_date->format('H:i') }}
                                </p>
                            </div>
                            @endif

                            @if($candidate->interview_score)
                            <div class="mt-3 flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $candidate->interview_score }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-green-600">{{ $candidate->interview_score }}%</span>
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="text-center py-8 text-gray-400">
                            <i class="fa-solid fa-inbox text-3xl mb-2"></i>
                            <p class="text-sm">Tidak ada kandidat</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Rejected Column --}}
            <div class="w-80 flex-shrink-0">
                <div class="bg-red-50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-red-700">
                            <i class="fa-solid fa-circle mr-2 text-red-400"></i>
                            Rejected
                        </h3>
                        <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">
                            {{ $stages['rejected']->count() }}
                        </span>
                    </div>

                    <div class="space-y-3 max-h-[500px] overflow-y-auto">
                        @forelse($stages['rejected'] as $candidate)
                        <div class="bg-white rounded-lg border border-red-200 p-4 opacity-75">
                            <div class="flex items-start justify-between mb-2">
                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-sm font-bold text-red-600">
                                    {{ strtoupper(substr($candidate->applicant_name, 0, 2)) }}
                                </div>
                            </div>

                            <h4 class="font-medium text-gray-900">{{ $candidate->applicant_name }}</h4>
                            <p class="text-sm text-gray-500">{{ $candidate->position?->name ?? '-' }}</p>

                            @if($candidate->rejected_reason)
                            <p class="text-xs text-red-600 mt-2 truncate">
                                <i class="fa-solid fa-times mr-1"></i>{{ $candidate->rejected_reason }}
                            </p>
                            @endif
                        </div>
                        @empty
                        <div class="text-center py-8 text-red-300">
                            <i class="fa-solid fa-check text-3xl mb-2"></i>
                            <p class="text-sm">Tidak ada yang ditolak</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('administrasi.recruitment.index', ['filter' => 'today']) }}"
           class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-all flex items-center gap-3">
            <div class="p-3 bg-red-100 rounded-lg">
                <i class="fa-solid fa-calendar-check text-red-600"></i>
            </div>
            <div>
                <p class="font-medium text-gray-800">Interview Hari Ini</p>
                <p class="text-sm text-gray-500">{{ $stats['interviews_today'] }} jadwal</p>
            </div>
        </a>

        <a href="{{ route('administrasi.recruitment.index', ['stage' => 'interview_hr']) }}"
           class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-all flex items-center gap-3">
            <div class="p-3 bg-purple-100 rounded-lg">
                <i class="fa-solid fa-user-tie text-purple-600"></i>
            </div>
            <div>
                <p class="font-medium text-gray-800">Interview HR</p>
                <p class="text-sm text-gray-500">{{ $stages['interview_hr']->count() }} kandidat</p>
            </div>
        </a>

        <a href="{{ route('administrasi.recruitment.index', ['stage' => 'offering']) }}"
           class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-all flex items-center gap-3">
            <div class="p-3 bg-amber-100 rounded-lg">
                <i class="fa-solid fa-hand-holding-dollar text-amber-600"></i>
            </div>
            <div>
                <p class="font-medium text-gray-800">Offering</p>
                <p class="text-sm text-gray-500">{{ $stages['offering']->count() }} menunggu</p>
            </div>
        </a>

        <a href="{{ route('administrasi.recruitment.index', ['stage' => 'hiring']) }}"
           class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-all flex items-center gap-3">
            <div class="p-3 bg-green-100 rounded-lg">
                <i class="fa-solid fa-check-circle text-green-600"></i>
            </div>
            <div>
                <p class="font-medium text-gray-800">Berhasil Hire</p>
                <p class="text-sm text-gray-500">{{ $stages['hiring']->count() }} kandidat</p>
            </div>
        </a>
    </div>
</div>

{{-- Candidate Detail Modal --}}
<div id="candidateModal" class="fixed inset-0 bg-black/50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-6" id="candidateDetailContent">
                {{-- Content loaded via AJAX --}}
            </div>
        </div>
    </div>
</div>

<script>
function showCandidateDetail(candidateId) {
    // In production, load via AJAX
    // For demo, show placeholder
    document.getElementById('candidateModal').classList.remove('hidden');
}

// Close modal
document.getElementById('candidateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

// Drag and drop functionality would be added here
</script>
@endsection
