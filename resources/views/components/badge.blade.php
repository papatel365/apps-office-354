{{-- Badge Component --}}
@props([
    'type' => 'default',
    'size' => 'md',
])

@php
$classes = match($type) {
    'success' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'warning' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
    'danger' => 'bg-red-100 text-red-700 border-red-200',
    'info' => 'bg-blue-100 text-blue-700 border-blue-200',
    'purple' => 'bg-purple-100 text-purple-700 border-purple-200',
    'indigo' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
    default => 'bg-gray-100 text-gray-700 border-gray-200',
};

$sizeClasses = match($size) {
    'sm' => 'px-1.5 py-0.5 text-[10px]',
    'lg' => 'px-3 py-1.5 text-sm',
    default => 'px-2 py-1 text-xs',
};
@endphp

<span class="inline-flex items-center gap-1 font-medium rounded-full border {{ $classes }} {{ $sizeClasses }}">
    {{ $slot }}
</span>
