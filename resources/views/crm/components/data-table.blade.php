{{-- resources/views/crm/components/datatable.blade.php --}}
@props([
    'columns' => [],
    'data' => [],
    'actions' => null,
    'resource' => '',
    'createRoute' => null,
])

@aware(['title' => 'Data'])

<div class="flex flex-col">
    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ $title ?? 'Data' }}</h2>
            <p class="mt-1 text-sm text-gray-500">
                @if(isset($data) && method_exists($data, 'total'))
                    {{ $data->total() }} {{ Str::plural('record', $data->total()) }}
                @endif
            </p>
        </div>
        @if($createRoute)
            <div class="mt-4 sm:mt-0">
                <a href="{{ route($createRoute) }}"
                   class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Buat Baru
                </a>
            </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="mt-4 overflow-x-auto border border-gray-200 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($columns as $column)
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                    @if($actions)
                        <th class="relative px-3 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($data as $item)
                    <tr class="hover:bg-gray-50">
                        @foreach($columns as $column)
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-900">
                                {{ $item->{$column['field']} ?? '' }}
                            </td>
                        @endforeach
                        @if($actions)
                            <td class="relative px-3 py-3 text-right text-sm font-medium">
                                {{ $actions }}
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + ($actions ? 1 : 0) }}" class="px-3 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fa-solid fa-inbox text-4xl text-gray-300 mb-4"></i>
                                <p>Tidak ada data</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if(isset($data) && method_exists($data, 'links'))
        <div class="mt-4">
            {{ $data->links() }}
        </div>
    @endif
</div>
