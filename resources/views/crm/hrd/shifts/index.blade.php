@extends('layouts.app')

@section('title', 'Shift Management')
@section('page-title', 'Shift Management')

@push('page-actions')
    <div class="flex gap-2">
        <a href="{{ route('administrasi.shifts.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fa-solid fa-plus mr-2"></i>Tambah Shift
        </a>
        <a href="{{ route('administrasi.shifts.calendar') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
            <i class="fa-solid fa-calendar mr-2"></i>Kalender
        </a>
    </div>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Shift Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($shifts as $shift)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all">
            <div class="p-4 border-b" style="border-left: 4px solid {{ $shift->color ?? '#3b82f6' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $shift->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $shift->code }}</p>
                    </div>
                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                        {{ $shift->working_hours }} jam
                    </span>
                </div>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-xs text-gray-500">Jam Masuk</p>
                        <p class="font-medium text-green-600">{{ $shift->start_time?->format('H:i') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Jam Pulang</p>
                        <p class="font-medium text-red-600">{{ $shift->end_time?->format('H:i') ?? '-' }}</p>
                    </div>
                </div>

                <div class="space-y-2 text-sm">
                    @if($shift->grace_period_minutes)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Grace Period</span>
                        <span class="text-gray-700">{{ $shift->grace_period_minutes }} menit</span>
                    </div>
                    @endif
                    @if($shift->late_tolerance_minutes)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Toleransi Terlambat</span>
                        <span class="text-gray-700">{{ $shift->late_tolerance_minutes }} menit</span>
                    </div>
                    @endif
                    @if($shift->overtime_start_time)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Jam Mulai Lembur</span>
                        <span class="text-gray-700">{{ $shift->overtime_start_time?->format('H:i') }}</span>
                    </div>
                    @endif
                    @if($shift->is_night_shift)
                    <div class="flex items-center gap-2 text-amber-600">
                        <i class="fa-solid fa-moon"></i>
                        <span class="text-sm">Shift Malam</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="px-4 py-3 bg-gray-50 flex justify-between items-center">
                <a href="{{ route('administrasi.shifts.edit', $shift->id) }}"
                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    <i class="fa-solid fa-pen mr-1"></i>Edit
                </a>
                <form action="{{ route('administrasi.shifts.destroy', $shift->id) }}" method="POST"
                      onsubmit="return confirm('Hapus shift ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                        <i class="fa-solid fa-trash mr-1"></i>Hapus
                    </button>
                </form>
            </div>
        </div>
        @empty
        @endforelse
    </div>

    {{-- Shift Schedule Quick Add --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-4">
            <i class="fa-solid fa-calendar-plus mr-2 text-blue-600"></i>
            Assign Shift Cepat
        </h3>

        <form action="{{ route('administrasi.shifts.assign') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan</label>
                <select name="employee_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Pilih Karyawan</option>
                    @foreach(\App\Models\HRD\EmployeeProfile::where('company_id', auth()->user()->company_id)->active()->with('user')->get() as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                <select name="shift_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Pilih Shift</option>
                    @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                <input type="date" name="date" required value="{{ today()->format('Y-m-d') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fa-solid fa-plus mr-2"></i>Assign
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
