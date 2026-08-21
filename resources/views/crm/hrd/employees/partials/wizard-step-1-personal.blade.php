{{-- Step 1: Data Pribadi & Akun Login (Unified - Create & Edit) --}}
@php
    $isEditMode = isset($mode) && $mode === 'edit';
    $emp = $employee ?? null;
    $userData = ($emp && $emp->relationLoaded('user')) ? $emp->user : null;

    // Helper function for old input with fallback - uses data_get() for reliable access
    $oldInput = function($key, $emp = null, $user = null, $default = '') {
        // 1. First check Laravel's old() input
        $oldValue = old($key);
        if ($oldValue !== null && $oldValue !== '') {
            return $oldValue;
        }

        // 2. Check user fields (prefix: user.)
        if (str_starts_with($key, 'user.')) {
            $field = substr($key, 5);
            $value = data_get($user, $field);
            if ($value !== null) {
                return $value;
            }
            return $default;
        }

        // 3. Check employee fields (prefix: employee.)
        if (str_starts_with($key, 'employee.')) {
            $field = substr($key, 10);
            $value = data_get($emp, $field);
            if ($value !== null) {
                return $value;
            }
            return $default;
        }

        // 4. Direct field access
        $value = data_get($emp, $key);
        if ($value !== null) {
            return $value;
        }

        return $default;
    };
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold mr-3">1</span>
        Data Pribadi & Akun Login
    </h3>

    {{-- ======================================================= --}}
    {{-- SECTION A: AKUN LOGIN (OTOMATIS DIBUAT/GANTI)        --}}
    {{-- ======================================================= --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                <i class="fa-solid fa-key text-sm"></i>
            </div>
            <h4 class="text-md font-semibold text-gray-800">A. AKUN LOGIN</h4>
        </div>

        @if($isEditMode && $emp?->user)
        {{-- Edit Mode: Tampilkan info akun existing --}}
        <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-lg mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                        <i class="fa-solid fa-user-check text-indigo-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $userData->name ?? ($emp?->full_name ?? 'N/A') }}</p>
                        <p class="text-xs text-gray-500">{{ $userData->email ?? 'N/A' }}</p>
                        <p class="text-xs text-indigo-600 mt-1">
                            <i class="fa-solid fa-link mr-1"></i>
                            Akun aktif - Ubah password di bawah jika perlu
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden input untuk user_id --}}
        <input type="hidden" name="user[existing_user_id]" value="{{ $userData->id ?? old('user.existing_user_id', $emp?->user_id) }}">
        @else
        <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-lg mb-6">
            <p class="text-sm text-indigo-800">
                <i class="fa-solid fa-info-circle mr-2"></i>
                <strong>Akun akan dibuat secara otomatis</strong> setelah data karyawan disimpan.
            </p>
        </div>
        @endif

        {{-- Account Form Fields --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Username --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Username <span class="text-red-500">*</span>
                </label>
                <input type="text" name="user[name]"
                       value="{{ $oldInput('user.name', $emp, $userData, '') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('user.name') border-red-500 @enderror"
                       placeholder="Nama untuk login">
                @error('user.name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email Login --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Email Login <span class="text-red-500">*</span>
                </label>
                <input type="email" name="user[email]"
                       value="{{ $oldInput('user.email', $emp, $userData, '') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('user.email') border-red-500 @enderror"
                       placeholder="email@contoh.com">
                @error('user.email')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Password
                    @if(!$isEditMode)
                        <span class="text-red-500">*</span>
                    @else
                        <span class="text-gray-400">(kosongkan jika tidak diubah)</span>
                    @endif
                </label>
                <div class="relative" x-data="{showPassword: false}">
                    <input :type="showPassword ? 'text' : 'password'" name="user[password]"
                           class="w-full px-4 py-2.5 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('user.password') border-red-500 @enderror"
                           placeholder="{{ $isEditMode ? 'Minimal 6 karakter' : 'Minimal 6 karakter' }}">
                    <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                @error('user.password')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
                @if($isEditMode)
                    <p class="mt-1 text-xs text-gray-500">Isi hanya jika ingin mengubah password</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- SECTION B: DATA PRIBADI KARYAWAN (SELALU TAMPIL)     --}}
    {{-- ======================================================= --}}
    <div class="border-t border-gray-200 pt-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center">
                <i class="fa-solid fa-id-card text-sm"></i>
            </div>
            <h4 class="text-md font-semibold text-gray-800">B. DATA PRIBADI KARYAWAN</h4>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Nama Lengkap Karyawan --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="employee[full_name]" value="{{ $oldInput('employee.full_name', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee.full_name') border-red-500 @enderror"
                       placeholder="Nama lengkap karyawan">
                @error('employee.full_name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nama Panggilan --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Panggilan <span class="text-red-500">*</span></label>
                <input type="text" name="employee[nick_name]" value="{{ $oldInput('employee.nick_name', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('employee.nick_name') border-red-500 @enderror"
                       placeholder="Nama panggilan">
                @error('employee.nick_name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nomor Ponsel --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Ponsel <span class="text-red-500">*</span></label>
                <input type="tel" name="employee[phone]" value="{{ $oldInput('employee.phone', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee.phone') border-red-500 @enderror"
                       placeholder="08xxxxxxxxxx">
                @error('employee.phone')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- NIK / No. KTP --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NIK / No. KTP <span class="text-red-500">*</span></label>
                <input type="text" name="employee[nik]" value="{{ $oldInput('employee.nik', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee.nik') border-red-500 @enderror"
                       placeholder="321xxxxxxxxxxxxx">
                @error('employee.nik')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- NPWP Number --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NPWP</label>
                <input type="text" name="employee[npwp_number]" value="{{ $oldInput('employee.npwp_number', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                       placeholder="00.xxx.xxx.x-xxx.xxx">
            </div>

            {{-- Nomor BPJS Kesehatan --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor BPJS Kesehatan</label>
                <input type="text" name="employee[bpjs_kesehatan]" value="{{ $oldInput('employee.bpjs_kesehatan', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                       placeholder="Masukkan nomor BPJS Kesehatan"
                       maxlength="13">
            </div>

            {{-- Nomor BPJS Ketenagakerjaan --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor BPJS Ketenagakerjaan</label>
                <input type="text" name="employee[bpjs_ketenagakerjaan]" value="{{ $oldInput('employee.bpjs_ketenagakerjaan', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                       placeholder="Masukkan nomor BPJS Ketenagakerjaan"
                       maxlength="16">
            </div>

            {{-- Tempat Lahir --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tempat Lahir <span class="text-red-500">*</span></label>
                <input type="text" name="employee[place_of_birth]" value="{{ $oldInput('employee.place_of_birth', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee.place_of_birth') border-red-500 @enderror"
                       placeholder="Jakarta">
                @error('employee.place_of_birth')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tanggal Lahir --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir <span class="text-red-500">*</span></label>
                <input type="date" name="employee[date_of_birth]" value="{{ $oldInput('employee.date_of_birth', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee.date_of_birth') border-red-500 @enderror">
                @error('employee.date_of_birth')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Jenis Kelamin --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin <span class="text-red-500">*</span></label>
                <select name="employee[gender]"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee.gender') border-red-500 @enderror">
                    <option value="">Pilih</option>
                    <option value="male" {{ $oldInput('employee.gender', $emp, $userData) == 'male' ? 'selected' : '' }}>Laki-laki</option>
                    <option value="female" {{ $oldInput('employee.gender', $emp, $userData) == 'female' ? 'selected' : '' }}>Perempuan</option>
                </select>
                @error('employee.gender')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Agama --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Agama <span class="text-red-500">*</span></label>
                <select name="employee[religion]"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee.religion') border-red-500 @enderror">
                    <option value="">Pilih</option>
                    @foreach(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'] as $religion)
                        <option value="{{ strtolower($religion) }}" {{ $oldInput('employee.religion', $emp, $userData) == strtolower($religion) ? 'selected' : '' }}>
                            {{ $religion }}
                        </option>
                    @endforeach
                </select>
                @error('employee.religion')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status Perkawinan --}}
            <div x-data="{
                get isMarried() {
                    // Get value from DOM select element (handles both Alpine and Blade initial values)
                    const select = document.querySelector('select[name=\'employee[marital_status]\']');
                    return select ? select.value === 'married' : false;
                }
            }">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Perkawinan <span class="text-red-500">*</span></label>
                    <select name="employee[marital_status]"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee.marital_status') border-red-500 @enderror">
                        <option value="">Pilih</option>
                        <option value="single" {{ $oldInput('employee.marital_status', $emp, $userData) == 'single' ? 'selected' : '' }}>Belum Menikah</option>
                        <option value="married" {{ $oldInput('employee.marital_status', $emp, $userData) == 'married' ? 'selected' : '' }}>Menikah</option>
                        <option value="divorced" {{ $oldInput('employee.marital_status', $emp, $userData) == 'divorced' ? 'selected' : '' }}>Cerai Hidup</option>
                        <option value="widowed" {{ $oldInput('employee.marital_status', $emp, $userData) == 'widowed' ? 'selected' : '' }}>Cerai Mati</option>
                    </select>
                </div>
                @error('employee.marital_status')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror

                {{-- Informasi Keluarga (muncul hanya jika Status Perkawinan = Menikah) --}}
                <div x-show="isMarried" x-transition class="mt-4 space-y-4">
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i class="fa-solid fa-people-roof mr-2 text-gray-400"></i>
                            Informasi Keluarga
                        </h5>

                        {{-- Apakah Memiliki Anak? --}}
                        <div x-data="{
                            get hasChildren() {
                                // Check if '1' radio is checked
                                const radioYes = document.querySelector('input[name=\'employee[punya_anak]\'][value=\'1\']');
                                return radioYes && radioYes.checked;
                            },
                            get showChildCount() { return this.hasChildren; }
                        }">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Apakah Memiliki Anak?</label>
                            <div class="flex gap-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="employee[punya_anak]" value="1"
                                           class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                           {{ $oldInput('employee.punya_anak', $emp, $userData) == '1' || $oldInput('employee.punya_anak', $emp, $userData) === true ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700">Ya</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="employee[punya_anak]" value="0"
                                           class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                           {{ $oldInput('employee.punya_anak', $emp, $userData) == '0' || $oldInput('employee.punya_anak', $emp, $userData) === 'false' ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700">Tidak</span>
                                </label>
                            </div>
                        </div>

                        {{-- Jumlah Anak (muncul hanya jika Memiliki Anak = Ya) --}}
                        <div x-data="{
                            get hasChildren() {
                                const radioYes = document.querySelector('input[name=\'employee[punya_anak]\'][value=\'1\']');
                                return radioYes && radioYes.checked;
                            }
                        }" x-show="hasChildren" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-1 mt-3">Jumlah Anak</label>
                            <input type="number" name="employee[jumlah_anak]"
                                   value="{{ $oldInput('employee.jumlah_anak', $emp, $userData) }}"
                                   min="1" max="20" placeholder="Masukkan jumlah anak"
                                   class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee.jumlah_anak') border-red-500 @enderror">
                            @error('employee.jumlah_anak')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Golongan Darah --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Golongan Darah <span class="text-red-500">*</span></label>
                <select name="employee[blood_type]"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('employee.blood_type') border-red-500 @enderror">
                    <option value="">Pilih</option>
                    @foreach(['A', 'B', 'AB', 'O'] as $blood)
                        <option value="{{ $blood }}" {{ $oldInput('employee.blood_type', $emp, $userData) == $blood ? 'selected' : '' }}>{{ $blood }}</option>
                    @endforeach
                </select>
                @error('employee.blood_type')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status Karyawan (Edit mode only) --}}
            @if($isEditMode)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status Karyawan</label>
                <div class="mt-2 flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="employee[is_active]" value="1"
                            {{ $oldInput('employee.is_active', $emp, true) ? 'checked' : '' }}
                            class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Aktif</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="employee[is_active]" value="0"
                            {{ $oldInput('employee.is_active', $emp, true) === false ? 'checked' : '' }}
                            class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Resign</span>
                    </label>
                </div>
            </div>
            @endif
        </div>

        {{-- Alamat --}}
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat <span class="text-red-500">*</span></label>
            <textarea name="employee[address]" rows="2"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('employee.address') border-red-500 @enderror"
                      placeholder="Alamat lengkap">{{ $oldInput('employee.address', $emp, $userData) }}</textarea>
            @error('employee.address')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Kota, Provinsi, Kode Pos --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kota/Kabupaten <span class="text-red-500">*</span></label>
                <input type="text" name="employee[city]" value="{{ $oldInput('employee.city', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('employee.city') border-red-500 @enderror"
                       placeholder="Jakarta">
                @error('employee.city')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span class="text-red-500">*</span></label>
                <input type="text" name="employee[province]" value="{{ $oldInput('employee.province', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('employee.province') border-red-500 @enderror"
                       placeholder="DKI Jakarta">
                @error('employee.province')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kode Pos <span class="text-red-500">*</span></label>
                <input type="text" name="employee[postal_code]" value="{{ $oldInput('employee.postal_code', $emp, $userData) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('employee.postal_code') border-red-500 @enderror"
                       placeholder="12345">
                @error('employee.postal_code')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Emergency Contact --}}
        <div class="mt-6">
            <h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                <i class="fa-solid fa-phone-volume mr-2 text-gray-400"></i>
                Kontak Darurat
            </h5>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kontak</label>
                    <input type="text" name="employee[emergency_contact_name]" value="{{ $oldInput('employee.emergency_contact_name', $emp, $userData) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="Nama kontak darurat">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Ponsel Darurat</label>
                    <input type="tel" name="employee[emergency_contact_phone]" value="{{ $oldInput('employee.emergency_contact_phone', $emp, $userData) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="08xxxxxxxxxx">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hubungan</label>
                    <select name="employee[emergency_contact_relation]"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">Pilih</option>
                        <option value="suami" {{ $oldInput('employee.emergency_contact_relation', $emp, $userData) == 'suami' ? 'selected' : '' }}>Suami</option>
                        <option value="istri" {{ $oldInput('employee.emergency_contact_relation', $emp, $userData) == 'istri' ? 'selected' : '' }}>Istri</option>
                        <option value="orang_tua" {{ $oldInput('employee.emergency_contact_relation', $emp, $userData) == 'orang_tua' ? 'selected' : '' }}>Orang Tua</option>
                        <option value="saudara" {{ $oldInput('employee.emergency_contact_relation', $emp, $userData) == 'saudara' ? 'selected' : '' }}>Saudara</option>
                        <option value="lainnya" {{ $oldInput('employee.emergency_contact_relation', $emp, $userData) == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
