{{-- Nav Item Component --}}
@props([
    'href' => '#',
    'active' => false,
    'icon' => 'fa-circle',
])

<a href="{{ $href }}"
   class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
          {{ $active
              ? 'nav-item-active text-white'
              : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
    <i class="fa-solid {{ $icon }} w-5 text-center text-sm
              {{ $active ? 'text-indigo-400' : 'text-gray-500' }}"></i>
    <span class="flex-1">{{ $slot }}</span>
    @if(isset($badge))
        {{ $badge }}
    @endif
</a>
