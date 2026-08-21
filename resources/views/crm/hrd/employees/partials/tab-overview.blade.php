{{-- Tab Overview - Comprehensive Dashboard --}}
<div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
    <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-4 sm:mb-6 flex items-center">
        <i class="fa-solid fa-home mr-2 text-indigo-600"></i>
        Ringkasan Profil
    </h3>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
        {{-- Left Column --}}
        <div class="space-y-4 sm:space-y-6">
            {{-- User Account Status --}}
            <div class="border rounded-lg p-3 sm:p-4 {{ $employee->is_active ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 {{ $employee->is_active ? 'bg-green-100' : 'bg-red-100' }}">
                            @if($employee->is_active)
                                <i class="fa-solid fa-user-check text-green-600"></i>
                            @else
                                <i class="fa-solid fa-user-slash text-red-600"></i>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800">Akun Login</p>
                            <p class="text-xs text-gray-500 truncate">{{ $employee->user?->email ?? '-' }}</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 {{ $employee->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded-full text-xs font-medium shrink-0">
                        {{ $employee->is_active ? 'Terhubung' : 'Resign' }}
                    </span>
                </div>
            </div>

            {{-- DATA PRIBADI Section --}}
            <div class="border-b border-gray-200 pb-4">
                <h4 class="text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wide mb-3 flex items-center">
                    <i class="fa-solid fa-user mr-2 text-gray-400"></i>
                    Data Pribadi
                </h4>
                <dl class="space-y-2 sm:space-y-3">
                    {{-- Nama Lengkap --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Nama Lengkap</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->full_name ?? '-' }}</dd>
                    </div>
                    {{-- Username --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Username</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->user?->name ?? '-' }}</dd>
                    </div>
                    {{-- Email --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Email</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words text-sm sm:text-base">{{ $employee->user?->email ?? '-' }}</dd>
                    </div>
                    {{-- No. HP --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">No. HP</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->phone ?? $employee->mobile ?? '-' }}</dd>
                    </div>
                    {{-- NIK --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">NIK</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->nik ?? '-' }}</dd>
                    </div>
                    {{-- NPWP --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">NPWP</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->npwp_number ?? '-' }}</dd>
                    </div>
                    {{-- BPJS Kesehatan --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">BPJS Kesehatan</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->bpjs_kesehatan ?? '-' }}</dd>
                    </div>
                    {{-- BPJS Ketenagakerjaan --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">BPJS Naker</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->bpjs_ketenagakerjaan ?? '-' }}</dd>
                    </div>
                    {{-- Jenis Kelamin --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Jenis Kelamin</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2">
                            @if($employee->gender === 'male') Laki-laki
                            @elseif($employee->gender === 'female') Perempuan
                            @else - @endif
                        </dd>
                    </div>
                    {{-- Tanggal Lahir --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Tanggal Lahir</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2">{{ $employee->date_of_birth?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    {{-- Tempat Lahir --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Tempat Lahir</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->birth_place ?? '-' }}</dd>
                    </div>
                    {{-- Gol. Darah --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Gol. Darah</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2">{{ $employee->blood_type ?? '-' }}</dd>
                    </div>
                    {{-- Agama --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Agama</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2">{{ ucfirst($employee->religion ?? '-') }}</dd>
                    </div>
                    {{-- Status --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Status</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2">
                            @switch($employee->marital_status)
                                @case('single') Belum Menikah @break
                                @case('married') Menikah @break
                                @case('divorced') Cerai @break
                                @case('widowed') Duda/Janda @break
                                @default - @endswitch
                        </dd>
                    </div>
                    {{-- Alamat --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Alamat</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->address ?? '-' }}</dd>
                    </div>
                    {{-- Kota --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Kota</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->city ?? '-' }}</dd>
                    </div>
                    {{-- Provinsi --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Provinsi</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->province ?? '-' }}</dd>
                    </div>
                    {{-- Kode Pos --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Kode Pos</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2">{{ $employee->postal_code ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- PEKERJAAN Section --}}
            <div class="border-b border-gray-200 pb-4">
                <h4 class="text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wide mb-3 flex items-center">
                    <i class="fa-solid fa-briefcase mr-2 text-gray-400"></i>
                    Pekerjaan
                </h4>
                <dl class="space-y-2 sm:space-y-3">
                    {{-- Departemen --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Departemen</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->department?->name ?? '-' }}</dd>
                    </div>
                    {{-- Divisi --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Divisi</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->division?->name ?? '-' }}</dd>
                    </div>
                    {{-- Posisi --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Posisi</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->position?->name ?? '-' }}</dd>
                    </div>
                    {{-- Tipe Karyawan --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Tipe Karyawan</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2">{{ $employee->employeeType?->name ?? '-' }}</dd>
                    </div>
                    {{-- Status --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Status</dt>
                        <dd class="font-medium sm:col-span-2">
                            @if($employee->is_active)
                                <span class="text-green-600">Aktif</span>
                            @else
                                <span class="text-red-600">Resign</span>
                            @endif
                        </dd>
                    </div>
                    {{-- Atasan --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Atasan</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->supervisor?->full_name ?? '-' }}</dd>
                    </div>
                    {{-- Tanggal Bergabung --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Tgl Bergabung</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2">{{ $employee->join_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    {{-- Mulai Kerja --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Mulai Kerja</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2">{{ $employee->contract_start?->format('d M Y') ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-4 sm:space-y-6">
            {{-- PENEMPATAN Section --}}
            <div class="border-b border-gray-200 pb-4">
                <h4 class="text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wide mb-3 flex items-center">
                    <i class="fa-solid fa-map-marker-alt mr-2 text-gray-400"></i>
                    Penempatan
                </h4>
                <dl class="space-y-2 sm:space-y-3">
                    {{-- Lokasi --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Lokasi</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->placement?->name ?? '-' }}</dd>
                    </div>
                    {{-- Cabang --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                        <dt class="text-gray-600 text-sm">Cabang</dt>
                        <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $employee->placement?->code ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- GAJI Section --}}
            <div class="border-b border-gray-200 pb-4">
                <h4 class="text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wide mb-3 flex items-center">
                    <i class="fa-solid fa-money-bills mr-2 text-gray-400"></i>
                    Gaji
                </h4>
                @if($salaryDetails)
                    <dl class="space-y-2 sm:space-y-3">
                        {{-- Gaji Pokok --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                            <dt class="text-gray-600 text-sm">Gaji Pokok</dt>
                            <dd class="text-gray-900 font-medium sm:col-span-2 break-words">Rp {{ number_format($salaryDetails['basic_salary'] ?? 0, 0, ',', '.') }}</dd>
                        </div>
                        {{-- Total Tunjangan --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                            <dt class="text-gray-600 text-sm">Total Tunjangan</dt>
                            <dd class="text-green-600 font-medium sm:col-span-2 break-words">Rp {{ number_format($salaryDetails['total_allowances'] ?? 0, 0, ',', '.') }}</dd>
                        </div>
                        {{-- Total Potongan --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                            <dt class="text-gray-600 text-sm">Total Potongan</dt>
                            <dd class="text-red-600 font-medium sm:col-span-2 break-words">Rp {{ number_format($salaryDetails['total_deductions'] ?? 0, 0, ',', '.') }}</dd>
                        </div>
                        {{-- Gaji Bersih --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4 bg-indigo-50 -mx-1 sm:mx-0 px-2 py-2 rounded-lg sm:rounded">
                            <dt class="text-gray-800 font-medium sm:font-semibold">Gaji Bersih</dt>
                            <dd class="text-indigo-600 font-bold sm:font-bold col-span-2 sm:col-span-2">Rp {{ number_format($salaryDetails['total_salary'] ?? 0, 0, ',', '.') }}</dd>
                        </div>
                        {{-- Metode --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                            <dt class="text-gray-600 text-sm">Metode</dt>
                            <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ ucfirst($salaryDetails['payment_method'] ?? '-') }}</dd>
                        </div>
                        {{-- Bank --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                            <dt class="text-gray-600 text-sm">Bank</dt>
                            <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $salaryDetails['bank_name'] ?? '-' }}</dd>
                        </div>
                        {{-- No. Rekening --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4">
                            <dt class="text-gray-600 text-sm">No. Rekening</dt>
                            <dd class="text-gray-900 font-medium sm:col-span-2 break-words">{{ $salaryDetails['bank_account'] ?? '-' }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-gray-500 text-sm">Belum ada data gaji</p>
                @endif
            </div>

        </div>
    </div>
</div>
