@extends('layouts.app')

@section('title', 'Data Karyawan')
@section('page-title', 'Data Karyawan')

@push('page-actions')
    <div class="flex gap-2">
        <a href="{{ route('administrasi.data_karyawan.wizard.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fa-solid fa-user-plus mr-2"></i>Tambah Karyawan
        </a>
    </div>
@endpush

@section('content')
<div class="space-y-6">
   {{-- Stats Cards --}}
    @php
        $employeeCards = [
            [
                'title' => 'Total Karyawan',
                'value' => $stats['total'],
                'icon'  => 'fa-users',
                'bg'    => 'from-blue-500 to-blue-600',
                'text'  => 'text-blue-100',
            ],
            [
                'title' => 'Karyawan Tetap',
                'value' => $stats['permanent'] ?? 0,
                'icon'  => 'fa-user-check',
                'bg'    => 'from-emerald-500 to-green-600',
                'text'  => 'text-green-100',
            ],
            [
                'title' => 'Karyawan Kontrak',
                'value' => $stats['contract'] ?? 0,
                'icon'  => 'fa-file-contract',
                'bg'    => 'from-amber-500 to-orange-600',
                'text'  => 'text-amber-100',
            ],
            [
                'title' => 'Karyawan Percobaan',
                'value' => $stats['probation'] ?? 0,
                'icon'  => 'fa-hourglass-half',
                'bg'    => 'from-purple-500 to-fuchsia-600',
                'text'  => 'text-purple-100',
            ],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($employeeCards as $card)
            <div
                class="bg-gradient-to-br {{ $card['bg'] }} rounded-xl p-5 text-white shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">

                <div class="flex items-center justify-between">
                    <div>
                        <p class="{{ $card['text'] }} text-sm">
                            {{ $card['title'] }}
                        </p>

                        <p class="mt-1 text-2xl font-bold">
                            {{ number_format($card['value']) }}
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20">
                        <i class="fa-solid {{ $card['icon'] }} text-2xl"></i>
                    </div>
                </div>

            </div>
        @endforeach
    </div>
    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Nama, NIK, atau email..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="resigned" {{ $status === 'resigned' ? 'selected' : '' }}>Resign</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                <select name="department" id="filterDepartment" onchange="onDepartmentFilterChange()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="all">Semua</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $department == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Divisi</label>
                <select name="division" id="filterDivision" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="all">Semua</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Filter</label>
                <select name="filter" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="expiring" {{ $filter === 'expiring' ? 'selected' : '' }}>Kontrak Akan Habis</option>
                    <option value="new" {{ $filter === 'new' ? 'selected' : '' }}>Baru Bergabung</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <i class="fa-solid fa-filter mr-2"></i>Filter
            </button>
        </form>
    </div>

    
    {{-- Employee Status Chart --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fa-solid fa-chart-pie mr-2 text-blue-500"></i>
                Statistik Status Karyawan
            </h3>
        </div>

        <div class="flex flex-col items-center">
            {{-- Doughnut Chart Container -足够高 untuk 4 legend items --}}
            <div class="w-full max-w-xs sm:max-w-sm md:max-w-md">
                <div class="relative" style="min-height: 340px;">
                    <canvas id="employeeStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>


    {{-- Employee Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIK</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posisi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departemen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Divisi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                        <!-- <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Kontrak</th> -->
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($employees as $emp)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-600">
                                    {{ strtoupper(substr($emp->full_name, 0, 2)) }}
                                </div>
                                <div>
                                    <a href="{{ route('administrasi.data_karyawan.show', $emp->id) }}"
                                       class="font-medium text-gray-900 hover:text-blue-600">
                                        {{ $emp->full_name }}
                                    </a>
                                    <p class="text-xs text-gray-500">{{ $emp->user->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $emp->nik ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $emp->position->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
                                {{ $emp->department->name ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($emp->division)
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">
                                    {{ $emp->division->name }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if(!$emp->is_active)
                                {{-- Jika resign, tampilkan badge Resign dengan prioritas --}}
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">
                                    Resign
                                </span>
                            @elseif($emp->employeeType)
                                <span class="px-2 py-1 text-xs rounded-full" style="background-color: {{ $emp->employeeType->color ?? '#6B7280' }}20; color: {{ $emp->employeeType->color ?? '#6B7280' }}">
                                    {{ $emp->employeeType->name }}
                                </span>
                            @elseif($emp->employment_type)
                                <span class="px-2 py-1 {{ $emp->employment_type === 'permanent' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }} text-xs rounded-full">
                                    {{ ucfirst($emp->employment_type) }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <!-- <td class="px-6 py-4 text-center">
                            @if($emp->contract_end)
                                @if($emp->contract_days_remaining <= 30 && $emp->contract_days_remaining > 0)
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">
                                    {{ $emp->contract_days_remaining }} hari
                                </span>
                                @elseif($emp->contract_days_remaining <= 0)
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                                    Habis
                                </span>
                                @else
                                <span class="text-xs text-gray-500">
                                    {{ $emp->contract_end->format('d M Y') }}
                                </span>
                                @endif
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td> -->
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('administrasi.data_karyawan.show', $emp->id) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('administrasi.data_karyawan.edit', $emp->id) }}"
                                   class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <button type="button"
                                        onclick="showDeleteModal({{ $emp->id }})"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-users text-5xl mb-4"></i>
                            <p class="text-lg">Belum ada data karyawan</p>
                            <a href="{{ route('administrasi.data_karyawan.wizard.create') }}"
                               class="mt-4 inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fa-solid fa-plus mr-2"></i>Tambah Karyawan
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $employees->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-trash text-white text-lg"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Hapus Karyawan</h3>
                </div>
                <button type="button" onclick="closeDeleteModal()" class="text-white/80 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
        </div>

        {{-- Content --}}
        <div class="p-6">
            <p class="text-gray-600 mb-4">Apakah Anda yakin ingin menghapus karyawan ini?</p>

            {{-- Employee Info Card --}}
            <div id="employeeInfo" class="bg-gray-50 rounded-xl p-4 mb-4 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Nama</span>
                    <span id="empName" class="font-medium text-gray-900">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Email</span>
                    <span id="empEmail" class="font-medium text-gray-900">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">NIK</span>
                    <span id="empNik" class="font-medium text-gray-900">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Posisi</span>
                    <span id="empPosition" class="font-medium text-gray-900">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Departemen</span>
                    <span id="empDepartment" class="font-medium text-gray-900">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Status</span>
                    <span id="empStatus" class="font-medium">-</span>
                </div>
            </div>

            {{-- Warning --}}
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                    <p class="text-sm text-amber-800">
                        <strong>Peringatan:</strong> Data yang sudah dihapus tidak dapat digunakan kembali.
                    </p>
                </div>
            </div>

            {{-- Confirmation Checkbox --}}
            <div class="mb-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" id="deleteConfirmCheckbox" class="w-5 h-5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="text-gray-700">Saya memahami tindakan ini.</span>
                </label>
            </div>

            {{-- Error Message --}}
            <div id="deleteError" class="hidden bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                    <span id="deleteErrorText" class="text-sm text-red-700"></span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeDeleteModal()"
                        class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-medium">
                    Batal
                </button>
                <button type="button" id="deleteBtn" onclick="confirmDelete()"
                        disabled
                        class="px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <span id="deleteBtnText">Hapus Karyawan</span>
                    <span id="deleteBtnSpinner" class="hidden">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Second Confirmation Modal for Permanent Delete (Developer Only) --}}
<div id="permanentDeleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[60]" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-skull text-white text-lg"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Hapus Permanen</h3>
                </div>
                <button type="button" onclick="closePermanentDeleteModal()" class="text-white/80 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
        </div>

        <div class="p-6">
            <p class="text-gray-600 mb-4">Tindakan ini tidak dapat dibatalkan. Ketik <strong>HAPUS</strong> untuk melanjutkan.</p>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ketik HAPUS untuk konfirmasi</label>
                <input type="text" id="permanentDeleteConfirmText"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500"
                       placeholder="Ketik HAPUS">
            </div>

            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closePermanentDeleteModal()"
                        class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-medium">
                    Batal
                </button>
                <button type="button" id="permanentDeleteBtn" onclick="confirmPermanentDelete()"
                        disabled
                        class="px-5 py-2.5 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <span>Hapus Permanen</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentDeleteEmployeeId = null;
let isDeleting = false;

// Click outside to close modal
document.addEventListener('click', function(event) {
    const deleteModal = document.getElementById('deleteModal');
    const permanentModal = document.getElementById('permanentDeleteModal');

    if (deleteModal && deleteModal.style.display === 'flex' && event.target === deleteModal) {
        closeDeleteModal();
    }
    if (permanentModal && permanentModal.style.display === 'flex' && event.target === permanentModal) {
        closePermanentDeleteModal();
    }
});

// ESC key to close modal
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
        closePermanentDeleteModal();
    }
});

function showDeleteModal(employeeId) {
    currentDeleteEmployeeId = employeeId;

    // Reset state
    document.getElementById('deleteConfirmCheckbox').checked = false;
    document.getElementById('deleteBtn').disabled = true;
    document.getElementById('deleteError').classList.add('hidden');
    document.getElementById('empName').textContent = '-';
    document.getElementById('empEmail').textContent = '-';
    document.getElementById('empNik').textContent = '-';
    document.getElementById('empPosition').textContent = '-';
    document.getElementById('empDepartment').textContent = '-';
    document.getElementById('empStatus').textContent = '-';

    // Show modal
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'flex';

    // Fetch employee data
    fetch(`/administrasi/data-karyawan/${employeeId}/delete-data`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const emp = data.data;
                document.getElementById('empName').textContent = emp.name;
                document.getElementById('empEmail').textContent = emp.email;
                document.getElementById('empNik').textContent = emp.nik;
                document.getElementById('empPosition').textContent = emp.position;
                document.getElementById('empDepartment').textContent = emp.department;
                const statusEl = document.getElementById('empStatus');
                statusEl.textContent = emp.status;
                statusEl.className = emp.status === 'Aktif'
                    ? 'font-medium text-green-600'
                    : 'font-medium text-gray-600';
            } else {
                closeDeleteModal();
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: data.message || 'Terjadi kesalahan.'
                });
            }
        })
        .catch(error => {
            closeDeleteModal();
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Tidak dapat memuat data karyawan.'
            });
        });
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
    currentDeleteEmployeeId = null;
}

