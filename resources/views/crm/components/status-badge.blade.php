{{-- resources/views/crm/components/status-badge.blade.php --}}
@props(['status' => 'pending', 'type' => 'primary', 'text' => null])

@php
    $colors = [
        'primary' => 'bg-blue-100 text-blue-800 ring-blue-600/20',
        'success' => 'bg-green-100 text-green-800 ring-green-600/20',
        'warning' => 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
        'danger' => 'bg-red-100 text-red-800 ring-red-600/20',
        'secondary' => 'bg-gray-100 text-gray-800 ring-gray-600/20',
        'info' => 'bg-indigo-100 text-indigo-800 ring-indigo-600/20',
    ];

    $colorClass = $colors[$type] ?? $colors['secondary'];
    $label = $text ?? ucfirst($status);
@endphp

<span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $colorClass }}">
    {{ $label }}
</span>
