{{-- Tab Payroll --}}
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
            <i class="fa-solid fa-money-bills mr-2 text-indigo-600"></i>
            Informasi Gaji
        </h3>
        <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs rounded-full">
            <i class="fa-solid fa-lock mr-1"></i>Data Sensitif
        </span>
    </div>

    @if($salaryDetails)
        {{-- Current Salary Configuration --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Gaji Pokok --}}
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white shadow-lg hover:shadow-xl transition-all duration-300">
        <p class="text-green-100 text-sm font-medium">Gaji Pokok (Rp)</p>
        <p class="text-2xl font-bold mt-1">
            {{ number_format($salaryDetails['basic_salary'] ?? 0, 0, ',', '.') }}
        </p>
    </div>

    {{-- Total Tunjangan --}}
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white shadow-lg hover:shadow-xl transition-all duration-300">
        <p class="text-blue-100 text-sm font-medium">Total Tunjangan (Rp)</p>
        <p class="text-2xl font-bold mt-1">
            {{ number_format($salaryDetails['total_allowances'] ?? 0, 0, ',', '.') }}
        </p>
    </div>

    {{-- Total Potongan --}}
    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-5 text-white shadow-lg hover:shadow-xl transition-all duration-300">
        <p class="text-orange-100 text-sm font-medium">Total Potongan (Rp)</p>
        <p class="text-2xl font-bold mt-1">
            {{ number_format($salaryDetails['total_deductions'] ?? 0, 0, ',', '.') }}
        </p>
    </div>

    {{-- Gaji Bersih --}}
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-5 text-white shadow-lg hover:shadow-xl transition-all duration-300">
        <p class="text-purple-100 text-sm font-medium">Gaji Bersih (Rp)</p>
        <p class="text-2xl font-bold mt-1">
            {{ number_format($salaryDetails['total_salary'] ?? 0, 0, ',', '.') }}
        </p>
    </div>

</div>

        {{-- Allowance Details --}}
        @if(count($salaryDetails['allowances'] ?? []) > 0)
        <div class="mb-6">
            <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3 flex items-center">
                <i class="fa-solid fa-plus-circle mr-2 text-green-500"></i>
                Tunjangan
            </h4>
            <div class="bg-green-50 rounded-lg border border-green-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-green-100">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-green-800">Nama</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-green-800">Jenis</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-green-800">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-green-100">
                        @foreach($salaryDetails['allowances'] as $allowance)
                        <?php
                            // Handle both array (from JSON) and object (from model)
                            $isObject = is_object($allowance);
                            $name = $isObject ? $allowance->name : ($allowance['name'] ?? '-');
                            $type = $isObject ? $allowance->calculation_type : ($allowance['calculation_type'] ?? 'fixed');
                            $amount = $isObject ? $allowance->amount : ($allowance['calculated_amount'] ?? $allowance['amount'] ?? 0);
                            $typeLabel = $type === 'percentage' ? 'Persentase' : 'Tetap';
                        ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600 text-right">{{ $typeLabel }}</td>
                            <td class="px-4 py-2 text-sm font-medium text-gray-900 text-right">
                                @if($type === 'percentage')
                                    {{ number_format($amount, 0, ',', '.') }}%
                                @else
                                    Rp {{ number_format($amount, 0, ',', '.') }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Deduction Details --}}
        @if(count($salaryDetails['deductions'] ?? []) > 0)
        <div class="mb-6">
            <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3 flex items-center">
                <i class="fa-solid fa-minus-circle mr-2 text-red-500"></i>
                Potongan
            </h4>
            <div class="bg-red-50 rounded-lg border border-red-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-red-100">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-red-800">Nama</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-red-800">Jenis</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-red-800">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-100">
                        @foreach($salaryDetails['deductions'] as $deduction)
                        <?php
                            // Handle both array (from JSON) and object (from model)
                            $isObject = is_object($deduction);
                            $name = $isObject ? $deduction->name : ($deduction['name'] ?? '-');
                            $type = $isObject ? $deduction->calculation_type : ($deduction['calculation_type'] ?? 'fixed');
                            $amount = $isObject ? $deduction->amount : ($deduction['calculated_amount'] ?? $deduction['amount'] ?? 0);
                            $typeLabel = $type === 'percentage' ? 'Persentase' : 'Tetap';
                        ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600 text-right">{{ $typeLabel }}</td>
                            <td class="px-4 py-2 text-sm font-medium text-gray-900 text-right">
                                @if($type === 'percentage')
                                    {{ number_format($amount, 0, ',', '.') }}%
                                @else
                                    Rp {{ number_format($amount, 0, ',', '.') }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Bank Information --}}
        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Informasi Bank</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm text-gray-500">Nama Bank</label>
                <p class="font-medium text-gray-900">{{ $salaryDetails['bank_name'] ?? '-' }}</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500">Nomor Rekening</label>
                <p class="font-medium text-gray-900">{{ $salaryDetails['bank_account'] ?? '-' }}</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500">Nama Pemilik</label>
                <p class="font-medium text-gray-900">{{ $salaryDetails['bank_account_name'] ?? '-' }}</p>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <i class="fa-solid fa-money-bills text-3xl text-gray-300"></i>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Data Gaji</h4>
            <p class="text-gray-500">Karyawan ini belum memiliki konfigurasi gaji.</p>
        </div>
    @endif
</div>
