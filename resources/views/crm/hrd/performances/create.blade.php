@extends('layouts.app')

@section('title', 'Buat Evaluasi Kinerja')

@section('page-title', 'Buat Evaluasi Kinerja Baru')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form action="{{ route('staff.performances.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Karyawan</label>
                    <select name="employee_id" class="w-full rounded-lg border-gray-300" required>
                        <option value="">Pilih Karyawan</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->full_name }} - {{ $employee->position?->name ?? '-' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Periode</label>
                        <select name="period" class="w-full rounded-lg border-gray-300" required>
                            <option value="q1">Q1</option>
                            <option value="q2">Q2</option>
                            <option value="q3">Q3</option>
                            <option value="q4">Q4</option>
                            <option value="semi_annual">Semester</option>
                            <option value="annual">Tahunan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                        <select name="year" class="w-full rounded-lg border-gray-300" required>
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                        <input type="date" name="period_start" class="w-full rounded-lg border-gray-300" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Selesai</label>
                        <input type="date" name="period_end" class="w-full rounded-lg border-gray-300" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Skor Keseluruhan (0-100)</label>
                    <input type="number" name="overall_score" min="0" max="100" step="0.1" class="w-full rounded-lg border-gray-300" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Achievements (Pencapaian)</label>
                    <textarea name="achievements" rows="3" class="w-full rounded-lg border-gray-300"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Improvements (Peningkatan)</label>
                    <textarea name="improvements" rows="3" class="w-full rounded-lg border-gray-300"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Goals Next Period (Target)</label>
                    <textarea name="goals_next_period" rows="3" class="w-full rounded-lg border-gray-300"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recommendations (Rekomendasi)</label>
                    <textarea name="recommendations" rows="3" class="w-full rounded-lg border-gray-300"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-6 border-t">
                <a href="{{ route('staff.performances.index') }}" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
