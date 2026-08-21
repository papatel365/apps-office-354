{{-- resources/views/components/dashboard/asset-category-widget.blade.php --}}
{{--
    Asset Category Widget
    Shows asset category stats - only visible if user has 'asset_categories' permission
--}}

@php
    $totalCategories = $stats['total_asset_categories'] ?? 0;
    $recentCategories = $recentAssetCategories ?? collect();
@endphp

@if($totalCategories > 0 || $recentCategories->count() > 0)
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900">
            <i class="fa-solid fa-layer-group text-cyan-500 mr-2"></i>
            Kategori Aset
        </h3>
        <a href="{{ route('asset-categories.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Kelola</a>
    </div>
    <div class="p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Kategori</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalCategories) }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-cyan-100 flex items-center justify-center">
                <i class="fa-solid fa-layer-group text-cyan-600 text-xl"></i>
            </div>
        </div>

        @if($recentCategories->count() > 0)
        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-medium text-gray-500 uppercase mb-2">Kategori Terbaru</p>
            <div class="space-y-2">
                @foreach($recentCategories->take(3) as $category)
                    <div class="flex items-center justify-between py-1">
                        <span class="text-sm text-gray-700">{{ $category->name }}</span>
                        <span class="text-xs text-gray-400">{{ $category->assets_count ?? 0 }} aset</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endif
