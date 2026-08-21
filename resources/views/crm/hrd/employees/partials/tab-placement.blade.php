{{-- Tab Placement --}}
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <i class="fa-solid fa-map-marker-alt mr-2 text-indigo-600"></i>
        Penempatan Staff
    </h3>

    @if($employee->placement)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-map-marker-alt text-indigo-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900">{{ $employee->placement->name }}</h4>
                    @if($employee->placement->code)
                    <p class="text-sm text-gray-500">{{ $employee->placement->code }}</p>
                    @endif
                </div>
            </div>

            @if($employee->placement->address)
            <div class="mt-4">
                <label class="block text-sm text-gray-500">Alamat</label>
                <p class="font-medium text-gray-900">{{ $employee->placement->address }}</p>
                @if($employee->placement->city)
                <p class="text-gray-600">{{ $employee->placement->city }}</p>
                @endif
            </div>
            @endif

            @if($employee->placement->latitude && $employee->placement->longitude)
            <div class="mt-4">
                <label class="block text-sm text-gray-500">Koordinat GPS</label>
                <p class="font-medium text-gray-900">
                    {{ $employee->placement->latitude }}, {{ $employee->placement->longitude }}
                </p>
            </div>
            @endif
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
            <i class="fa-solid fa-map-marker-alt text-3xl text-gray-300"></i>
        </div>
        <h4 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Penempatan</h4>
        <p class="text-gray-500 mb-4">Karyawan ini belum ditempatkan di lokasi manapun.</p>
    </div>
    @endif
</div>
