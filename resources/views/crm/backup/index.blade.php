@extends('layouts.app')

@section('title', 'Backup')

@section('page-title', 'Backup')

@section('styles')
<style>
    .backup-card {
        transition: all 0.3s ease;
    }

    .backup-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .backup-type-btn {
        transition: all 0.2s ease;
    }

    .backup-type-btn:hover {
        transform: scale(1.02);
    }

    .progress-overlay {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(4px);
    }

    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
        50% { box-shadow: 0 0 0 8px rgba(99, 102, 241, 0); }
    }

    .status-pending {
        animation: pulse-glow 2s infinite;
    }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Backup</h1>
        <p class="text-gray-500 mt-1">Kelola backup database, file, dan sistem</p>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Last Backup --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 backup-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Backup Terakhir</p>
                    <p class="text-xl font-bold text-gray-800 mt-1">
                        @if($statistics['latest_backup'])
                            {{ $statistics['latest_backup']->created_at->format('d M Y, H:i') }}
                        @else
                            <span class="text-gray-400">Belum ada</span>
                        @endif
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-clock-rotate-left text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Total Backups --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 backup-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Jumlah Backup</p>
                    <p class="text-xl font-bold text-gray-800 mt-1">{{ $statistics['total_backups'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-database text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Total Size --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 backup-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Ukuran</p>
                    <p class="text-xl font-bold text-gray-800 mt-1">{{ $statistics['formatted_total_size'] }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-hard-drive text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 backup-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Backup Berhasil</p>
                    <p class="text-xl font-bold text-green-600 mt-1">{{ $statistics['completed_backups'] }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-check-circle text-emerald-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - Backup Actions --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Backup Types --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Jenis Backup</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Database Backup --}}
                    <button
                        onclick="startBackup('database')"
                        id="btn-database"
                        class="backup-type-btn bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-5 text-white text-center hover:from-indigo-600 hover:to-indigo-700"
                    >
                        <div class="w-14 h-14 bg-white/20 rounded-xl mx-auto mb-3 flex items-center justify-center">
                            <i class="fa-solid fa-database text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-lg">Backup Database</h4>
                        <p class="text-sm text-white/80 mt-1">Backup seluruh database MySQL</p>
                    </button>

                    {{-- File Backup --}}
                    <button
                        onclick="startBackup('file')"
                        id="btn-file"
                        class="backup-type-btn bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-5 text-white text-center hover:from-cyan-600 hover:to-cyan-700"
                    >
                        <div class="w-14 h-14 bg-white/20 rounded-xl mx-auto mb-3 flex items-center justify-center">
                            <i class="fa-solid fa-folder text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-lg">Backup File</h4>
                        <p class="text-sm text-white/80 mt-1">Backup seluruh file upload</p>
                    </button>

                    {{-- Full Backup --}}
                    {{-- <button
                        onclick="startBackup('full')"
                        id="btn-full"
                        class="backup-type-btn bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl p-5 text-white text-center hover:from-violet-600 hover:to-violet-700"
                    >
                        <div class="w-14 h-14 bg-white/20 rounded-xl mx-auto mb-3 flex items-center justify-center">
                            <i class="fa-solid fa-box-archive text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-lg">Full Backup</h4>
                        <p class="text-sm text-white/80 mt-1">Backup database + storage</p>
                    </button> --}}
                </div>

                {{-- Progress Section --}}
                <div id="backupProgress" class="hidden mt-6">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span id="backupStatusText" class="text-sm font-medium text-gray-700">Preparing...</span>
                            <span id="backupPercent" class="text-sm font-medium text-indigo-600">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div id="backupProgressBar" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p id="backupMessage" class="text-xs text-gray-500 mt-2">Memproses...</p>
                    </div>
                </div>
            </div>

            {{-- Recent Backups --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Riwayat Backup</h3>
                    <a href="{{ route('pengaturan.backup.history') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        Lihat Semua
                    </a>
                </div>

                @if($recentBackups->isEmpty())
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                            <i class="fa-solid fa-inbox text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-500">Belum ada backup</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($recentBackups as $backup)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                        @if($backup->backup_type === 'database') bg-indigo-100 text-indigo-600
                                        @elseif($backup->backup_type === 'file') bg-cyan-100 text-cyan-600
                                        @else bg-violet-100 text-violet-600 @endif">
                                        <i class="fa-solid @if($backup->backup_type === 'database') fa-database
                                            @elseif($backup->backup_type === 'file') fa-folder
                                            @else fa-box-archive @endif"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $backup->filename }}</p>
                                        <p class="text-xs text-gray-500">{{ $backup->created_at->format('d M Y, H:i') }} - {{ $backup->formatted_filesize }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $backup->status_badge_class }}">
                                        {{ ucfirst($backup->status) }}
                                    </span>
                                    @if($backup->status === 'completed' && $backup->file_exists)
                                        <a href="{{ route('pengaturan.backup.download', $backup) }}" class="p-2 text-gray-500 hover:text-indigo-600 transition-colors" title="Download">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                        <a href="{{ route('pengaturan.backup.restore.show', $backup) }}" class="p-2 text-gray-500 hover:text-green-600 transition-colors" title="Restore">
                                            <i class="fa-solid fa-rotate"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Column - Settings --}}
        <div class="space-y-6">
            {{-- Schedule Info --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Jadwal Backup</h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Status</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $settings->is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ $settings->is_enabled ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Tipe</span>
                        <span class="font-medium text-gray-800">{{ $settings->schedule_type_label }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Waktu</span>
                        <span class="font-medium text-gray-800">{{ $settings->formatted_backup_time }}</span>
                    </div>
                    @if($settings->backup_day_label)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Hari</span>
                            <span class="font-medium text-gray-800">{{ $settings->backup_day_label }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Retensi</span>
                        <span class="font-medium text-gray-800">{{ $settings->retention_label }}</span>
                    </div>
                </div>

                @if($nextScheduled)
                    <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                        <p class="text-xs text-blue-600 font-medium">Backup Berikutnya:</p>
                        <p class="text-sm text-blue-800 font-medium">{{ $nextScheduled }}</p>
                    </div>
                @endif

                <a href="{{ route('pengaturan.backup.settings') }}" class="mt-4 block w-full text-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-gear mr-2"></i>
                    Atur Jadwal
                </a>
            </div>

            {{-- Quick Info --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-info-circle text-indigo-500 mt-0.5"></i>
                        <p class="text-gray-600">Backup disimpan di <code class="bg-gray-100 px-1 rounded">storage/app/backups</code></p>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-shield-halved text-green-500 mt-0.5"></i>
                        <p class="text-gray-600">Backup dienkripsi dan aman</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-trash text-red-500 mt-0.5"></i>
                        <p class="text-gray-600">Backup lama dihapus otomatis sesuai retensi</p>
                    </div>
                </div>
            </div>

            {{-- Storage Used --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Storage Digunakan</h3>

                <div class="text-center">
                    <div class="w-24 h-24 mx-auto mb-4 relative">
                        <svg class="w-24 h-24 transform -rotate-90">
                            <circle cx="48" cy="48" r="40" stroke="#e5e7eb" stroke-width="8" fill="none" />
                            <circle cx="48" cy="48" r="40" stroke="#667eea" stroke-width="8" fill="none"
                                stroke-dasharray="251.2"
                                stroke-dashoffset="188.4"
                                stroke-linecap="round" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fa-solid fa-server text-indigo-500 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-800">{{ $statistics['formatted_storage_used'] }}</p>
                    <p class="text-sm text-gray-500 mt-1">Total storage backup</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentBackupType = null;
    let progressInterval = null;

    async function startBackup(type) {
        // Confirm backup
        const typeLabels = {
            database: 'Backup Database',
            file: 'Backup File',
            full: 'Full Backup'
        };

        if (!confirm(`Yakin ingin membuat ${typeLabels[type]}?`)) {
            return;
        }

        // Disable buttons
        document.querySelectorAll('.backup-type-btn').forEach(btn => {
            btn.disabled = true;
            btn.classList.add('opacity-50');
        });

        // Show progress
        currentBackupType = type;
        const progressDiv = document.getElementById('backupProgress');
        const progressBar = document.getElementById('backupProgressBar');
        const progressPercent = document.getElementById('backupPercent');
        const statusText = document.getElementById('backupStatusText');
        const message = document.getElementById('backupMessage');

        progressDiv.classList.remove('hidden');
        progressBar.style.width = '0%';
        progressPercent.textContent = '0%';
        statusText.textContent = 'Memulai...';
        message.textContent = 'Mempersiapkan backup...';

        // Animate progress
        let progress = 0;
        const steps = [
            { percent: 20, text: 'Preparing...', message: 'Mempersiapkan data...' },
            { percent: 40, text: 'Processing...', message: 'Memproses backup...' },
            { percent: 70, text: 'Compressing...', message: 'Mengkompres file...' },
            { percent: 90, text: 'Finishing...', message: 'Menyelesaikan...' },
            { percent: 100, text: 'Completed', message: 'Backup selesai!' },
        ];

        let stepIndex = 0;

        try {
            // Call backup API
            const endpoints = {
                database: '{{ route('pengaturan.backup.database') }}',
                file: '{{ route('pengaturan.backup.file') }}',
                full: '{{ route('pengaturan.backup.full') }}'
            };

            const response = await fetch(endpoints[type], {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            // Simulate progress animation
            const animateProgress = () => {
                if (stepIndex < steps.length) {
                    const step = steps[stepIndex];
                    progressBar.style.width = step.percent + '%';
                    progressPercent.textContent = step.percent + '%';
                    statusText.textContent = step.text;
                    message.textContent = step.message;
                    stepIndex++;

                    if (stepIndex < steps.length) {
                        setTimeout(animateProgress, 800);
                    }
                }
            };

            animateProgress();

            if (data.success) {
                setTimeout(() => {
                    statusText.textContent = 'Selesai!';
                    message.textContent = `File: ${data.data.filename} (${data.data.formatted_filesize})`;
                    showToast(`${typeLabels[type]} berhasil dibuat!`, 'success');

                    // Reload after 2 seconds
                    setTimeout(() => location.reload(), 2000);
                }, 2000);
            } else {
                const errorMsg = extractErrorMessage(data, 'Gagal membuat backup');
                statusText.textContent = 'Gagal!';
                message.textContent = errorMsg;
                showToast(errorMsg, 'error');
            }

        } catch (error) {
            console.error('Backup error:', error);
            statusText.textContent = 'Gagal!';
            message.textContent = 'Terjadi kesalahan koneksi';
            showToast('Terjadi kesalahan saat membuat backup', 'error');
        } finally {
            // Re-enable buttons
            document.querySelectorAll('.backup-type-btn').forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            });
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
