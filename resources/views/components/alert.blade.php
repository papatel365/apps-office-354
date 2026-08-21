{{-- Alert Component --}}
@props([
    'type' => 'info',
    'dismissible' => false,
])

@php
$config = match($type) {
    'success' => [
        'bg' => 'bg-emerald-50 border-emerald-200',
        'text' => 'text-emerald-800',
        'icon' => 'fa-check-circle',
        'iconColor' => 'text-emerald-500',
    ],
    'warning' => [
        'bg' => 'bg-yellow-50 border-yellow-200',
        'text' => 'text-yellow-800',
        'icon' => 'fa-exclamation-triangle',
        'iconColor' => 'text-yellow-500',
    ],
    'danger' => [
        'bg' => 'bg-red-50 border-red-200',
        'text' => 'text-red-800',
        'icon' => 'fa-exclamation-circle',
        'iconColor' => 'text-red-500',
    ],
    default => [
        'bg' => 'bg-blue-50 border-blue-200',
        'text' => 'text-blue-800',
        'icon' => 'fa-info-circle',
        'iconColor' => 'text-blue-500',
    ],
};
@endphp

<div class="border rounded-lg p-4 flex items-start gap-3 {{ $config['bg'] }} {{ $config['text'] }}"
     @if($dismissible) x-data="{ show: true }" x-show="show" @endif>
    <i class="fa-solid {{ $config['icon'] }} {{ $config['iconColor'] }} mt-0.5"></i>
    <div class="flex-1">
        {{ $slot }}
    </div>
    @if($dismissible)
        <button type="button" class="ml-auto -mr-1 opacity-50 hover:opacity-100" @click="show = false">
            <i class="fa-solid fa-times"></i>
        </button>
    @endif
</div>
