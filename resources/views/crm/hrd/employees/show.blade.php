{{-- resources/views/crm/staff/employees/show.blade.php --}}
@extends('layouts.app')

@section('title', $employee->full_name)

@php
    use App\Models\CrmModulePermission;
    use App\Services\Permission\UserPermissionService;

    $user = auth()->user();
    $permissionService = UserPermissionService::forUser($user);

    // Check if user can access HRD features via employees module
    $canViewAll = $permissionService->can('employees');
    $canEditEmployee = $permissionService->can('employees');
    // Payroll is HRD feature - check via employees module
    $canViewPayroll = $permissionService->can('employees');
@endphp

@section('page-title', $employee->full_name)
@section('page-subtitle', 'Profil Karyawan')

@push('page-actions')
    @if($canEditEmployee)
    <a href="{{ route('administrasi.data_karyawan.wizard.edit', $employee->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
        <i class="fa-solid fa-pen mr-2"></i>
        Edit Lengkap
    </a>
    @endif
@endpush

@section('content')
<div class="space-y-6" x-data="employeeProfileTabs()">
    {{-- Profile Header Card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
            {{-- Avatar --}}
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold flex-shrink-0 shadow-lg">
                {{ strtoupper(substr($employee->full_name, 0, 2)) }}
            </div>

            {{-- Basic Info --}}
            <div class="flex-1 text-center sm:text-left">
                <h2 class="text-2xl font-bold text-gray-900">{{ $employee->full_name }}</h2>
                <p class="text-gray-500">
                    {{ $employee->position?->name ?? 'Belum ada posisi' }}
                    <span class="mx-2">|</span>
                    {{ $employee->department?->name ?? 'Belum ada departemen' }}
                </p>
                <div class="flex flex-wrap justify-center sm:justify-start gap-2 mt-3">
                    @if($employee->is_active)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Aktif</span>
                    @else
                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">Resign</span>
                    @endif
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                        {{ $employee->employeeType?->name ?? '-' }}
                    </span>
                    <!-- @if($employee->contract_end && $employee->contract_days_remaining <= 30)
                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm">
                            Kontrak: {{ $employee->contract_days_remaining }} hari
                        </span>
                    @endif -->
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="mt-6 border-t pt-4">
            <div class="flex flex-wrap gap-1 overflow-x-auto pb-2">
                <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'bg-indigo-100 text-indigo-700 border-indigo-500' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                    class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors whitespace-nowrap">
                    <i class="fa-solid fa-home mr-1"></i> Ringkasan
                </button>
                <button @click="activeTab = 'personal'" :class="activeTab === 'personal' ? 'bg-indigo-100 text-indigo-700 border-indigo-500' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                    class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors whitespace-nowrap">
                    <i class="fa-solid fa-user mr-1"></i> Data Pribadi
                </button>
                <button @click="activeTab = 'employment'" :class="activeTab === 'employment' ? 'bg-indigo-100 text-indigo-700 border-indigo-500' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                    class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors whitespace-nowrap">
                    <i class="fa-solid fa-briefcase mr-1"></i> Pekerjaan
                </button>
                <button @click="activeTab = 'placement'" :class="activeTab === 'placement' ? 'bg-indigo-100 text-indigo-700 border-indigo-500' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                    class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors whitespace-nowrap">
                    <i class="fa-solid fa-map-marker-alt mr-1"></i> Penempatan
                </button>
                @if($canViewPayroll)
                <button @click="activeTab = 'payroll'" :class="activeTab === 'payroll' ? 'bg-indigo-100 text-indigo-700 border-indigo-500' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                    class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors whitespace-nowrap">
                    <i class="fa-solid fa-money-bills mr-1"></i> Gaji
                </button>
                @endif
                <button @click="activeTab = 'attendance'" :class="activeTab === 'attendance' ? 'bg-indigo-100 text-indigo-700 border-indigo-500' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                    class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors whitespace-nowrap">
                    <i class="fa-solid fa-calendar-check mr-1"></i> Absensi
                </button>
            </div>
        </div>
    </div>

    {{-- Tab Contents --}}
    <div class="min-h-[400px]">
        {{-- Overview Tab --}}
        <div x-show="activeTab === 'overview'" x-transition>
            @include('crm.hrd.employees.partials.tab-overview', [
                'canEditEmployee' => $canEditEmployee,
                'salaryDetails' => $salaryDetails ?? null,
                'attendanceStats' => $attendanceStats ?? null
            ])
        </div>

        {{-- Personal Tab --}}
        <div x-show="activeTab === 'personal'" x-transition>
            @include('crm.hrd.employees.partials.tab-personal')
        </div>

        {{-- Employment Tab --}}
        <div x-show="activeTab === 'employment'" x-transition>
            @include('crm.hrd.employees.partials.tab-employment')
        </div>

        {{-- Placement Tab --}}
        <div x-show="activeTab === 'placement'" x-transition>
            @include('crm.hrd.employees.partials.tab-placement')
        </div>

        {{-- Payroll Tab --}}
        @if($canViewPayroll)
        <div x-show="activeTab === 'payroll'" x-transition>
            @include('crm.hrd.employees.partials.tab-payroll', ['salaryDetails' => $salaryDetails ?? null])
        </div>
        @endif

        {{-- Attendance Tab --}}
        <div x-show="activeTab === 'attendance'" x-transition>
            @include('crm.hrd.employees.partials.tab-attendance', ['attendanceStats' => $attendanceStats ?? null])
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function employeeProfileTabs() {
    return {
        activeTab: 'overview',
    }
}

// Resign Modal Functions
function openResignModal() {
    document.getElementById('resignModal').classList.remove('hidden');
    document.getElementById('resignModal').classList.add('flex');
}

function closeResignModal() {
    document.getElementById('resignModal').classList.add('hidden');
    document.getElementById('resignModal').classList.remove('flex');
    document.getElementById('resignReason').value = '';
}

// Handle Resign Submit
document.getElementById('resignForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const reason = document.getElementById('resignReason').value;
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Memproses...';

    try {
        const response = await fetch(`/administrasi/data-karyawan/{{ $employee->id }}/resign`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ reason: reason })
        });

        const data = await response.json();

        if (data.success) {
            closeResignModal();
            showToast(data.message || 'Status berhasil diubah ke Resign', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(extractErrorMessage(data, 'Gagal mengubah status'), 'error');
        }
    } catch (error) {
        console.error('Resign error:', error);
        showToast('Terjadi kesalahan saat memproses', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgClass = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    const iconClass = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';

    toast.className = `fixed bottom-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 ${bgClass} text-white`;
    toast.innerHTML = `<i class="fa-solid ${iconClass} text-lg flex-shrink-0"></i><span>${escapeHtml(message)}</span>`;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush

{{-- Resign Confirmation Modal --}}
<div id="resignModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-user-minus text-white text-lg"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Ubah Status ke Resign</h3>
                </div>
                <button type="button" onclick="closeResignModal()" class="text-white/80 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
        </div>

        {{-- Content --}}
        <form id="resignForm">
            <div class="p-6">
                <p class="text-gray-600 mb-4">
                    Anda akan mengubah status <strong>{{ $employee->full_name }}</strong> menjadi <span class="text-red-600 font-medium">Resign</span>.
                </p>

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                        <div class="text-sm text-amber-800">
                            <p><strong>Peringatan:</strong></p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>Karyawan tidak akan muncul di absensi aktif</li>
                                <li>Akses login karyawan akan dinonaktifkan</li>
                                <li>Data karyawan tetap tersimpan</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="resignReason" class="block text-sm font-medium text-gray-700 mb-1">
                        Alasan Resign <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reason" id="resignReason" rows="3" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        placeholder="Masukkan alasan resign..."></textarea>
                </div>
            </div>

            {{-- Actions --}}
            <div class="px-6 py-4 bg-gray-50 border-t flex gap-3 justify-end">
                <button type="button" onclick="closeResignModal()"
                    class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-medium">
                    Batal
                </button>
                <button type="submit"
                    class="px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium flex items-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    Ya, Ubah ke Resign
                </button>
            </div>
        </form>
    </div>
</div>
