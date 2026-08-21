{{-- resources/views/components/dashboard/empty-state-widget.blade.php --}}
{{--
    Empty State Widget
    Shown when user only has 'dashboard' permission and no module access
--}}

<div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border border-gray-200 p-8 text-center">
    {{-- Icon --}}
    <div class="w-20 h-20 rounded-full bg-white border-4 border-gray-200 flex items-center justify-center mx-auto mb-6 shadow-sm">
        <i class="fa-solid fa-user-shield text-3xl text-gray-400"></i>
    </div>

    {{-- Title --}}
    <h2 class="text-xl font-bold text-gray-900 mb-2">Selamat Datang</h2>

    {{-- Message --}}
    <p class="text-gray-600 max-w-md mx-auto mb-6">
        akses anda terbatas. silahkan hubungi administrator untuk mendapatkan module lainnya.
    </p>

    {{-- User Info --}}
    <div class="inline-flex items-center gap-3 bg-white rounded-lg px-4 py-3 shadow-sm">
        <img
            class="h-10 w-10 rounded-full"
            src="https://ui-avatars.com/api/?name={{ urlencode($displayName) }}&background=667eea&color=fff"
            alt="{{ $displayName }}"
        >
        <div class="text-left">
            <p class="text-sm font-medium text-gray-900">{{ $displayName }}</p>
            <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
        </div>
    </div>

    {{-- Permission Info --}}
    <div class="mt-6 text-xs text-gray-400">
        <i class="fa-solid fa-key mr-1"></i>
        Akses terbatas: Beranda Only
    </div>
</div>

{{-- Alternative: Welcome Card (simpler version) --}}
{{--
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
    <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-4">
        <i class="fa-solid fa-hand text-2xl text-indigo-600"></i>
    </div>
    <h2 class="text-lg font-semibold text-gray-900 mb-2">Selamat Datang, {{ auth()->user()->name }}!</h2>
    <p class="text-sm text-gray-500">Hubungi Administrator untuk mendapatkan akses module lainnya.</p>
</div>
--}}
