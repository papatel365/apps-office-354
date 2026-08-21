{{-- Tab Personal Data --}}
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <i class="fa-solid fa-user mr-2 text-indigo-600"></i>
        Data Pribadi
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
            <label class="block text-sm text-gray-500">Nama Lengkap</label>
            <p class="font-medium text-gray-900">{{ $employee->full_name }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Email</label>
            <p class="font-medium text-gray-900">{{ $employee->user?->email ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">No. Telepon</label>
            <p class="font-medium text-gray-900">{{ $employee->phone ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">NIK / No. KTP</label>
            <p class="font-medium text-gray-900">{{ $employee->nik ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">NPWP</label>
            <p class="font-medium text-gray-900">{{ $employee->npwp_number ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">BPJS Kesehatan</label>
            <p class="font-medium text-gray-900">{{ $employee->bpjs_kesehatan ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">BPJS Ketenagakerjaan</label>
            <p class="font-medium text-gray-900">{{ $employee->bpjs_ketenagakerjaan ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Tempat, Tanggal Lahir</label>
            <p class="font-medium text-gray-900">
                {{ $employee->place_of_birth ?? '-' }}
                @if($employee->date_of_birth)
                    , {{ $employee->date_of_birth->format('d M Y') }}
                @endif
            </p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Jenis Kelamin</label>
            <p class="font-medium text-gray-900">
                @if($employee->gender === 'male')
                    Laki-laki
                @elseif($employee->gender === 'female')
                    Perempuan
                @else
                    -
                @endif
            </p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Agama</label>
            <p class="font-medium text-gray-900">{{ ucfirst($employee->religion ?? '-') }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Status Perkawinan</label>
            <p class="font-medium text-gray-900">
                @switch($employee->marital_status)
                    @case('single') Belum Menikah @break
                    @case('married') Menikah @break
                    @case('divorced') Cerai @break
                    @case('widowed') Duda/Janda @break
                    @default -
                @endswitch
            </p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Golongan Darah</label>
            <p class="font-medium text-gray-900">{{ $employee->blood_type ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm text-gray-500">Status Akun</label>
            <p class="font-medium">
                @if($employee->is_active)
                    <span class="text-green-600">Aktif</span>
                @else
                    <span class="text-red-600">Resign</span>
                @endif
            </p>
        </div>
    </div>

    <div class="mt-6">
        <label class="block text-sm text-gray-500 mb-1">Alamat</label>
        <p class="font-medium text-gray-900">
            {{ $employee->address ?? '-' }}
            @if($employee->city)
                <br>{{ $employee->city }}
            @endif
            @if($employee->province)
                , {{ $employee->province }}
            @endif
            @if($employee->postal_code)
                {{ $employee->postal_code }}
            @endif
        </p>
    </div>

    @if($employee->emergency_contact_name || $employee->emergency_contact_phone)
    <div class="mt-6 pt-6 border-t">
        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Kontak Darurat</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-500">Nama</label>
                <p class="font-medium text-gray-900">{{ $employee->emergency_contact_name ?? '-' }}</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500">No. Telepon</label>
                <p class="font-medium text-gray-900">{{ $employee->emergency_contact_phone ?? '-' }}</p>
            </div>
        </div>
    </div>
    @endif
</div>
