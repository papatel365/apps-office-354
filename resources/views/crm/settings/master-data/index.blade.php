@extends('layouts.tenant')

@section('title', 'Pengaturan')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .sidebar-item { transition: all 0.2s ease; border-left: 3px solid transparent; }
    .sidebar-item:hover { background-color: #f3f4f6; border-left-color: #818cf8; }
    .sidebar-item.active { background-color: #eef2ff; border-left-color: #4f46e5; color: #4f46e5; }
    .content-section { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .modal-backdrop { background-color: rgba(0, 0, 0, 0.3); }
    .modal-container { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .modal-content { background-color: white; border-radius: 0.75rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 32rem; width: 100%; }
    .spinner { border: 2px solid #f3f3f3; border-top: 2px solid #4f46e5; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
<div x-data="masterDataManager()" x-init="init()">

    <!-- Mobile Header -->
    <div class="lg:hidden fixed top-0 left-0 right-0 bg-white border-b border-gray-200 z-40 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <button @click="mobileMenuOpen = true" class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-lg font-semibold text-gray-800">Pengaturan</h1>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Overlay -->
    <div x-show="mobileMenuOpen" x-cloak @click="mobileMenuOpen = false" 
         class="fixed inset-0 z-40 bg-black/50 lg:hidden modal-backdrop"></div>

    <!-- Mobile Drawer -->
    <div x-show="mobileMenuOpen" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-xl lg:hidden"
         :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-database text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Pengaturan</h2>
                </div>
            </div>
            <button @click="mobileMenuOpen = false" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Back to CRM Button (Mobile Only) -->
        <div class="lg:hidden px-4 py-3 border-b border-gray-100">
            <button @click="goBackToCRM()"
                    class="w-full flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="fas fa-arrow-left w-5 mr-3 text-gray-500"></i>
                <span>Kembali ke Sidebar</span>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 space-y-1">
            @include('crm.settings.master-data.partials.sidebar-nav')
        </nav>
    </div>

    <!-- Desktop Sidebar -->
    <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r border-gray-200 pt-16 lg:pt-0">

        <div class="flex items-center px-4 py-4 border-b border-gray-200">
            <div class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-database text-white text-sm"></i>
            </div>

            <div>
                <h2 class="text-base font-bold text-gray-800">
                    Pengaturan Umum
                </h2>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 space-y-1">
            @include('crm.settings.master-data.partials.sidebar-nav')
        </nav>

    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-64 pt-16 lg:pt-0 overflow-y-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- General Section -->
            <div x-show="activeTab === 'general'" x-cloak class="content-section">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Pengaturan</h2>
                </div>

                <!-- Logo & Favicon Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-image text-indigo-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Logo & Favicon</h3>
                            <p class="text-sm text-gray-500">Upload logo dan favicon perusahaan</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-image mr-2"></i>Logo Perusahaan
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-indigo-400 transition-colors">
                                <!-- Preview -->
                                <div class="mb-4" x-show="companyInfo.logo_url">
                                    <div class="relative inline-block">
                                        <img :src="companyInfo.logo_url" alt="Logo Preview" class="max-h-24 mx-auto rounded-lg shadow-sm border border-gray-200">
                                        <button type="button" @click="removeLogo()" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                <p x-show="companyInfo.logo_url" class="text-xs text-gray-500 mb-3">Preview Logo</p>

                                <!-- Upload Area -->
                                <div x-show="!companyInfo.logo_url" class="py-4">
                                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2 block"></i>
                                    <p class="text-sm text-gray-500">Klik untuk upload logo</p>
                                </div>

                                <input type="file" name="logo" @change="previewLogo($event)" accept="image/*" class="hidden" id="logoInput">
                                <label for="logoInput" class="cursor-pointer inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                    <i class="fas fa-upload mr-2"></i>
                                    <span x-text="companyInfo.logo_url ? 'Ganti Logo' : 'Pilih Logo'"></span>
                                </label>
                                <p class="text-xs text-gray-400 mt-2">Format: PNG, JPG, SVG (Max 2MB)</p>
                                <input type="hidden" name="remove_logo" x-model="removeLogoFlag">
                            </div>
                        </div>

                        <!-- Favicon Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-globe mr-2"></i>Favicon Browser
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-indigo-400 transition-colors">
                                <!-- Preview -->
                                <div class="mb-4" x-show="companyInfo.favicon_url">
                                    <div class="flex items-center justify-center gap-4">
                                        <div class="relative">
                                            <img :src="companyInfo.favicon_url" alt="Favicon Preview" class="w-12 h-12 rounded-lg shadow-sm border border-gray-200 p-1 bg-white">
                                            <button type="button" @click="removeFavicon()" class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-xs text-gray-500">Favicon Preview</p>
                                            <p class="text-xs text-gray-400">Akan muncul di tab browser</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Upload Area -->
                                <div x-show="!companyInfo.favicon_url" class="py-4">
                                    <i class="fas fa-file-image text-4xl text-gray-400 mb-2 block"></i>
                                    <p class="text-sm text-gray-500">Upload favicon</p>
                                </div>

                                <input type="file" name="favicon" @change="previewFavicon($event)" accept="image/x-icon,image/png,image/svg+xml" class="hidden" id="faviconInput">
                                <label for="faviconInput" class="cursor-pointer inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                    <i class="fas fa-upload mr-2"></i>
                                    <span x-text="companyInfo.favicon_url ? 'Ganti Favicon' : 'Pilih Favicon'"></span>
                                </label>
                                <p class="text-xs text-gray-400 mt-2">Format: ICO, PNG, SVG (Max 512KB)</p>
                                <input type="hidden" name="remove_favicon" x-model="removeFaviconFlag">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200 flex justify-end">
                        <button type="button" @click="saveLogoFavicon()" class="inline-flex items-center px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                            <i class="fas fa-save mr-2"></i>
                            Simpan Logo & Favicon
                        </button>
                    </div>
                </div>

                <!-- Footer Settings Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-grip-lines text-gray-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Footer</h3>
                            <p class="text-sm text-gray-500">Atur teks footer yang ditampilkan di seluruh halaman Office 354</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teks Footer</label>
                            <textarea
                                x-model="companyInfo.footer_text"
                                rows="2"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="© 2026 Nama Perusahaan. Hak Cipta Dilindung."
                            ></textarea>
                            <p class="text-xs text-gray-400 mt-1">Teks ini akan muncul di bagian bawah setiap halaman Office 354.</p>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200 flex justify-end">
                        <button type="button" @click="saveFooter()" class="inline-flex items-center px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                            <i class="fas fa-save mr-2"></i>
                            Simpan Footer
                        </button>
                    </div>
                </div>

            </div>

            <!-- Company Information Section -->
            <div x-show="activeTab === 'company'" x-cloak class="content-section">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Informasi Perusahaan</h2>
                    <p class="text-gray-500 mt-1">Kelola informasi identitas perusahaan</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <form @submit.prevent="saveCompanyInfo()">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Perusahaan</label>
                                <input type="text" x-model="companyInfo.name" 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alias</label>
                                <input type="text" x-model="companyInfo.alias" placeholder="Nama singkat"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" x-model="companyInfo.email" 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telepon</label>
                                <input type="text" x-model="companyInfo.phone" 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                                <input type="url" x-model="companyInfo.website" placeholder="https://"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">NPWP</label>
                                <input type="text" x-model="companyInfo.npwp" placeholder="00.000.000.0-000.000"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                                <textarea x-model="companyInfo.address" rows="3" 
                                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" 
                                    class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center">
                                <i class="fas fa-save mr-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Departments Section -->
            <div x-show="activeTab === 'departments'" x-cloak class="content-section">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Departemen</h2>
                        <p class="text-gray-500 mt-1">Kelola departemen perusahaan</p>
                    </div>
                    <button @click="openDepartmentModal()" 
                            class="mt-4 sm:mt-0 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>Tambah Departemen
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" x-model="departmentSearch" @input="filterDepartments()" 
                                       placeholder="Cari departemen..." 
                                       class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <select x-model="departmentStatusFilter" @change="filterDepartments()" 
                                class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jumlah Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="dept in filteredDepartments" :key="dept.id">
                                    <tr class="table-row-hover">
                                        <td class="px-6 py-4">
                                            <span class="font-medium text-gray-900" x-text="dept.name"></span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600" x-text="dept.code || '-'"></td>
                                        <td class="px-6 py-4 text-gray-600" x-text="dept.employee_count || 0"></td>
                                        <td class="px-6 py-4">
                                            <span :class="dept.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                                  class="px-2.5 py-1 text-xs font-medium rounded-full"
                                                  x-text="dept.is_active ? 'Aktif' : 'Tidak Aktif'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="editDepartment(dept)"
                                                    class="text-indigo-600 hover:text-indigo-900 mr-3 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button @click="deleteDepartment(dept.id)"
                                                    class="text-red-600 hover:text-red-900 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="filteredDepartments.length === 0">
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3"></i>
                                            <p>Tidak ada data departemen</p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Divisions Section -->
            <div x-show="activeTab === 'divisions'" x-cloak class="content-section">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Divisi</h2>
                        <p class="text-gray-500 mt-1">Kelola divisi dalam departemen</p>
                    </div>
                    <button @click="openDivisionModal()" 
                            class="mt-4 sm:mt-0 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>Tambah Divisi
                    </button>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" x-model="divisionSearch" @input="filterDivisions()" 
                                       placeholder="Cari divisi..." 
                                       class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <select x-model="divisionDepartmentFilter" @change="filterDivisions()" 
                                class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua Departemen</option>
                            <template x-for="dept in departments" :key="dept.id">
                                <option :value="dept.id" x-text="dept.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Departemen</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="div in filteredDivisions" :key="div.id">
                                    <tr class="table-row-hover">
                                        <td class="px-6 py-4">
                                            <span class="font-medium text-gray-900" x-text="div.name"></span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600" x-text="div.code || '-'"></td>
                                        <td class="px-6 py-4 text-gray-600" x-text="div.department_name || '-'"></td>
                                        <td class="px-6 py-4 text-gray-600 text-sm" x-text="div.description || '-'"></td>
                                        <td class="px-6 py-4">
                                            <span :class="div.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'" 
                                                  class="px-2.5 py-1 text-xs font-medium rounded-full" 
                                                  x-text="div.is_active ? 'Aktif' : 'Tidak Aktif'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="editDivision(div)" 
                                                    class="text-indigo-600 hover:text-indigo-900 mr-3 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button @click="deleteDivision(div.id)" 
                                                    class="text-red-600 hover:text-red-900 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="filteredDivisions.length === 0">
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3"></i>
                                            <p>Tidak ada data divisi</p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Positions Section -->
            <div x-show="activeTab === 'positions'" x-cloak class="content-section">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Posisi / Jabatan</h2>
                        <p class="text-gray-500 mt-1">Kelola posisi dan jabatan dalam perusahaan</p>
                    </div>
                    <button @click="openPositionModal()" 
                            class="mt-4 sm:mt-0 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>Tambah Posisi
                    </button>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" x-model="positionSearch" @input="filterPositions()" 
                                       placeholder="Cari posisi..." 
                                       class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <select x-model="positionLevelFilter" @change="filterPositions()"
                                class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua Level</option>
                            <option value="Director">Director</option>
                            <option value="General Manager">General Manager</option>
                            <option value="Head">Head</option>
                            <option value="Manager">Manager</option>
                            <option value="Coordinator">Coordinator</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Senior Staff">Senior Staff</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="pos in filteredPositions" :key="pos.id">
                                    <tr class="table-row-hover">
                                        <td class="px-6 py-4">
                                            <span class="font-medium text-gray-900" x-text="pos.name"></span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600" x-text="pos.code || '-'"></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800"
                                                  x-text="pos.level || '-'"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span :class="pos.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'" 
                                                  class="px-2.5 py-1 text-xs font-medium rounded-full" 
                                                  x-text="pos.is_active ? 'Aktif' : 'Tidak Aktif'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="editPosition(pos)" 
                                                    class="text-indigo-600 hover:text-indigo-900 mr-3 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button @click="deletePosition(pos.id)" 
                                                    class="text-red-600 hover:text-red-900 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="filteredPositions.length === 0">
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3"></i>
                                            <p>Tidak ada data posisi</p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Employee Types Section -->
            <div x-show="activeTab === 'employee-types'" x-cloak class="content-section">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Status Karyawan</h2>
                        <p class="text-gray-500 mt-1">Kelola tipe dan status kepegawaian</p>
                    </div>
                    <button @click="openEmployeeTypeModal()" 
                            class="mt-4 sm:mt-0 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>Tambah Status
                    </button>
                </div>

                <!-- Search -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" x-model="employeeTypeSearch" @input="filterEmployeeTypes()" 
                               placeholder="Cari status karyawan..." 
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="type in filteredEmployeeTypes" :key="type.id">
                                    <tr class="table-row-hover">
                                        <td class="px-6 py-4">
                                            <span class="font-medium text-gray-900" x-text="type.name"></span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600" x-text="type.code || '-'"></td>
                                        <td class="px-6 py-4">
                                            <span :class="type.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                                  class="px-2.5 py-1 text-xs font-medium rounded-full"
                                                  x-text="type.is_active ? 'Aktif' : 'Tidak Aktif'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="editEmployeeType(type)"
                                                    class="text-indigo-600 hover:text-indigo-900 mr-3 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button @click="deleteEmployeeType(type.id)"
                                                    class="text-red-600 hover:text-red-900 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="filteredEmployeeTypes.length === 0">
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3"></i>
                                            <p>Tidak ada data status karyawan</p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Locations Section -->
            <div x-show="activeTab === 'locations'" x-cloak class="content-section">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Lokasi Penempatan</h2>
                        <p class="text-gray-500 mt-1">Kelola lokasi kerja karyawan</p>
                    </div>
                    <button @click="openLocationModal()" 
                            class="mt-4 sm:mt-0 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>Tambah Lokasi
                    </button>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" x-model="locationSearch" @input="filterLocations()" 
                                       placeholder="Cari lokasi..." 
                                       class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <select x-model="locationTypeFilter" @change="filterLocations()" 
                                class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua Tipe</option>
                            <option value="kantor_pusat">Kantor Pusat</option>
                            <option value="cabang">Cabang</option>
                            <option value="site">Site</option>
                            <option value="gudang">Gudang</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tipe</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kota</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jumlah Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="loc in filteredLocations" :key="loc.id">
                                    <tr class="table-row-hover">
                                        <td class="px-6 py-4">
                                            <span class="font-medium text-gray-900" x-text="loc.name"></span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600" x-text="loc.code || '-'"></td>
                                        <td class="px-6 py-4">
                                            <span :class="{
                                                'bg-blue-100 text-blue-800': loc.location_type === 'office',
                                                'bg-orange-100 text-orange-800': loc.location_type === 'warehouse',
                                                'bg-red-100 text-red-800': loc.location_type === 'factory',
                                                'bg-purple-100 text-purple-800': loc.location_type === 'remote'
                                            }" class="px-2.5 py-1 text-xs font-medium rounded-full capitalize" 
                                                  x-text="loc.location_type"></span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600" x-text="loc.city || '-'"></td>
                                        <td class="px-6 py-4 text-gray-600" x-text="loc.employee_count || 0"></td>
                                        <td class="px-6 py-4">
                                            <span :class="loc.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'" 
                                                  class="px-2.5 py-1 text-xs font-medium rounded-full" 
                                                  x-text="loc.is_active ? 'Aktif' : 'Tidak Aktif'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="editLocation(loc)" 
                                                    class="text-indigo-600 hover:text-indigo-900 mr-3 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button @click="deleteLocation(loc.id)" 
                                                    class="text-red-600 hover:text-red-900 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="filteredLocations.length === 0">
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3"></i>
                                            <p>Tidak ada data lokasi</p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>


    
    <!-- Modals -->
    <!-- Department Modal -->
    <div x-show="departmentModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/25 backdrop-blur-md">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white rounded-t-2xl z-10 border-b border-gray-100">
                <div class="flex items-center justify-between px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editingDepartment ? 'Edit Departemen' : 'Tambah Departemen'"></h3>
                    <button @click="departmentModalOpen = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-1 transition-colors"><i class="fas fa-times text-lg"></i></button>
                </div>
            </div>
            <form @submit.prevent="saveDepartment()" class="p-6 space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label><input type="text" x-model="departmentForm.name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Kode</label><input type="text" x-model="departmentForm.code" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label><textarea x-model="departmentForm.description" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></textarea></div>
                <div class="flex items-center"><input type="checkbox" x-model="departmentForm.is_active" id="dept_active" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"><label for="dept_active" class="ml-2 text-sm text-gray-700">Aktif</label></div>
                <div class="flex justify-end gap-3 pt-4"><button type="button" @click="departmentModalOpen = false" class="px-4 py-2.5 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">Batal</button><button type="submit" class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Simpan</button></div>
            </form>
        </div>
    </div>

    <!-- Division Modal -->
    <div x-show="divisionModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/25 backdrop-blur-md">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white rounded-t-2xl z-10 border-b border-gray-100">
                <div class="flex items-center justify-between px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editingDivision ? 'Edit Divisi' : 'Tambah Divisi'"></h3>
                    <button @click="divisionModalOpen = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-1 transition-colors"><i class="fas fa-times text-lg"></i></button>
                </div>
            </div>
            <form @submit.prevent="saveDivision()" class="p-6 space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label><input type="text" x-model="divisionForm.name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Kode</label><input type="text" x-model="divisionForm.code" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Departemen *</label><select x-model="divisionForm.department_id" required class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900"><option value="">Pilih Departemen</option><template x-for="dept in departments" :key="dept.id"><option :value="dept.id" x-text="dept.name"></option></template></select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label><textarea x-model="divisionForm.description" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></textarea></div>
                <div class="flex items-center"><input type="checkbox" x-model="divisionForm.is_active" id="div_active" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"><label for="div_active" class="ml-2 text-sm text-gray-700">Aktif</label></div>
                <div class="flex justify-end gap-3 pt-4"><button type="button" @click="divisionModalOpen = false" class="px-4 py-2.5 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">Batal</button><button type="submit" class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Simpan</button></div>
            </form>
        </div>
    </div>

    <!-- Position Modal -->
    <div x-show="positionModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/25 backdrop-blur-md">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white rounded-t-2xl z-10 border-b border-gray-100">
                <div class="flex items-center justify-between px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editingPosition ? 'Edit Posisi' : 'Tambah Posisi'"></h3>
                    <button @click="positionModalOpen = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-1 transition-colors"><i class="fas fa-times text-lg"></i></button>
                </div>
            </div>
            <form @submit.prevent="savePosition()" class="p-6 space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label><input type="text" x-model="positionForm.name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Kode</label><input type="text" x-model="positionForm.code" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Level</label><select x-model="positionForm.level" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900"><option value="">Pilih Level</option><option value="Director">Director</option><option value="General Manager">General Manager</option><option value="Head">Head</option><option value="Manager">Manager</option><option value="Coordinator">Coordinator</option><option value="Supervisor">Supervisor</option><option value="Senior Staff">Senior Staff</option><option value="Staff">Staff</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label><textarea x-model="positionForm.description" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></textarea></div>
                <div class="flex items-center"><input type="checkbox" x-model="positionForm.is_active" id="pos_active" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"><label for="pos_active" class="ml-2 text-sm text-gray-700">Aktif</label></div>
                <div class="flex justify-end gap-3 pt-4"><button type="button" @click="positionModalOpen = false" class="px-4 py-2.5 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">Batal</button><button type="submit" class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Simpan</button></div>
            </form>
        </div>
    </div>

    <!-- Employee Type Modal -->
    <div x-show="employeeTypeModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/25 backdrop-blur-md">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white rounded-t-2xl z-10 border-b border-gray-100">
                <div class="flex items-center justify-between px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editingEmployeeType ? 'Edit Status' : 'Tambah Status'"></h3>
                    <button @click="employeeTypeModalOpen = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-1 transition-colors"><i class="fas fa-times text-lg"></i></button>
                </div>
            </div>
            <form @submit.prevent="saveEmployeeType()" class="p-6 space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label><input type="text" x-model="employeeTypeForm.name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Kode</label><input type="text" x-model="employeeTypeForm.code" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label><textarea x-model="employeeTypeForm.description" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></textarea></div>
                <div class="flex items-center"><input type="checkbox" x-model="employeeTypeForm.is_active" id="etype_active" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"><label for="etype_active" class="ml-2 text-sm text-gray-700">Aktif</label></div>
                <div class="flex justify-end gap-3 pt-4"><button type="button" @click="employeeTypeModalOpen = false" class="px-4 py-2.5 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">Batal</button><button type="submit" class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Simpan</button></div>
            </form>
        </div>
    </div>

    <!-- Location Modal -->
    <div x-show="locationModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/25 backdrop-blur-md">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white rounded-t-2xl z-10 border-b border-gray-100">
                <div class="flex items-center justify-between px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editingLocation ? 'Edit Lokasi' : 'Tambah Lokasi'"></h3>
                    <button @click="locationModalOpen = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-1 transition-colors"><i class="fas fa-times text-lg"></i></button>
                </div>
            </div>
            <form @submit.prevent="saveLocation()" class="p-6 space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label><input type="text" x-model="locationForm.name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Kode</label><input type="text" x-model="locationForm.code" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Tipe Lokasi *</label><select x-model="locationForm.type" required class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900"><option value="kantor_pusat">Kantor Pusat</option><option value="cabang">Cabang</option><option value="site">Site</option><option value="gudang">Gudang</option></select></div>
                <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">Kota</label><input type="text" x-model="locationForm.city" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label><input type="text" x-model="locationForm.province" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Kode Pos</label><input type="text" x-model="locationForm.postal_code" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label><textarea x-model="locationForm.address" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400"></textarea></div>
                <div class="flex items-center"><input type="checkbox" x-model="locationForm.is_active" id="loc_active" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"><label for="loc_active" class="ml-2 text-sm text-gray-700">Aktif</label></div>
                <div class="flex justify-end gap-3 pt-4"><button type="button" @click="locationModalOpen = false" class="px-4 py-2.5 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">Batal</button><button type="submit" class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Simpan</button></div>
            </form>
        </div>
    </div>

<!-- Toast Notification -->


<div x-show="toast.show" x-cloak
     :class="toast.type === 'success' ? 'bg-green-500' : toast.type === 'error' ? 'bg-red-500' : 'bg-indigo-500'"
     class="fixed bottom-4 right-4 z-50 px-6 py-3 rounded-lg text-white shadow-lg toast flex items-center">
    <i :class="toast.type === 'success' ? 'fas fa-check-circle' : toast.type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle'" class="mr-3"></i>
    <span x-text="toast.message"></span>
</div>

</div><!-- End of Alpine component x-data="masterDataManager()" -->

@endsection

@push('scripts')
<script>
function masterDataManager() {
    return {
        activeTab: 'general',
        mobileMenuOpen: false,
        
        // General Settings - placeholder only (no API endpoint available)
        generalSettings: {
            app_name: 'Office 354 System',
            app_version: '2.0.0',
            timezone: 'Asia/Jakarta',
            locale: 'id'
        },
        
        // Company Info
        companyInfo: {
            name: '',
            alias: '',
            email: '',
            phone: '',
            website: '',
            npwp: '',
            address: '',
            logo_url: '',
            favicon_url: '',
            footer_text: ''
        },
        removeLogoFlag: '0',
        removeFaviconFlag: '0',
        logoPreview: null,
        faviconPreview: null,

        // Departments
        departments: [],
        filteredDepartments: [],
        departmentSearch: '',
        departmentStatusFilter: '',
        departmentModalOpen: false,
        editingDepartment: null,
        departmentForm: {
            name: '',
            code: '',
            description: '',
            is_active: true
        },
        
        // Divisions
        divisions: [],
        filteredDivisions: [],
        divisionSearch: '',
        divisionDepartmentFilter: '',
        divisionModalOpen: false,
        editingDivision: null,
        divisionForm: {
            name: '',
            code: '',
            department_id: '',
            description: '',
            is_active: true
        },
        
        // Positions
        positions: [],
        filteredPositions: [],
        positionSearch: '',
        positionLevelFilter: '',
        positionModalOpen: false,
        editingPosition: null,
        positionForm: {
            name: '',
            code: '',
            level: '',
            description: '',
            is_active: true
        },
        
        // Employee Types
        employeeTypes: [],
        filteredEmployeeTypes: [],
        employeeTypeSearch: '',
        employeeTypeModalOpen: false,
        editingEmployeeType: null,
        employeeTypeForm: {
            name: '',
            code: '',
            description: '',
            is_active: true
        },
        
        // Locations
        locations: [],
        filteredLocations: [],
        locationSearch: '',
        locationTypeFilter: '',
        locationModalOpen: false,
        editingLocation: null,
        locationForm: {
            name: '',
            code: '',
            type: 'kantor_pusat',
            address: ''
        },
        
        // Toast
        toast: {
            show: false,
            message: '',
            type: 'success'
        },
        
        init() {
            this.fetchDepartments();
            this.fetchDivisions();
            this.fetchPositions();
            this.fetchEmployeeTypes();
            this.fetchLocations();
            this.fetchCompanyInfo();
            this.fetchGeneralSettings();
        },

        setActiveTab(tab) {
            this.activeTab = tab;
            this.mobileMenuOpen = false;
        },

        /**
         * Go back to CRM main sidebar
         * - Closes Master Data mobile drawer
         * - Opens CRM main sidebar
         * - Does NOT change URL or reload page
         */
        goBackToCRM() {
            // Close Master Data mobile drawer
            this.mobileMenuOpen = false;

            // Open CRM main sidebar
            if (typeof Alpine !== 'undefined' && Alpine.store('sidebar')) {
                Alpine.store('sidebar').mobileOpen = true;
            }
        },
        
        showToast(message, type = 'success') {
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
        },

        formatCurrency(value) {
            if (!value) return '-';
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
        },
        
        async fetchDepartments() {
            try {
                const response = await fetch('/api/master-data/data?type=departments&per_page=100');
                if (response.ok) {
                    const data = await response.json();
                    this.departments = data.data || [];
                    this.filterDepartments();
                }
            } catch (error) {
                console.error('Error fetching departments:', error);
            }
        },
        
        filterDepartments() {
            this.filteredDepartments = this.departments.filter(d => {
                const searchMatch = !this.departmentSearch || 
                    d.name.toLowerCase().includes(this.departmentSearch.toLowerCase()) ||
                    (d.code && d.code.toLowerCase().includes(this.departmentSearch.toLowerCase()));
                const statusMatch = !this.departmentStatusFilter || 
                    (this.departmentStatusFilter === 'active' && d.is_active) ||
                    (this.departmentStatusFilter === 'inactive' && !d.is_active);
                return searchMatch && statusMatch;
            });
        },
        
        openDepartmentModal(dept = null) {
            this.editingDepartment = dept;
            this.departmentForm = dept ? Object.assign({}, dept) : {
                name: '', code: '', description: '', is_active: true
            };
            this.departmentModalOpen = true;
        },
        
        editDepartment(dept) {
            this.openDepartmentModal(dept);
        },
        
        async saveDepartment() {
            try {
                const method = this.editingDepartment ? 'PUT' : 'POST';
                const url = this.editingDepartment ?
                    '/api/master-data/departments/' + this.editingDepartment.id :
                    '/api/master-data/departments';
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.departmentForm)
                });
                const data = await response.json();
                if (response.ok) {
                    this.departmentModalOpen = false;
                    await this.fetchDepartments();
                    this.showToast(data.message || 'Departemen berhasil disimpan');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menyimpan departemen'), 'error');
                }
            } catch (error) {
                console.error('Error saving department:', error);
                this.showToast('Terjadi kesalahan saat menyimpan', 'error');
            }
        },
        
        async deleteDepartment(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus departemen ini')) return;
            try {
                const response = await fetch('/api/master-data/departments/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    await this.fetchDepartments();
                    this.showToast(data.message || 'Departemen berhasil dihapus');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menghapus departemen'), 'error');
                }
            } catch (error) {
                console.error('Error deleting department:', error);
                this.showToast('Terjadi kesalahan saat menghapus', 'error');
            }
        },

        async toggleDepartment(id) {
            try {
                const response = await fetch('/api/master-data/departments/' + id + '/toggle', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    await this.fetchDepartments();
                    this.showToast(data.message || 'Status departemen berhasil diperbarui');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal memperbarui status'), 'error');
                }
            } catch (error) {
                console.error('Error toggling department:', error);
                this.showToast('Terjadi kesalahan saat memperbarui status', 'error');
            }
        },
        
        async fetchDivisions() {
            try {
                const response = await fetch('/api/master-data/data?type=divisions&per_page=100');
                if (response.ok) {
                    const data = await response.json();
                    this.divisions = data.data || [];
                    this.filterDivisions();
                }
            } catch (error) {
                console.error('Error fetching divisions:', error);
            }
        },
        
        filterDivisions() {
            this.filteredDivisions = this.divisions.filter(d => {
                const searchMatch = !this.divisionSearch || 
                    d.name.toLowerCase().includes(this.divisionSearch.toLowerCase()) ||
                    (d.code && d.code.toLowerCase().includes(this.divisionSearch.toLowerCase()));
                const deptMatch = !this.divisionDepartmentFilter || d.department_id == this.divisionDepartmentFilter;
                return searchMatch && deptMatch;
            });
        },
        
        openDivisionModal(div = null) {
            this.editingDivision = div;
            this.divisionForm = div ? Object.assign({}, div) : { 
                name: '', code: '', department_id: '', description: '', is_active: true 
            };
            this.divisionModalOpen = true;
        },
        
        editDivision(div) {
            this.openDivisionModal(div);
        },
        
        async saveDivision() {
            try {
                const method = this.editingDivision ? 'PUT' : 'POST';
                const url = this.editingDivision ?
                    '/api/master-data/divisions/' + this.editingDivision.id :
                    '/api/master-data/divisions';
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.divisionForm)
                });
                const data = await response.json();
                if (response.ok) {
                    this.divisionModalOpen = false;
                    await this.fetchDivisions();
                    this.showToast(data.message || 'Divisi berhasil disimpan');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menyimpan divisi'), 'error');
                }
            } catch (error) {
                console.error('Error saving division:', error);
                this.showToast('Terjadi kesalahan saat menyimpan', 'error');
            }
        },
        
        async deleteDivision(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus divisi ini')) return;
            try {
                const response = await fetch('/api/master-data/divisions/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    await this.fetchDivisions();
                    this.showToast(data.message || 'Divisi berhasil dihapus');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menghapus divisi'), 'error');
                }
            } catch (error) {
                console.error('Error deleting division:', error);
                this.showToast('Terjadi kesalahan saat menghapus', 'error');
            }
        },

        async toggleDivision(id) {
            try {
                const response = await fetch('/api/master-data/divisions/' + id + '/toggle', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    await this.fetchDivisions();
                    this.showToast(data.message || 'Status divisi berhasil diperbarui');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal memperbarui status'), 'error');
                }
            } catch (error) {
                console.error('Error toggling division:', error);
                this.showToast('Terjadi kesalahan saat memperbarui status', 'error');
            }
        },
        
        async fetchPositions() {
            try {
                const response = await fetch('/api/master-data/data?type=positions&per_page=100');
                if (response.ok) {
                    const data = await response.json();
                    this.positions = data.data || [];
                    this.filterPositions();
                }
            } catch (error) {
                console.error('Error fetching positions:', error);
            }
        },
        
        filterPositions() {
            this.filteredPositions = this.positions.filter(p => {
                const searchMatch = !this.positionSearch ||
                    p.name.toLowerCase().includes(this.positionSearch.toLowerCase());
                const levelMatch = !this.positionLevelFilter || (p.level && p.level.toLowerCase() === this.positionLevelFilter.toLowerCase());
                return searchMatch && levelMatch;
            });
        },
        
        openPositionModal(pos = null) {
            this.editingPosition = pos;
            this.positionForm = pos ? Object.assign({}, pos) : {
                name: '', code: '', level: '', description: '', is_active: true
            };
            this.positionModalOpen = true;
        },
        
        editPosition(pos) {
            this.openPositionModal(pos);
        },
        
        async savePosition() {
            try {
                const method = this.editingPosition ? 'PUT' : 'POST';
                const url = this.editingPosition ?
                    '/api/master-data/positions/' + this.editingPosition.id :
                    '/api/master-data/positions';
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.positionForm)
                });
                const data = await response.json();
                if (response.ok) {
                    this.positionModalOpen = false;
                    await this.fetchPositions();
                    this.showToast(data.message || 'Posisi berhasil disimpan');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menyimpan posisi'), 'error');
                }
            } catch (error) {
                console.error('Error saving position:', error);
                this.showToast('Terjadi kesalahan saat menyimpan', 'error');
            }
        },

        async deletePosition(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus posisi ini')) return;
            try {
                const response = await fetch('/api/master-data/positions/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    await this.fetchPositions();
                    this.showToast(data.message || 'Posisi berhasil dihapus');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menghapus posisi'), 'error');
                }
            } catch (error) {
                console.error('Error deleting position:', error);
                this.showToast('Terjadi kesalahan saat menghapus', 'error');
            }
        },

        async togglePosition(id) {
            try {
                const response = await fetch('/api/master-data/positions/' + id + '/toggle', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    await this.fetchPositions();
                    this.showToast(data.message || 'Status posisi berhasil diperbarui');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal memperbarui status'), 'error');
                }
            } catch (error) {
                console.error('Error toggling position:', error);
                this.showToast('Terjadi kesalahan saat memperbarui status', 'error');
            }
        },
        
        async fetchEmployeeTypes() {
            try {
                const response = await fetch('/api/master-data/data?type=employee-types&per_page=100');
                if (response.ok) {
                    const data = await response.json();
                    this.employeeTypes = data.data || [];
                    this.filterEmployeeTypes();
                }
            } catch (error) {
                console.error('Error fetching employee types:', error);
            }
        },
        
        filterEmployeeTypes() {
            this.filteredEmployeeTypes = this.employeeTypes.filter(t => 
                !this.employeeTypeSearch || 
                t.name.toLowerCase().includes(this.employeeTypeSearch.toLowerCase())
            );
        },
        
        openEmployeeTypeModal(type = null) {
            this.editingEmployeeType = type;
            this.employeeTypeForm = type ? Object.assign({}, type) : {
                name: '', code: '', description: '', is_active: true
            };
            this.employeeTypeModalOpen = true;
        },
        
        editEmployeeType(type) {
            this.openEmployeeTypeModal(type);
        },
        
        async saveEmployeeType() {
            try {
                const method = this.editingEmployeeType ? 'PUT' : 'POST';
                const url = this.editingEmployeeType ?
                    '/api/master-data/employee-types/' + this.editingEmployeeType.id :
                    '/api/master-data/employee-types';
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.employeeTypeForm)
                });
                const data = await response.json();
                if (response.ok) {
                    this.employeeTypeModalOpen = false;
                    await this.fetchEmployeeTypes();
                    this.showToast(data.message || 'Status karyawan berhasil disimpan');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menyimpan status karyawan'), 'error');
                }
            } catch (error) {
                console.error('Error saving employee type:', error);
                this.showToast('Terjadi kesalahan saat menyimpan', 'error');
            }
        },

        async deleteEmployeeType(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus status ini')) return;
            try {
                const response = await fetch('/api/master-data/employee-types/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    await this.fetchEmployeeTypes();
                    this.showToast(data.message || 'Status karyawan berhasil dihapus');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menghapus status karyawan'), 'error');
                }
            } catch (error) {
                console.error('Error deleting employee type:', error);
                this.showToast('Terjadi kesalahan saat menghapus', 'error');
            }
        },

        async toggleEmployeeType(id) {
            try {
                const response = await fetch('/api/master-data/employee-types/' + id + '/toggle', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    await this.fetchEmployeeTypes();
                    this.showToast(data.message || 'Status karyawan berhasil diperbarui');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal memperbarui status'), 'error');
                }
            } catch (error) {
                console.error('Error toggling employee type:', error);
                this.showToast('Terjadi kesalahan saat memperbarui status', 'error');
            }
        },
        
        async fetchLocations() {
            try {
                const response = await fetch('/api/master-data/data?type=locations&per_page=100');
                if (response.ok) {
                    const data = await response.json();
                    this.locations = data.data || [];
                    this.filterLocations();
                }
            } catch (error) {
                console.error('Error fetching locations:', error);
            }
        },
        
        filterLocations() {
            this.filteredLocations = this.locations.filter(l => {
                const searchMatch = !this.locationSearch || 
                    l.name.toLowerCase().includes(this.locationSearch.toLowerCase());
                const typeMatch = !this.locationTypeFilter || l.location_type === this.locationTypeFilter;
                return searchMatch && typeMatch;
            });
        },
        
        openLocationModal(loc = null) {
            this.editingLocation = loc;
            this.locationForm = loc ? Object.assign({}, loc) : {
                name: '', code: '', type: 'kantor_pusat', address: ''
            };
            this.locationModalOpen = true;
        },
        
        editLocation(loc) {
            this.openLocationModal(loc);
        },
        
        async saveLocation() {
            try {
                const method = this.editingLocation ? 'PUT' : 'POST';
                const url = this.editingLocation ? 
                    '/api/master-data/locations/' + this.editingLocation.id : 
                    '/api/master-data/locations';
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.locationForm)
                });
                const data = await response.json();
                if (response.ok) {
                    this.locationModalOpen = false;
                    await this.fetchLocations();
                    this.showToast(data.message || 'Lokasi berhasil disimpan');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menyimpan lokasi'), 'error');
                }
            } catch (error) {
                console.error('Error saving location:', error);
                this.showToast('Terjadi kesalahan saat menyimpan', 'error');
            }
        },

        async deleteLocation(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus lokasi ini')) return;
            try {
                const response = await fetch('/api/master-data/locations/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    await this.fetchLocations();
                    this.showToast(data.message || 'Lokasi berhasil dihapus');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menghapus lokasi'), 'error');
                }
            } catch (error) {
                console.error('Error deleting location:', error);
                this.showToast('Terjadi kesalahan saat menghapus', 'error');
            }
        },

        async toggleLocation(id) {
            try {
                const response = await fetch('/api/master-data/locations/' + id + '/toggle', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    await this.fetchLocations();
                    this.showToast(data.message || 'Status lokasi berhasil diperbarui');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal memperbarui status'), 'error');
                }
            } catch (error) {
                console.error('Error toggling location:', error);
                this.showToast('Terjadi kesalahan saat memperbarui status', 'error');
            }
        },
        
        async fetchCompanyInfo() {
            try {
                const response = await fetch('/api/company/current');
                if (response.ok) {
                    const data = await response.json();
                    if (data.company) {
                        this.companyInfo = Object.assign(this.companyInfo, data.company);
                    }
                }
            } catch (error) {
                console.error('Error fetching company info:', error);
            }
        },
        
        async saveCompanyInfo() {
            try {
                const response = await fetch('/api/company/update-identity', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.companyInfo)
                });
                const data = await response.json();
                if (response.ok) {
                    this.showToast(data.message || 'Informasi perusahaan berhasil disimpan');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menyimpan informasi perusahaan'), 'error');
                }
            } catch (error) {
                console.error('Error saving company info:', error);
                this.showToast('Terjadi kesalahan saat menyimpan', 'error');
            }
        },

        async saveFooter() {
            try {
                const response = await fetch('/api/company/update-identity', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        footer_text: this.companyInfo.footer_text
                    })
                });
                const data = await response.json();
                if (response.ok) {
                    this.showToast(data.message || 'Footer berhasil disimpan');
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menyimpan footer'), 'error');
                }
            } catch (error) {
                console.error('Error saving footer:', error);
                this.showToast('Terjadi kesalahan saat menyimpan', 'error');
            }
        },

        // General settings placeholder - no API endpoint
        fetchGeneralSettings() {
            // Placeholder - no API endpoint available
            console.log('General settings placeholder active');
        },

        previewLogo(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    this.showToast('Ukuran file logo maksimal 2MB', 'error');
                    event.target.value = '';
                    return;
                }
                this.logoPreview = URL.createObjectURL(file);
                this.removeLogoFlag = '0';
                this.companyInfo.logo_url = this.logoPreview;
            }
        },

        previewFavicon(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 512 * 1024) {
                    this.showToast('Ukuran file favicon maksimal 512KB', 'error');
                    event.target.value = '';
                    return;
                }
                this.faviconPreview = URL.createObjectURL(file);
                this.removeFaviconFlag = '0';
                this.companyInfo.favicon_url = this.faviconPreview;
            }
        },

        removeLogo() {
            this.companyInfo.logo_url = '';
            this.logoPreview = null;
            this.removeLogoFlag = '1';
            document.getElementById('logoInput').value = '';
        },

        removeFavicon() {
            this.companyInfo.favicon_url = '';
            this.faviconPreview = null;
            this.removeFaviconFlag = '1';
            document.getElementById('faviconInput').value = '';
        },

        async saveLogoFavicon() {
            try {
                const formData = new FormData();

                const logoInput = document.getElementById('logoInput');
                const faviconInput = document.getElementById('faviconInput');

                if (logoInput.files[0]) {
                    formData.append('logo', logoInput.files[0]);
                }
                if (faviconInput.files[0]) {
                    formData.append('favicon', faviconInput.files[0]);
                }
                formData.append('remove_logo', this.removeLogoFlag);
                formData.append('remove_favicon', this.removeFaviconFlag);

                const response = await fetch('/api/company/update-identity', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message || 'Logo & Favicon berhasil disimpan');
                    this.logoPreview = null;
                    this.faviconPreview = null;
                    this.removeLogoFlag = '0';
                    this.removeFaviconFlag = '0';
                    // Update with actual URLs from server (persistent storage URLs)
                    if (data.logo_url) this.companyInfo.logo_url = data.logo_url;
                    if (data.favicon_url) this.companyInfo.favicon_url = data.favicon_url;
                    // If logo was removed, clear the preview
                    if (this.removeLogoFlag === '1' || (this.removeLogoFlag === '0' && !this.logoPreview)) {
                        // Keep the companyInfo.logo_url from server response
                    }
                    logoInput.value = '';
                    faviconInput.value = '';
                    // Reload company info to ensure we have the latest data
                    await this.fetchCompanyInfo();
                } else {
                    this.showToast(extractErrorMessage(data, 'Gagal menyimpan logo & favicon'), 'error');
                }
            } catch (error) {
                console.error('Error saving logo/favicon:', error);
                this.showToast('Terjadi kesalahan saat menyimpan', 'error');
            }
        }
    }
}
</script>
@endpush
