{{-- resources/views/crm/settings/index.blade.php --}}
@extends('layouts.app')

@section('title', $isInitialSetup ? 'Setup Perusahaan' : 'Pengaturan CRM')

@section('page-title', $isInitialSetup ? 'Setup Informasi Perusahaan' : 'Pengaturan CRM')

@php
    $showInitialSetupBanner = $isInitialSetup ?? false;
@endphp

@section('content')
    <div class="max-w-4xl mx-auto">
        @if($showInitialSetupBanner)
        {{-- Initial Setup Banner --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl p-6 mb-6 text-white shadow-lg">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-building text-2xl"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold mb-1">Selamat Datang di Office 354!</h2>
                    <p class="text-blue-100 text-sm">Silakan lengkapi informasi perusahaan Anda untuk memulai.</p>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('settings.crm.update') }}" method="POST" id="settingsForm">
            @csrf

            <div class="bg-white rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $isInitialSetup ? 'Informasi Perusahaan' : 'Pengaturan CRM' }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $isInitialSetup ? 'Lengkapi informasi dasar perusahaan Anda' : 'Data perusahaan untuk invoice dan laporan' }}</p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        <i class="fa-solid fa-save mr-2"></i>{{ $isInitialSetup ? 'Simpan & Mulai' : 'Simpan' }}
                    </button>
                </div>

                <div class="p-6 space-y-8">
                    {{-- Company Information only --}}
                    <div>
                        <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fa-solid fa-building mr-2 text-blue-500"></i>
                            {{ $isInitialSetup ? 'Informasi Dasar Perusahaan' : 'Informasi Perusahaan' }}
                        </h4>
                        <div class="bg-gray-50 rounded-xl p-6 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Perusahaan <span class="text-red-500">*</span></label>
                                    <input type="text" name="company_name" id="company_name" required class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500" value="{{ old('company_name', $crmSettings['company_name']) }}">
                                </div>
                                <div>
                                    <label for="short_name" class="block text-sm font-medium text-gray-700 mb-1">Alias</label>
                                    <input type="text" name="short_name" id="short_name" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500" value="{{ old('short_name', $crmSettings['short_name'] ?? '') }}" placeholder="Nama singkat/alias">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="company_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" name="company_email" id="company_email" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500" value="{{ old('company_email', $crmSettings['company_email']) }}">
                                </div>
                                <div>
                                    <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                                    <input type="text" name="company_phone" id="company_phone" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500" value="{{ old('company_phone', $crmSettings['company_phone']) }}">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                    <input type="url" name="website" id="website" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500" value="{{ old('website', $crmSettings['website'] ?? '') }}" placeholder="https://example.com">
                                </div>
                                <div>
                                    <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-1">NPWP / Tax ID</label>
                                    <input type="text" name="tax_id" id="tax_id" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500" value="{{ old('tax_id', $crmSettings['tax_id']) }}" placeholder="01.234.567.8-123.000">
                                </div>
                            </div>
                            <div>
                                <label for="company_address" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                                <textarea name="company_address" id="company_address" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500">{{ old('company_address', $crmSettings['company_address']) }}</textarea>
                            </div>
                        </div>
                    </div>
                    @if(!$isInitialSetup)
                    {{-- Invoice Settings --}}
                    <div>
                        <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fa-solid fa-file-invoice-dollar mr-2 text-green-500"></i>
                            Pengaturan Invoice
                        </h4>
                        <div class="bg-gray-50 rounded-xl p-6 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="invoice_prefix" class="block text-sm font-medium text-gray-700 mb-1">Prefix Invoice</label>
                                    <input type="text" name="invoice_prefix" id="invoice_prefix" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500" value="{{ old('invoice_prefix', $crmSettings['invoice_prefix']) }}" placeholder="INV">
                                    <p class="text-xs text-gray-500 mt-1">Contoh: INV-2024-0001</p>
                                </div>
                                <div>
                                    <label for="default_payment_terms" class="block text-sm font-medium text-gray-700 mb-1">Jatuh Tempo (hari)</label>
                                    <input type="number" name="default_payment_terms" id="default_payment_terms" min="1" max="365" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500" value="{{ old('default_payment_terms', $crmSettings['default_payment_terms'] ?? 30) }}">
                                    <p class="text-xs text-gray-500 mt-1">Default 30 hari</p>
                                </div>
                                <div>
                                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Mata Uang</label>
                                    <select name="currency" id="currency" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="IDR" {{ old('currency', $crmSettings['currency']) == 'IDR' ? 'selected' : '' }}>IDR - Rupiah Indonesia</option>
                                        <option value="USD" {{ old('currency', $crmSettings['currency']) == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                        <option value="EUR" {{ old('currency', $crmSettings['currency']) == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                        <option value="SGD" {{ old('currency', $crmSettings['currency']) == 'SGD' ? 'selected' : '' }}>SGD - Singapore Dollar</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label for="invoice_footer" class="block text-sm font-medium text-gray-700 mb-1">Footer Invoice</label>
                                <textarea name="invoice_footer" id="invoice_footer" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500" placeholder="Syarat dan ketentuan, informasi bank, dll">{{ old('invoice_footer', $crmSettings['invoice_footer']) }}</textarea>
                                <p class="text-xs text-gray-500 mt-1">Teks yang akan muncul di bagian bawah invoice</p>
                            </div>
                        </div>
                    </div>

                                        {{-- Proposal Templates --}}
                    <div>
                        <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fa-solid fa-file-contract mr-2 text-indigo-500"></i>
                            Template Proposal
                        </h4>
                        <div class="bg-gray-50 rounded-xl p-6">
                            <p class="text-sm text-gray-600 mb-4">Kelola template untuk PDF proposal Anda dengan logo perusahaan, watermark, dan informasi perusahaan.</p>
                            <a href="{{ route('settings.proposal-templates.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                <i class="fa-solid fa-gear mr-2"></i>
                                Kelola Template Proposal
                            </a>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div>
                        <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fa-solid fa-eye mr-2 text-purple-500"></i>
                            Preview Invoice
                        </h4>
                        <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl p-6">
                            <div class="bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto">
                                <div class="flex justify-between items-start mb-6">
                                    <div>
                                        <h5 class="text-lg font-bold text-gray-900" id="previewCompanyName">{{ $crmSettings['company_name'] }}</h5>
                                        <p class="text-xs text-gray-500" id="previewCompanyAddress">{{ $crmSettings['company_address'] ?? 'Alamat perusahaan' }}</p>
                                        <p class="text-xs text-gray-500" id="previewCompanyEmail">{{ $crmSettings['company_email'] ?? 'email@perusahaan.com' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <h3 class="text-xl font-bold text-primary-600">INVOICE</h3>
                                        <p class="text-xs text-gray-500 font-mono">{{ $crmSettings['invoice_prefix'] ?? 'INV' }}-001</p>
                                    </div>
                                </div>
                                <div class="border-t border-gray-200 pt-4 mb-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Tanggal:</span>
                                        <span class="text-gray-900">{{ now()->format('d M Y') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm mt-1">
                                        <span class="text-gray-500">Jatuh Tempo:</span>
                                        <span class="text-gray-900">{{ now()->addDays((int) ($crmSettings['default_payment_terms'] ?? 30))->format('d M Y') }}</span>
                                    </div>
                                </div>
                                <table class="w-full text-sm mb-4">
                                    <thead>
                                        <tr class="border-b border-gray-200">
                                            <th class="text-left py-2 text-gray-500">Deskripsi</th>
                                            <th class="text-right py-2 text-gray-500">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b border-gray-100">
                                            <td class="py-2">Contoh Item</td>
                                            <td class="text-right py-2">Rp 1.000.000</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td class="py-2 text-right font-medium">Total:</td>
                                            <td class="text-right py-2 font-bold text-primary-600">Rp 1.000.000</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                @if($crmSettings['invoice_footer'])
                                    <div class="border-t border-gray-200 pt-4">
                                        <p class="text-xs text-gray-500 whitespace-pre-wrap">{{ $crmSettings['invoice_footer'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Footer Settings --}}
                    <div>
                        <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fa-solid fa-grip-lines mr-2 text-gray-500"></i>
                            Footer
                        </h4>
                        <div class="bg-gray-50 rounded-xl p-6 space-y-4">
                            <div>
                                <label for="footer_text" class="block text-sm font-medium text-gray-700 mb-1">Teks Footer</label>
                                <textarea
                                    name="footer_text"
                                    id="footer_text"
                                    rows="2"
                                    class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                                    placeholder="© 2026 Office 354. All rights reserved."
                                >{{ old('footer_text', $crmSettings['footer_text']) }}</textarea>
                                <p class="text-xs text-gray-500 mt-1">Teks footer yang akan ditampilkan di seluruh halaman CRM.</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        <i class="fa-solid fa-save mr-2"></i>{{ $isInitialSetup ? 'Simpan & Mulai' : 'Simpan Pengaturan' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@if(!$isInitialSetup)
<script>
// Live preview update - only for regular settings
document.getElementById('company_name').addEventListener('input', function() {
    document.getElementById('previewCompanyName').textContent = this.value || 'Nama Perusahaan';
});
document.getElementById('company_address').addEventListener('input', function() {
    document.getElementById('previewCompanyAddress').textContent = this.value || 'Alamat perusahaan';
});
document.getElementById('company_email').addEventListener('input', function() {
    document.getElementById('previewCompanyEmail').textContent = this.value || 'email@perusahaan.com';
});
</script>
@endif
@endpush
