@extends('layouts.app')

@section('title', 'Pengaturan Backup')

@section('page-title', 'Pengaturan Backup')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-6">
        <a href="{{ route('pengaturan.backup.index') }}" class="inline-flex items-center text-gray-500 hover:text-gray-700 mb-4">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Pengaturan Backup</h1>
        <p class="text-gray-500 mt-1">Atur jadwal dan retensi backup otomatis</p>
    </div>

    <form id="settingsForm">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Schedule Settings --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Jadwal Backup</h3>

                <div class="space-y-4">
                    {{-- Schedule Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Jadwal</label>
                        <select name="schedule_type" id="scheduleType" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" onchange="toggleScheduleOptions()">
                            <option value="manual" {{ $settings->schedule_type === 'manual' ? 'selected' : '' }}>Manual</option>
                            <option value="daily" {{ $settings->schedule_type === 'daily' ? 'selected' : '' }}>Harian</option>
                            <option value="weekly" {{ $settings->schedule_type === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                            <option value="monthly" {{ $settings->schedule_type === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                        </select>
                    </div>

                    {{-- Backup Time --}}
                    <div id="timeSection">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Waktu Backup</label>
                        <input type="time" name="backup_time" value="{{ \Carbon\Carbon::parse($settings->backup_time)->format('H:i') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-500 mt-1">Waktu dalam zona WIB (UTC+7)</p>
                    </div>

                    {{-- Day of Week (for weekly) --}}
                    <div id="daySection" class="{{ $settings->schedule_type !== 'weekly' ? 'hidden' : '' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hari</label>
                        <select name="backup_day" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="monday" {{ $settings->backup_day === 'monday' ? 'selected' : '' }}>Senin</option>
                            <option value="tuesday" {{ $settings->backup_day === 'tuesday' ? 'selected' : '' }}>Selasa</option>
                            <option value="wednesday" {{ $settings->backup_day === 'wednesday' ? 'selected' : '' }}>Rabu</option>
                            <option value="thursday" {{ $settings->backup_day === 'thursday' ? 'selected' : '' }}>Kamis</option>
                            <option value="friday" {{ $settings->backup_day === 'friday' ? 'selected' : '' }}>Jumat</option>
                            <option value="saturday" {{ $settings->backup_day === 'saturday' ? 'selected' : '' }}>Sabtu</option>
                            <option value="sunday" {{ $settings->backup_day === 'sunday' ? 'selected' : '' }}>Minggu</option>
                        </select>
                    </div>

                    {{-- Day of Month (for monthly) --}}
                    <div id="monthDaySection" class="{{ $settings->schedule_type !== 'monthly' ? 'hidden' : '' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                        <select name="backup_day_monthly" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @for($i = 1; $i <= 28; $i++)
                                <option value="{{ $i }}" {{ ($settings->backup_day == $i || ($settings->schedule_type !== 'weekly' && $settings->backup_day == $i)) ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Tanggal 1-28 untuk menghindari variasi jumlah hari</p>
                    </div>
                </div>
            </div>

            {{-- Retention Settings --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Retensi Backup</h3>

                <div class="space-y-4">
                    {{-- Retention Count --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Simpan Backup</label>
                        <select name="retention_count" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="7" {{ $settings->retention_count == 7 ? 'selected' : '' }}>7 Backup Terakhir</option>
                            <option value="14" {{ $settings->retention_count == 14 ? 'selected' : '' }}>14 Backup Terakhir</option>
                            <option value="30" {{ $settings->retention_count == 30 ? 'selected' : '' }}>30 Backup Terakhir</option>
                            <option value="60" {{ $settings->retention_count == 60 ? 'selected' : '' }}>60 Backup Terakhir</option>
                            <option value="90" {{ $settings->retention_count == 90 ? 'selected' : '' }}>90 Backup Terakhir</option>
                            <option value="365" {{ $settings->retention_count == 365 ? 'selected' : '' }}>365 Backup Terakhir</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Backup lama akan dihapus otomatis</p>
                    </div>

                    {{-- Enable/Disable --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-800">Aktifkan Jadwal</p>
                            <p class="text-sm text-gray-500">Backup otomatis sesuai jadwal</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_enabled" value="1" class="sr-only peer" {{ $settings->is_enabled ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    {{-- Next Scheduled --}}
                    @if($settings->is_enabled && $settings->schedule_type !== 'manual')
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-600 font-medium">Backup Berikutnya:</p>
                            <p class="text-sm text-blue-800 font-medium mt-1">{{ $settings->next_scheduled_run }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                <i class="fa-solid fa-save mr-2"></i>
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function toggleScheduleOptions() {
        const scheduleType = document.getElementById('scheduleType').value;
        const daySection = document.getElementById('daySection');
        const monthDaySection = document.getElementById('monthDaySection');

        if (scheduleType === 'weekly') {
            daySection.classList.remove('hidden');
            monthDaySection.classList.add('hidden');
        } else if (scheduleType === 'monthly') {
            daySection.classList.add('hidden');
            monthDaySection.classList.remove('hidden');
        } else {
            daySection.classList.add('hidden');
            monthDaySection.classList.add('hidden');
        }
    }

    document.getElementById('settingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Adjust backup_day for monthly
        const scheduleType = document.getElementById('scheduleType').value;
        if (scheduleType === 'monthly') {
            formData.set('backup_day', formData.get('backup_day_monthly'));
        }

        try {
            const response = await fetch('{{ route('pengaturan.backup.settings') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('Pengaturan berhasil disimpan', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(extractErrorMessage(data, 'Gagal menyimpan pengaturan'), 'error');
            }
        } catch (error) {
            console.error('Save error:', error);
            showToast('Terjadi kesalahan saat menyimpan', 'error');
        }
    });

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
