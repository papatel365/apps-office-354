@extends('layouts.app')

@section('title', 'Restore Backup')

@section('page-title', 'Restore Backup')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-6">
        <a href="{{ route('pengaturan.backup.history') }}" class="inline-flex items-center text-gray-500 hover:text-gray-700 mb-4">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali ke Riwayat
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Restore Backup</h1>
        <p class="text-gray-500 mt-1">Pulihkan data dari file backup</p>
    </div>

    {{-- Wizard Steps --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            {{-- Step 1 --}}
            <div id="step1" class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">1</div>
                <div class="ml-3">
                    <p class="font-medium text-gray-800">Preview</p>
                    <p class="text-sm text-gray-500">Periksa file backup</p>
                </div>
            </div>

            <div class="flex-1 mx-4 border-t-2 border-gray-200"></div>

            {{-- Step 2 --}}
            <div id="step2" class="flex items-center opacity-50">
                <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-semibold">2</div>
                <div class="ml-3">
                    <p class="font-medium text-gray-600">Konfirmasi</p>
                    <p class="text-sm text-gray-400">Pilih data yang di-restore</p>
                </div>
            </div>

            <div class="flex-1 mx-4 border-t-2 border-gray-200"></div>

            {{-- Step 3 --}}
            <div id="step3" class="flex items-center opacity-50">
                <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-semibold">3</div>
                <div class="ml-3">
                    <p class="font-medium text-gray-600">Restore</p>
                    <p class="text-sm text-gray-400">Proses restore</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 1: Preview --}}
    <div id="previewSection">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Backup</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Nama File</span>
                        <span class="font-medium text-gray-800">{{ $backup->filename }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Jenis</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $backup->type_badge_class }}">
                            {{ ucfirst($backup->backup_type) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Ukuran</span>
                        <span class="font-medium text-gray-800">{{ $backup->formatted_filesize }}</span>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Tanggal Backup</span>
                        <span class="font-medium text-gray-800">{{ $backup->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Status</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $backup->status_badge_class }}">
                            {{ ucfirst(str_replace('_', ' ', $backup->status)) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Checksum</span>
                        <span class="font-mono text-xs text-gray-600">{{ $backup->checksum ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            {{-- Warning --}}
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-yellow-600 text-lg mt-0.5"></i>
                    <div>
                        <h4 class="font-semibold text-yellow-800">Perhatian!</h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            Proses restore akan menimpa data yang ada saat ini. Pastikan Anda telah membuat backup sebelum melanjutkan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button onclick="goToStep(2)" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                    Lanjutkan
                    <i class="fa-solid fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Step 2: Options --}}
    <div id="optionsSection" class="hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Pilih Data yang Di-restore</h3>

            <form id="restoreForm">
                @csrf

                <div class="space-y-4">
                    {{-- Select All --}}
                    <label class="flex items-center p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" id="selectAll" checked class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500" onchange="toggleAll()">
                        <span class="ml-3 font-medium text-gray-800">Pilih Semua</span>
                    </label>

                    {{-- Database --}}
                    <label class="flex items-center p-4 bg-indigo-50 rounded-lg cursor-pointer hover:bg-indigo-100 transition-colors">
                        <input type="checkbox" name="options[]" value="database" checked
                            class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 option-checkbox">
                        <div class="ml-3 flex-1">
                            <div class="flex items-center">
                                <i class="fa-solid fa-database text-indigo-600 mr-2"></i>
                                <span class="font-medium text-gray-800">Database</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Pulihkan seluruh data database</p>
                        </div>
                    </label>

                    {{-- Files --}}
                    <label class="flex items-center p-4 bg-cyan-50 rounded-lg cursor-pointer hover:bg-cyan-100 transition-colors">
                        <input type="checkbox" name="options[]" value="files" checked
                            class="w-5 h-5 text-cyan-600 rounded focus:ring-cyan-500 option-checkbox">
                        <div class="ml-3 flex-1">
                            <div class="flex items-center">
                                <i class="fa-solid fa-folder text-cyan-600 mr-2"></i>
                                <span class="font-medium text-gray-800">File & Storage</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Pulihkan file upload dan dokumen</p>
                        </div>
                    </label>
                </div>

                {{-- Hidden input for "all" --}}
                <input type="hidden" name="options[]" value="all" id="allOption">

                <div class="mt-6 flex justify-between">
                    <button type="button" onclick="goToStep(1)" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </button>
                    <button type="button" onclick="startRestore()" class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        <i class="fa-solid fa-rotate mr-2"></i>
                        Mulai Restore
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Step 3: Progress --}}
    <div id="restoreProgress" class="hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-6 text-center">Memproses Restore...</h3>

            <div class="max-w-md mx-auto">
                <div class="flex items-center justify-center mb-8">
                    <div class="relative w-24 h-24">
                        <div class="absolute inset-0 border-4 border-gray-200 rounded-full"></div>
                        <div id="progressCircle" class="absolute inset-0 border-4 border-green-500 rounded-full" style="clip-path: polygon(50% 50%, 50% 0%, 100% 0%, 100% 50%, 50% 50%, 50% 100%, 0% 100%, 0% 50%, 50% 50%);"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fa-solid fa-rotate text-green-500 text-3xl" id="progressIcon"></i>
                        </div>
                    </div>
                </div>

                <div class="text-center mb-6">
                    <p id="restoreStatus" class="text-lg font-medium text-gray-800">Menyiapkan...</p>
                    <p id="restoreMessage" class="text-sm text-gray-500 mt-1">Memproses data backup</p>
                </div>

                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="restoreProgressBar" class="bg-green-500 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 4: Complete --}}
    <div id="restoreComplete" class="hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                <i class="fa-solid fa-check text-green-600 text-4xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Restore Berhasil!</h3>
            <p class="text-gray-500 mb-6">Data berhasil dipulihkan dari backup</p>

            <div class="flex justify-center gap-4">
                <a href="{{ route('pengaturan.backup.history') }}" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                    <i class="fa-solid fa-list mr-2"></i>
                    Lihat Riwayat
                </a>
                <a href="{{ route('beranda') }}" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                    <i class="fa-solid fa-home mr-2"></i>
                    Beranda
                </a>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    let currentStep = 1;

    function goToStep(step) {
        // Hide all sections
        document.getElementById('previewSection').classList.add('hidden');
        document.getElementById('optionsSection').classList.add('hidden');

        // Show selected section
        if (step === 1) {
            document.getElementById('previewSection').classList.remove('hidden');
        } else if (step === 2) {
            document.getElementById('optionsSection').classList.remove('hidden');
        }

        // Update step indicators
        for (let i = 1; i <= 3; i++) {
            const stepEl = document.getElementById('step' + i);
            const circle = stepEl.querySelector('div:first-child');

            if (i <= step) {
                circle.className = 'w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold';
                stepEl.classList.remove('opacity-50');
            } else {
                circle.className = 'w-10 h-10 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-semibold';
                stepEl.classList.add('opacity-50');
            }
        }

        currentStep = step;
    }

    function toggleAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.option-checkbox');

        checkboxes.forEach(cb => {
            cb.checked = selectAll.checked;
        });
    }

    function toggleOption() {
        const checkboxes = document.querySelectorAll('.option-checkbox');
        const selectAll = document.getElementById('selectAll');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);

        selectAll.checked = allChecked;
    }

    async function startRestore() {
        // Show progress section
        document.getElementById('optionsSection').classList.add('hidden');
        document.getElementById('restoreProgress').classList.remove('hidden');

        // Update step indicators
        const step3 = document.getElementById('step3');
        const circle = step3.querySelector('div:first-child');
        circle.className = 'w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold';
        step3.classList.remove('opacity-50');

        const status = document.getElementById('restoreStatus');
        const message = document.getElementById('restoreMessage');
        const progressBar = document.getElementById('restoreProgressBar');

        try {
            // Get selected options
            const formData = new FormData(document.getElementById('restoreForm'));

            // Animate progress
            const steps = [
                { percent: 20, status: 'Menyiapkan...', message: 'Memvalidasi file backup' },
                { percent: 40, status: 'Memproses...', message: 'Mengekstrak data' },
                { percent: 60, status: 'Memulihkan Database...', message: 'Mengimpor data database' },
                { percent: 80, status: 'Memulihkan File...', message: 'Menyalin file storage' },
                { percent: 100, status: 'Selesai!', message: 'Semua data berhasil dipulihkan' },
            ];

            for (const step of steps) {
                await new Promise(resolve => setTimeout(resolve, 800));
                progressBar.style.width = step.percent + '%';
                status.textContent = step.status;
                message.textContent = step.message;
            }

            // Call restore API
            const response = await fetch('{{ route('pengaturan.backup.restore', $backup) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Show complete section
                document.getElementById('restoreProgress').classList.add('hidden');
                document.getElementById('restoreComplete').classList.remove('hidden');

                showToast('Restore berhasil!', 'success');
            } else {
                throw new Error(extractErrorMessage(data, 'Restore gagal'));
            }

        } catch (error) {
            console.error('Restore error:', error);
            message.textContent = error.message || 'Terjadi kesalahan';
            message.classList.add('text-red-500');
            showToast(error.message || 'Restore gagal', 'error');
        }
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        const bgClass = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
        const iconClass = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';

        toast.className = `fixed bottom-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 ${bgClass} text-white`;
        toast.innerHTML = `<i class="fa-solid ${iconClass} text-lg flex-shrink-0"></i><span>${message}</span>`;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
</script>
@endsection
