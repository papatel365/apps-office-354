@extends('layouts.app')

@section('title', 'Profil Saya')

@section('styles')
<style>
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #e5e7eb;
    }

    .profile-avatar-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 48px;
        font-weight: 600;
    }

    .photo-upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: #f9fafb;
    }

    .photo-upload-zone:hover {
        border-color: #667eea;
        background: #f0f4ff;
    }

    .photo-upload-zone.dragover {
        border-color: #667eea;
        background: #eef2ff;
        transform: scale(1.02);
    }

    .photo-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #e5e7eb;
        margin: 0 auto;
    }

    .progress-bar {
        height: 8px;
        border-radius: 4px;
        background: #e5e7eb;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        transition: width 0.3s ease;
    }

    @media (max-width: 640px) {
        .profile-avatar,
        .profile-avatar-placeholder {
            width: 80px;
            height: 80px;
            font-size: 32px;
        }
    }
</style>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Profil Saya</h1>
        <p class="text-gray-500 mt-1">Kelola informasi profil dan akun Anda</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-check-circle text-green-500 text-xl"></i>
            <span class="text-green-700">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Profile Card --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                {{-- Avatar - Use profile_photo if available --}}
                <div class="flex flex-col items-center">
                    @php
                        $photoUrl = null;
                        if ($user->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo))
                            $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo);
                    @endphp
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="{{ $user->name }}" class="profile-avatar">
                    @else
                        <div class="profile-avatar-placeholder">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif

                    <h2 class="mt-4 text-lg font-semibold text-gray-800 text-center">{{ $user->name }}</h2>
                    <p class="text-gray-500 text-sm text-center">{{ $user->email }}</p>

                    <div class="mt-3 px-3 py-1 bg-blue-50 rounded-full">
                        <span class="text-blue-600 text-sm font-medium">{{ $user->display_role }}</span>
                    </div>
                </div>

                {{-- Company Info --}}
                @if($company)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500 mb-3">Informasi Perusahaan</h3>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-building text-gray-400 w-5"></i>
                            <span class="text-sm text-gray-700">{{ $company->name }}</span>
                        </div>
                        @if($company->phone)
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-phone text-gray-400 w-5"></i>
                            <span class="text-sm text-gray-700">{{ $company->phone }}</span>
                        </div>
                        @endif
                        @if($company->email)
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-envelope text-gray-400 w-5"></i>
                            <span class="text-sm text-gray-700">{{ $company->email }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Account Info --}}
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500 mb-3">Informasi Akun</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Bergabung</span>
                            <span class="text-gray-700">{{ $user->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Terakhir Login</span>
                            <span class="text-gray-700">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="lg:col-span-2">
            {{-- Profile Photo Upload Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">
                    <i class="fa-solid fa-camera mr-2 text-indigo-500"></i>
                    Foto Profile
                </h3>

                {{-- Current Photo Preview --}}
                <div class="text-center mb-6">
                    <div class="relative inline-block">
                        @php
                            $currentPhotoUrl = null;
                            if ($user->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo))
                                $currentPhotoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo);
                        @endphp
                        @if($currentPhotoUrl)
                            <img
                                id="currentPhotoPreview"
                                src="{{ $currentPhotoUrl }}"
                                alt="{{ $user->name }}"
                                class="photo-preview"
                            >
                        @else
                            <div
                                id="currentPhotoPreview"
                                class="photo-preview flex items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-5xl font-bold"
                            >
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif

                        {{-- Delete button (shown if photo exists) --}}
                        @if($currentPhotoUrl)
                            <button
                                type="button"
                                id="deletePhotoBtn"
                                onclick="deletePhoto()"
                                class="absolute -bottom-2 -right-2 w-10 h-10 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow-lg transition-colors"
                                title="Hapus Foto"
                            >
                                <i class="fa-solid fa-trash text-sm"></i>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Upload Form --}}
                <form id="photoUploadForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Drop Zone --}}
                    <div
                        id="dropZone"
                        class="photo-upload-zone"
                        onclick="document.getElementById('photoInput').click()"
                    >
                        <input
                            type="file"
                            name="photo"
                            id="photoInput"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            onchange="handleFileSelect(this)"
                        >

                        <div id="uploadPlaceholder">
                            <div class="w-16 h-16 mx-auto mb-4 bg-indigo-100 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-cloud-arrow-up text-2xl text-indigo-500"></i>
                            </div>
                            <p class="text-gray-600 font-medium mb-1">Drag & Drop foto di sini</p>
                            <p class="text-gray-400 text-sm mb-3">atau</p>
                            <span class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                                <i class="fa-solid fa-folder-open mr-2"></i>
                                Pilih Foto
                            </span>
                            <p class="text-gray-400 text-xs mt-3">JPG, PNG, WebP. Maksimal 5 MB</p>
                        </div>

                        {{-- Preview after selection --}}
                        <div id="uploadPreview" class="hidden">
                            <img id="selectedPhotoPreview" class="w-32 h-32 rounded-full object-cover mx-auto mb-4 border-4 border-indigo-200">
                            <p id="selectedFileName" class="text-gray-600 text-sm mb-3"></p>
                            <button
                                type="button"
                                onclick="event.stopPropagation(); clearSelection()"
                                class="text-red-500 hover:text-red-600 text-sm font-medium"
                            >
                                <i class="fa-solid fa-xmark mr-1"></i>
                                Batalkan
                            </button>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div id="uploadProgress" class="mt-4 hidden">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Mengupload...</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div id="progressBarFill" class="progress-bar-fill" style="width: 0%"></div>
                        </div>
                    </div>

                    {{-- Upload Button --}}
                    <div class="mt-4 flex gap-3 justify-center">
                        <button
                            type="submit"
                            id="uploadBtn"
                            class="inline-flex items-center px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <i class="fa-solid fa-upload mr-2"></i>
                            Simpan Foto
                        </button>
                    </div>
                </form>
            </div>

            {{-- Profile Information --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">Informasi Profil</h3>

                <form id="profileForm">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror"
                                placeholder="Masukkan nama lengkap">
                            @error('name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-500 @enderror"
                                placeholder="Masukkan email">
                            @error('email')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('phone') border-red-500 @enderror"
                                placeholder="Masukkan nomor telepon">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fa-solid fa-save mr-2"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            {{-- Password Change --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">Ubah Password</h3>

                <form id="passwordForm">
                    @csrf

                    <div class="space-y-4 max-w-md">
                        {{-- Current Password --}}
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="password" name="current_password" id="current_password" required
                                    class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Masukkan password saat ini">
                                <button type="button" onclick="togglePassword('current_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fa-solid fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>

                        {{-- New Password --}}
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required minlength="8"
                                    class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Minimal 8 karakter">
                                <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fa-solid fa-eye" id="password_icon"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="password" name="password_confirmation" id="password_confirmation" required
                                    class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Ulangi password baru">
                                <button type="button" onclick="togglePassword('password_confirmation')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fa-solid fa-eye" id="password_confirmation_icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fa-solid fa-key mr-2"></i>
                            Ubah Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Photo Upload Variables
    let selectedFile = null;

    // Toggle Password Visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(inputId + '_icon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Handle File Selection
    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            selectedFile = input.files[0];

            // Validate file
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(selectedFile.type)) {
                showToast('Format file tidak valid. Gunakan JPG, PNG, atau WebP.', 'error');
                return;
            }

            // Validate size (5MB)
            if (selectedFile.size > 5 * 1024 * 1024) {
                showToast('Ukuran file terlalu besar. Maksimal 5 MB.', 'error');
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('selectedPhotoPreview').src = e.target.result;
                document.getElementById('selectedFileName').textContent = selectedFile.name + ' (' + formatFileSize(selectedFile.size) + ')';
                document.getElementById('uploadPlaceholder').classList.add('hidden');
                document.getElementById('uploadPreview').classList.remove('hidden');
            };
            reader.readAsDataURL(selectedFile);
        }
    }

    // Clear Selection
    function clearSelection() {
        document.getElementById('photoInput').value = '';
        selectedFile = null;
        document.getElementById('uploadPlaceholder').classList.remove('hidden');
        document.getElementById('uploadPreview').classList.add('hidden');
    }

    // Format File Size
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // Photo Upload Form Submit
    document.getElementById('photoUploadForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!selectedFile) {
            showToast('Pilih foto terlebih dahulu', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('photo', selectedFile);

        const submitBtn = document.getElementById('uploadBtn');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Mengupload...';

        // Show progress
        const progressDiv = document.getElementById('uploadProgress');
        const progressBarFill = document.getElementById('progressBarFill');
        const progressPercent = document.getElementById('progressPercent');
        progressDiv.classList.remove('hidden');

        try {
            const response = await fetch('{{ route('profile.photo.upload') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('Foto profile berhasil diupload', 'success');

                // Update current photo preview
                const preview = document.getElementById('currentPhotoPreview');
                if (preview.tagName === 'IMG') {
                    preview.src = data.data.photo_url;
                } else {
                    // Replace placeholder with image
                    const newPreview = document.createElement('img');
                    newPreview.id = 'currentPhotoPreview';
                    newPreview.src = data.data.photo_url;
                    newPreview.className = 'photo-preview';
                    newPreview.alt = '{{ $user->name }}';
                    preview.replaceWith(newPreview);

                    // Add delete button
                    const deleteBtn = document.getElementById('deletePhotoBtn');
                    if (!deleteBtn) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.id = 'deletePhotoBtn';
                        btn.className = 'absolute -bottom-2 -right-2 w-10 h-10 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow-lg transition-colors';
                        btn.title = 'Hapus Foto';
                        btn.onclick = deletePhoto;
                        btn.innerHTML = '<i class="fa-solid fa-trash text-sm"></i>';
                        document.querySelector('#currentPhotoPreview').parentElement.appendChild(btn);
                    }
                }

                // Clear selection
                clearSelection();

                // Reload page after short delay
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(extractErrorMessage(data, 'Terjadi kesalahan'), 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat mengupload foto', 'error');
            console.error(error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            progressDiv.classList.add('hidden');
            progressBarFill.style.width = '0%';
        }
    });

    // Delete Photo
    async function deletePhoto() {
        if (!confirm('Apakah Anda yakin ingin menghapus foto profile?')) {
            return;
        }

        const deleteBtn = document.getElementById('deletePhotoBtn');

        try {
            const response = await fetch('{{ route('profile.photo.delete') }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                showToast('Foto profile berhasil dihapus', 'success');

                // Update current photo preview to default
                const preview = document.getElementById('currentPhotoPreview');
                if (preview.tagName === 'IMG') {
                    // Replace with placeholder
                    const placeholder = document.createElement('div');
                    placeholder.id = 'currentPhotoPreview';
                    placeholder.className = 'photo-preview flex items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-5xl font-bold';
                    placeholder.textContent = '{{ strtoupper(substr($user->name, 0, 1)) }}';
                    preview.replaceWith(placeholder);
                }

                // Remove delete button
                if (deleteBtn) {
                    deleteBtn.remove();
                }

                // Reload page after short delay
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(extractErrorMessage(data, 'Terjadi kesalahan'), 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat menghapus foto', 'error');
            console.error(error);
        }
    }

    // Drag and Drop
    const dropZone = document.getElementById('dropZone');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('dragover');
        }, false);
    });

    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('photoInput').files = files;
            handleFileSelect(document.getElementById('photoInput'));
        }
    }, false);

    // Profile Form Submit
    document.getElementById('profileForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Menyimpan...';

        try {
            const response = await fetch('{{ route('profile.update') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('Profil berhasil diperbarui', 'success');

                // Reload page after short delay
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(extractErrorMessage(data, 'Terjadi kesalahan'), 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat menyimpan', 'error');
            console.error(error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    // Password Form Submit
    document.getElementById('passwordForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Validate password confirmation
        const password = formData.get('password');
        const passwordConfirmation = formData.get('password_confirmation');

        if (password !== passwordConfirmation) {
            showToast('Password dan konfirmasi password tidak sama', 'error');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Mengubah...';

        try {
            const response = await fetch('{{ route('profile.password') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('Password berhasil diubah', 'success');
                this.reset();
            } else {
                showToast(extractErrorMessage(data, 'Terjadi kesalahan'), 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat mengubah password', 'error');
            console.error(error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    // Toast Notification
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
        }, 3000);
    }
</script>
@endsection
