@extends('layouts.app')

@section('title', 'Riwayat Backup')

@section('page-title', 'Riwayat Backup')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Riwayat Backup</h1>
            <p class="text-gray-500 mt-1">Daftar semua backup yang telah dibuat</p>
        </div>
        <a href="{{ route('pengaturan.backup.index') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Type Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Backup</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua</option>
                    <option value="database" {{ request('type') === 'database' ? 'selected' : '' }}>Database</option>
                    <option value="file" {{ request('type') === 'file' ? 'selected' : '' }}>File</option>
                    <option value="full" {{ request('type') === 'full' ? 'selected' : '' }}>Full</option>
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Gagal</option>
                    <option value="restored" {{ request('status') === 'restored' ? 'selected' : '' }}>Restored</option>
                </select>
            </div>

            {{-- From Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- To Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- Actions --}}
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                    <i class="fa-solid fa-filter mr-1"></i>
                    Filter
                </button>
                <a href="{{ route('pengaturan.backup.history') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Backups Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ukuran</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($backups as $backup)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
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
                                        <p class="text-xs text-gray-500">
                                            @if($backup->is_scheduled)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                                    <i class="fa-solid fa-clock mr-1"></i>
                                                    Terjadwal
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                    Manual
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $backup->type_badge_class }}">
                                    {{ ucfirst($backup->backup_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $backup->formatted_filesize }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $backup->status_badge_class }}">
                                    {{ ucfirst(str_replace('_', ' ', $backup->status)) }}
                                </span>
                                @if($backup->status === 'failed' && $backup->error_message)
                                    <p class="text-xs text-red-500 mt-1" title="{{ $backup->error_message }}">
                                        {{ Str::limit($backup->error_message, 30) }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $backup->created_at->format('d M Y') }}<br>
                                <span class="text-gray-400">{{ $backup->created_at->format('H:i:s') }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $backup->formatted_duration }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($backup->status === 'completed')
                                        @if($backup->file_exists)
                                            <a href="{{ route('pengaturan.backup.download', $backup) }}" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Download">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <a href="{{ route('pengaturan.backup.restore.show', $backup) }}" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Restore">
                                                <i class="fa-solid fa-rotate"></i>
                                            </a>
                                        @endif
                                    @endif
                                    <button onclick="deleteBackup({{ $backup->id }})" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                                    <i class="fa-solid fa-inbox text-gray-400 text-2xl"></i>
                                </div>
                                <p class="text-gray-500">Tidak ada backup yang ditemukan</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($backups->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $backups->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function deleteBackup(id) {
        if (!confirm('Yakin ingin menghapus backup ini?')) {
            return;
        }

        try {
            const response = await fetch('{{ route('pengaturan.backup.destroy', ['backup' => ':id']) }}'.replace(':id', id), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                showToast('Backup berhasil dihapus', 'success');
                location.reload();
            } else {
                showToast(extractErrorMessage(data, 'Gagal menghapus backup'), 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showToast('Terjadi kesalahan saat menghapus backup', 'error');
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
