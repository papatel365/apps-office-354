{{-- Health Indicator Component --}}
@props([
    'status' => 'healthy',
    'label' => '',
    'value' => null,
    'message' => null,
])

@php
$config = match($status) {
    'healthy' => [
        'icon' => 'fa-check-circle',
        'iconColor' => 'text-emerald-500',
        'bgColor' => 'bg-emerald-50',
        'borderColor' => 'border-emerald-200',
        'textColor' => 'text-emerald-700',
        'dotColor' => 'bg-emerald-500',
    ],
    'warning' => [
        'icon' => 'fa-exclamation-triangle',
        'iconColor' => 'text-yellow-500',
        'bgColor' => 'bg-yellow-50',
        'borderColor' => 'border-yellow-200',
        'textColor' => 'text-yellow-700',
        'dotColor' => 'bg-yellow-500',
    ],
    'error' => [
        'icon' => 'fa-times-circle',
        'iconColor' => 'text-red-500',
        'bgColor' => 'bg-red-50',
        'borderColor' => 'border-red-200',
        'textColor' => 'text-red-700',
        'dotColor' => 'bg-red-500',
    ],
    default => [
        'icon' => 'fa-question-circle',
        'iconColor' => 'text-gray-400',
        'bgColor' => 'bg-gray-50',
        'borderColor' => 'border-gray-200',
        'textColor' => 'text-gray-500',
        'dotColor' => 'bg-gray-400',
    ],
};
@endphp

<div class="flex items-center gap-3 p-3 rounded-lg border {{ $config['bgColor'] }} {{ $config['borderColor'] }}">
    <i class="fa-solid {{ $config['icon'] }} {{ $config['iconColor'] }} text-lg"></i>
    <div class="flex-1">
        <p class="text-sm font-medium {{ $config['textColor'] }}">{{ $label }}</p>
        @if($value)
            <p class="text-xs text-gray-500">{{ $value }}</p>
        @endif
        @if($message)
            <p class="text-xs text-gray-400">{{ $message }}</p>
        @endif
    </div>
    <span class="relative flex h-3 w-3">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $config['dotColor'] }}"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 {{ $config['dotColor'] }}"></span>
    </span>
</div>
