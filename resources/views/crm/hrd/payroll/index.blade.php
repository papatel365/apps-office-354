@extends('layouts.app')

@section('title', 'Payroll')
@section('page-title', 'Payroll Management')

@push('page-actions')
    <div class="flex gap-2">
        <form action="{{ route('administrasi.payroll.generate') }}" method="POST" class="inline">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    onclick="return confirm('Generate payroll untuk {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}?')">
                <i class="fa-solid fa-calculator mr-2"></i>Generate Payroll
            </button>
        </form>
    </div>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Period Selector --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                <select name="month" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg">
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                    </option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="year" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg">
                    @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
        </form>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
            <p class="text-sm text-gray-500">Total</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600">{{ $stats['pending'] }}</p>
            <p class="text-sm text-gray-500">Draft</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['approved'] }}</p>
            <p class="text-sm text-gray-500">Approved</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['paid'] }}</p>
            <p class="text-sm text-gray-500">Paid</p>
        </div>
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-4 text-white text-center col-span-2">
            <p class="text-sm text-indigo-100">Total Gaji Bersih</p>
            <p class="text-xl font-bold mt-1">Rp {{ number_format($stats['total_net'] / 1000000, 1) }}M</p>
        </div>
    </div>

    {{-- Payroll Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gaji Pokok</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tunjangan</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lembur</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Potongan</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($salaries as $salary)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="salary_ids[]" value="{{ $salary->id }}"
                                   class="salary-checkbox" {{ $salary->status !== 'pending' ? 'disabled' : '' }}>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-600">
                                    {{ strtoupper(substr($salary->employee?->full_name ?? 'N', 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $salary->employee?->full_name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500">{{ $salary->employee?->position?->name ?? '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            Rp {{ number_format($salary->basic_salary, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-green-600">
                            + Rp {{ number_format($salary->allowances, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-purple-600">
                            + Rp {{ number_format($salary->overtime_pay, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-red-600">
                            - Rp {{ number_format($salary->deductions, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900">
                            Rp {{ number_format($salary->total_salary, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @switch($salary->status)
                            @case('draft')
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">Draft</span>
                            @break
                            @case('pending')
                            <span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded-full">Pending</span>
                            @break
                            @case('approved')
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">Approved</span>
                            @break
                            @case('paid')
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Paid</span>
                            @break
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('administrasi.payroll.export', $salary->id) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Slip Gaji">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </a>
                                @if($salary->status === 'pending')
                                <form action="{{ route('administrasi.payroll.approve', $salary->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Approve">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                                @elseif($salary->status === 'approved')
                                <form action="{{ route('administrasi.payroll.paid', $salary->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Mark as Paid">
                                        <i class="fa-solid fa-money-bill"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-money-bills text-5xl mb-4"></i>
                            <p class="text-lg">Belum ada data payroll</p>
                            <p class="text-sm">Klik "Generate Payroll" untuk membuat</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.salary-checkbox:not([disabled])');
    const selectAll = document.getElementById('selectAll');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}
</script>
@endsection