function confirmDelete() {
    if (!currentDeleteEmployeeId || isDeleting) return;

    const btn = document.getElementById('deleteBtn');
    const spinner = document.getElementById('deleteBtnSpinner');
    const btnText = document.getElementById('deleteBtnText');
    const errorDiv = document.getElementById('deleteError');
    const errorText = document.getElementById('deleteErrorText');

    isDeleting = true;
    btn.disabled = true;
    spinner.classList.remove('hidden');
    btnText.textContent = 'Menghapus...';
    errorDiv.classList.add('hidden');

    fetch(`/administrasi/data-karyawan/${currentDeleteEmployeeId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        isDeleting = false;
        spinner.classList.add('hidden');
        btnText.textContent = 'Hapus Karyawan';

        if (data.success) {
            closeDeleteModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                // Refresh the page to update the table
                location.reload();
            });
        } else {
            btn.disabled = false;
            errorDiv.classList.remove('hidden');
            errorText.textContent = extractErrorMessage(data, 'Gagal menghapus karyawan.');
        }
    })
    .catch(error => {
        isDeleting = false;
        spinner.classList.add('hidden');
        btnText.textContent = 'Hapus Karyawan';
        btn.disabled = false;
        errorDiv.classList.remove('hidden');
        errorText.textContent = 'Gagal menghapus karyawan. Silakan coba lagi.';
    });
}

// Checkbox toggle for delete confirmation
document.getElementById('deleteConfirmCheckbox').addEventListener('change', function() {
    document.getElementById('deleteBtn').disabled = !this.checked;
});

// Permanent delete modal functions
let currentPermanentDeleteId = null;

function showPermanentDeleteModal(employeeId) {
    currentPermanentDeleteId = employeeId;
    document.getElementById('permanentDeleteConfirmText').value = '';
    document.getElementById('permanentDeleteBtn').disabled = true;

    const modal = document.getElementById('permanentDeleteModal');
    modal.style.display = 'flex';
}

function closePermanentDeleteModal() {
    const modal = document.getElementById('permanentDeleteModal');
    modal.style.display = 'none';
    currentPermanentDeleteId = null;
}

document.getElementById('permanentDeleteConfirmText').addEventListener('input', function() {
    document.getElementById('permanentDeleteBtn').disabled = this.value !== 'HAPUS';
});

function confirmPermanentDelete() {
    if (!currentPermanentDeleteId) return;

    fetch(`/administrasi/data-karyawan/${currentPermanentDeleteId}/force-delete`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closePermanentDeleteModal();
            closeDeleteModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: data.message
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: 'Gagal menghapus karyawan secara permanen.'
        });
    });
}

function exportEmployees() {
    window.location.href = '{{ route('administrasi.data_karyawan.export') }}';
}

// Division filter data
const divisions = @json($divisions);
const currentDivision = '{{ $division ?? 'all' }}';
const currentDepartment = '{{ $department ?? 'all' }}';

// Initialize flag to prevent auto-submit on page load
let isInitialLoad = true;

function onDepartmentFilterChange() {
    const departmentId = document.getElementById('filterDepartment').value;
    const divisionSelect = document.getElementById('filterDivision');

    // Clear current options
    divisionSelect.innerHTML = '<option value="all">Semua</option>';

    if (!departmentId || departmentId === 'all') {
        // Show all divisions
        divisions.forEach(function(div) {
            const option = document.createElement('option');
            option.value = div.id;
            option.textContent = div.name;
            if (div.id == currentDivision) {
                option.selected = true;
            }
            divisionSelect.appendChild(option);
        });
    } else {
        // Filter by department
        const filteredDivisions = divisions.filter(function(div) {
            return !div.department_id || div.department_id == departmentId;
        });
        filteredDivisions.forEach(function(div) {
            const option = document.createElement('option');
            option.value = div.id;
            option.textContent = div.name;
            if (div.id == currentDivision) {
                option.selected = true;
            }
            divisionSelect.appendChild(option);
        });
    }

    // Only submit form if user manually changed the department (not on initial page load)
    if (!isInitialLoad) {
        divisionSelect.form.submit();
    }
    isInitialLoad = false;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize division filter based on current department
    onDepartmentFilterChange();

    // Initialize Employee Status Chart
    initEmployeeStatusChart();

    // Signal page loader when chart and all components are ready
    if (window.PageLoader) {
        // Small delay to ensure chart canvas is rendered
        requestAnimationFrame(function() {
            setTimeout(function() {
                PageLoader.done();
            }, 100);
        });
    }
});

// Employee Status Doughnut Chart - using database employee types
function initEmployeeStatusChart() {
    const ctx = document.getElementById('employeeStatusChart');
    if (!ctx) return;

    // Get data from PHP
    const allEmployeeTypes = @json($employeeSummary['employeeTypes'] ?? []);
    const byTypeCounts = @json($employeeSummary['by_type'] ?? []);
    const resignedCount = {{ $employeeSummary['resigned'] ?? 0 }};

    // Get individual fallback counts (for employees not yet migrated)
    const permanentCount = {{ $stats['permanent'] ?? 0 }};
    const contractCount = {{ $stats['contract'] ?? 0 }};
    const probationCount = {{ $stats['probation'] ?? 0 }};

    // Build labels and data arrays
    const labels = [];
    const datasetData = [];
    const colors = [];

    // Process each employee type from database (active employees only)
    allEmployeeTypes.forEach(t => {
        labels.push(t.name);

        // Get count from by_type (keys are uppercase like "TETAP")
        // Also check lowercase as fallback
        let count = byTypeCounts[t.code] || byTypeCounts[t.code?.toUpperCase()] || byTypeCounts[t.code?.toLowerCase()] || 0;

        // If no data from by_type, try fallback counts
        if (count === 0) {
            const codeLower = t.code?.toLowerCase();
            if (codeLower === 'tetap' || codeLower === 'tetap') count = permanentCount;
            else if (codeLower === 'kontrak' || codeLower === 'contract') count = contractCount;
            else if (codeLower === 'percobaan' || codeLower === 'probation') count = probationCount;
        }

        datasetData.push(count);

        // Convert hex color to rgba
        const hex = t.color || '#6B7280';
        colors.push(hexToRgba(hex, 0.8));
    });

    // Add Resign as a separate category (always add to labels, show 0 if none)
    labels.push('Resign');
    datasetData.push(resignedCount);
    colors.push(hexToRgba('#EF4444', 0.8)); // Red color for resign - same as card

    // Debug output
    console.log('[CHART] Debug Info:', {
        allEmployeeTypes: allEmployeeTypes,
        byTypeCounts: byTypeCounts,
        permanentCount: permanentCount,
        contractCount: contractCount,
        probationCount: probationCount,
        resignedCount: resignedCount,
        labels: labels,
        datasetData: datasetData
    });

    // Destroy existing chart if any
    if (window.employeeStatusChartInstance) {
        window.employeeStatusChartInstance.destroy();
    }

    // Create new chart
    window.employeeStatusChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: datasetData,
                backgroundColor: colors,
                borderWidth: 0,
                // Ensure all data points have hover state
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    fullSize: true,
                    labels: {
                        padding: 16,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            size: 12,
                            family: 'Inter, system-ui, sans-serif'
                        },
                        color: '#374151',
                        // Generate legend with proper labels
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map(function(label, i) {
                                    const meta = chart.getDatasetMeta(0);
                                    const style = meta.controller.getStyle(i);
                                    const value = data.datasets[0].data[i];

                                    // Hide items with 0 value
                                    if (value === 0) {
                                        return {
                                            text: label + ' : ' + value,
                                            fillStyle: style.backgroundColor,
                                            strokeStyle: style.borderColor,
                                            lineWidth: style.borderWidth,
                                            hidden: false,
                                            index: i
                                        };
                                    }

                                    return {
                                        text: label + ' : ' + value,
                                        fillStyle: style.backgroundColor,
                                        strokeStyle: style.borderColor,
                                        lineWidth: style.borderWidth,
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            return label + ' : ' + value;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
}

// Helper function to convert hex to rgba
function hexToRgba(hex, alpha) {
    hex = hex.replace('#', '');
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    return `rgba(${r},${g},${b},${alpha})`;
}
</script>

@endsection
