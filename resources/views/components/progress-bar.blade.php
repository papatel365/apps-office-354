{{-- Progress Bar Component --}}
@props([
    'value' => 0,
    'max' => 100,
    'label' => '',
    'showPercentage' => true,
    'color' => 'indigo',
    'size' => 'md',
])

@php
$percentage = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0;

$barColors = match($color) {
    'emerald' => 'bg-emerald-500',
    'yellow' => 'bg-yellow-500',
    'red' => 'bg-red-500',
    default => 'bg-indigo-500',
};

$sizeClasses = match($size) {
    'sm' => 'h-1.5',
    'lg' => 'h-4',
    default => 'h-2.5',
};
@endphp

<div class="w-full">
    @if($label)
        <div class="flex justify-between items-center mb-1">
            <p class="text-sm font-medium text-gray-700">{{ $label }}</p>
            @if($showPercentage)
                <p class="text-sm text-gray-500">{{ round($percentage) }}%</p>
            @endif
        </div>
    @endif
    <div class="w-full bg-gray-200 rounded-full overflow-hidden {{ $sizeClasses }}">
        <div class="h-full rounded-full transition-all duration-500 ease-out {{ $barColors }}"
             style="width: {{ $percentage }}%">
        </div>
    </div>
</div>
