{{-- resources/views/components/dashboard/asset-widget.blade.php --}}
{{--
    Asset Widget
    Shows asset stats and recent assets - only visible if user has 'assets' permission
--}}

{{-- Asset Stats Card --}}
<div class="mb-6">
    <x-dashboard.stat-card
        title="Total Aset"
        :value="$stats['total_assets']"
        icon="laptop"
        color="cyan"
        :subtitle="'<i class=\'fa-solid fa-check mr-1\'></i>' . number_format($stats['available_assets']) . ' tersedia | <i class=\'fa-solid fa-share mr-1\'></i>' . number_format($stats['allocated_assets']) . ' dialokasikan'"
    />
</div>

{{-- Recent Assets Table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900">
            <i class="fa-solid fa-laptop text-cyan-500 mr-2"></i>
            Aktivitas Aset Terbaru
        </h3>
        <a href="{{ route('assets.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Lihat Semua</a>
    </div>
    <div class="overflow-x-auto">
        @if($recentAssets->count() > 0)
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aset</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($recentAssets as $asset)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-cyan-100 flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-laptop text-cyan-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $asset->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $asset->category?->name ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @php
                                    $assetStatusColors = [
                                        'available' => 'bg-green-100 text-green-800',
                                        'allocated' => 'bg-blue-100 text-blue-800',
                                        'maintenance' => 'bg-amber-100 text-amber-800',
                                        'retired' => 'bg-gray-100 text-gray-800',
                                    ];
                                    $assetStatusLabels = [
                                        'available' => 'Tersedia',
                                        'allocated' => 'Dialokasikan',
                                        'maintenance' => 'Maintenance',
                                        'retired' => 'Dimusnahkan',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $assetStatusColors[$asset->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $assetStatusLabels[$asset->status] ?? ucfirst($asset->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-8 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-laptop text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500">Belum ada aset</p>
            </div>
        @endif
    </div>
</div>
