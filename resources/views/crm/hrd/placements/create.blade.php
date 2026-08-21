@extends('layouts.app')

@section('title', 'Tambah Lokasi Penempatan')

@section('page-title', 'Tambah Lokasi Penempatan')
@section('page-subtitle', 'Tambahkan lokasi kerja baru untuk karyawan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Informasi Lokasi</h3>
        </div>

        <form id="placementForm" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Lokasi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Contoh: Kantor Pusat">
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                        Kode
                    </label>
                    <input type="text" name="code" id="code"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Contoh: HO, CAB-A">
                </div>
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                    Alamat
                </label>
                <textarea name="address" id="address" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Alamat lengkap lokasi"></textarea>
            </div>

            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                    Kota
                </label>
                <input type="text" name="city" id="city"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Contoh: Jakarta Selatan">
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h4 class="text-sm font-semibold text-gray-900 mb-4">
                    <i class="fa-solid fa-map-marker-alt mr-2 text-indigo-600"></i>
                    Koordinat GPS & Radius Absensi
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700 mb-1">
                            Latitude
                        </label>
                        <input type="number" step="0.00000001" name="latitude" id="latitude"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="-6.2088">
                    </div>

                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700 mb-1">
                            Longitude
                        </label>
                        <input type="number" step="0.00000001" name="longitude" id="longitude"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="106.8456">
                    </div>

                    <div>
                        <label for="radius_meters" class="block text-sm font-medium text-gray-700 mb-1">
                            Radius (meter)
                        </label>
                        <input type="number" name="radius_meters" id="radius_meters" value="100" min="10" max="5000"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <p class="mt-2 text-xs text-gray-500">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    Radius menentukan area yang diizinkan untuk absensi. Karyawan yang absen di luar radius akan ditandai "Di Luar Area".
                </p>

                <div class="mt-4">
                    <button type="button" onclick="getCurrentLocation()" class="inline-flex items-center px-3 py-2 text-sm bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100">
                        <i class="fa-solid fa-location-crosshairs mr-2"></i>
                        Dapatkan Lokasi Saat Ini
                    </button>
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    Deskripsi
                </label>
                <textarea name="description" id="description" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Deskripsi tambahan untuk lokasi ini"></textarea>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked
                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="is_active" class="ml-2 text-sm text-gray-700">
                    Lokasi ini aktif
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('administrasi.placements.index') }}" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fa-solid fa-save mr-2"></i>
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function getCurrentLocation() {
        if (!navigator.geolocation) {
            alert('Geolocation tidak didukung oleh browser Anda');
            return;
        }

        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Mendapatkan lokasi...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                document.getElementById('latitude').value = position.coords.latitude.toFixed(8);
                document.getElementById('longitude').value = position.coords.longitude.toFixed(8);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-location-crosshairs mr-2"></i>Dapatkan Lokasi Saat Ini';
                showToast('Lokasi berhasil ditemukan', 'success');
            },
            (error) => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-location-crosshairs mr-2"></i>Dapatkan Lokasi Saat Ini';
                showToast('Gagal mendapatkan lokasi: ' + error.message, 'error');
            }
        );
    }

    document.getElementById('placementForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Menyimpan...';

        try {
            const response = await fetch('{{ route('administrasi.placements.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    window.location.href = '{{ route('administrasi.placements.index') }}';
                }, 1000);
            } else {
                showToast(extractErrorMessage(data, 'Terjadi kesalahan'), 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat menyimpan', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 ${type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'} text-white`;
        toast.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>${message}`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>
@endpush
