{{-- resources/views/crm/staff/data-karyawan/trashed.blade.php --}}
@extends('layouts.app')

@section('title', 'Karyawan Dihapus')
@section('page-title', 'Karyawan Dihapus')



@section('content')
<div class="space-y-6">
    {{-- Info Banner --}}
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-5">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-trash-can text-amber-600 text-xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-amber-900">Karyawan yang Dihapus</h3>
                <p class="text-amber-700 mt-1">
                    Karyawan yang dihapus akan muncul di sini. Anda dapat mengembalikan karyawan atau menghapusnya secara permanen.
                </p>
                <div class="flex gap-4 mt-3">
                    <div class="flex items-center gap-2 text-sm text-amber-800">
                        <i class="fa-solid fa-rotate-left text-green-600"></i>
                        Restore: Pemilik, Superadmin, Developer
                    </div>
                    <div class="flex items-center gap-2 text-sm text-amber-800">
                        <i class="fa-solid fa-skull text-gray-600"></i>
                        Hapus Permanen: Developer Only
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Nama, Email, atau NIK..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <i class="fa-solid fa-search mr-2"></i>Filter
            </button>
            <a href="{{ route('administrasi.data_karyawan.trashed') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fa-solid fa-rotate-left"></i>
            </a>
        </form>
    </div>

    {{-- Deleted Employees Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIK</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posisi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departemen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Hapus</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($employees as $emp)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-bold text-gray-500">
                                    {{ strtoupper(substr($emp->full_name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $emp->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $emp->user->email ?? '-' }}</p>
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
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $emp->deleted_at ? $emp->deleted_at->format('d M Y, H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Restore Button (for Owner, Developer, Superadmin) --}}
                                <button type="button"
                                        onclick="restoreEmployee({{ $emp->id }})"
                                        class="p-2 text-green-600 hover:bg-green-50 rounded-lg"
                                        title="Kembalikan">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>

                                {{-- Permanent Delete Button (Developer Only) --}}
                                @if(auth()->user()->is_developer)
                                <button type="button"
                                        onclick="showPermanentDeleteModal({{ $emp->id }}, '{{ $emp->full_name }}')"
                                        class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                                        title="Hapus Permanen">
                                    <i class="fa-solid fa-skull"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-trash-can text-5xl mb-4"></i>
                            <p class="text-lg">Belum ada karyawan yang dihapus</p>
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

{{-- Second Confirmation Modal for Permanent Delete --}}
<div id="permanentDeleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-gray-700 to-gray-900 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-skull text-white text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Hapus Permanen</h3>
                        <p id="permanentDeleteName" class="text-sm text-gray-300"></p>
                    </div>
                </div>
                <button type="button" onclick="closePermanentDeleteModal()" class="text-white/80 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
        </div>

        {{-- Content --}}
        <div class="p-6">
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-600 mt-0.5"></i>
                    <div>
                        <p class="font-medium text-red-900">Tindakan ini tidak dapat dibatalkan!</p>
                        <p class="text-sm text-red-700 mt-1">
                            Semua data karyawan akan dihapus secara permanen dari sistem.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ketik <strong>HAPUS</strong> untuk konfirmasi</label>
                <input type="text" id="permanentDeleteConfirmText"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                       placeholder="Ketik HAPUS"
                       oninput="togglePermanentDeleteBtn()">
            </div>

            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closePermanentDeleteModal()"
                        class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-medium">
                    Batal
                </button>
                <button type="button" id="permanentDeleteBtn" onclick="confirmPermanentDelete()"
                        disabled
                        class="px-5 py-2.5 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <span id="permanentDeleteBtnText">Hapus Permanen</span>
                    <span id="permanentDeleteSpinner" class="hidden">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentPermanentDeleteId = null;

function restoreEmployee(employeeId) {
    Swal.fire({
        title: 'Kembalikan Karyawan?',
        text: 'Karyawan akan dikembalikan dan statusnya设置为 nonaktif.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Kembalikan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/administrasi/data-karyawan/${employeeId}/restore`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
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
                    text: 'Terjadi kesalahan. Silakan coba lagi.'
                });
            });
        }
    });
}

function showPermanentDeleteModal(employeeId, employeeName) {
    currentPermanentDeleteId = employeeId;
    document.getElementById('permanentDeleteName').textContent = employeeName;
    document.getElementById('permanentDeleteConfirmText').value = '';
    document.getElementById('permanentDeleteBtn').disabled = true;

    const modal = document.getElementById('permanentDeleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closePermanentDeleteModal() {
    const modal = document.getElementById('permanentDeleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    currentPermanentDeleteId = null;
}

function togglePermanentDeleteBtn() {
    const input = document.getElementById('permanentDeleteConfirmText');
    const btn = document.getElementById('permanentDeleteBtn');
    btn.disabled = input.value !== 'HAPUS';
}

function confirmPermanentDelete() {
    if (!currentPermanentDeleteId) return;

    const btn = document.getElementById('permanentDeleteBtn');
    const spinner = document.getElementById('permanentDeleteSpinner');
    const btnText = document.getElementById('permanentDeleteBtnText');

    btn.disabled = true;
    spinner.classList.remove('hidden');
    btnText.textContent = 'Menghapus...';

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
        spinner.classList.add('hidden');
        btnText.textContent = 'Hapus Permanen';

        if (data.success) {
            closePermanentDeleteModal();
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
            btn.disabled = false;
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: data.message
            });
        }
    })
    .catch(error => {
        spinner.classList.add('hidden');
        btnText.textContent = 'Hapus Permanen';
        btn.disabled = false;
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: 'Terjadi kesalahan. Silakan coba lagi.'
        });
    });
}
</script>
@endpush
@endsection
