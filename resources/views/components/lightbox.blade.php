<!-- Lightbox Component for Photo Preview -->
@props(['maxWidth' => 'max-w-4xl'])

<div x-data="{ open: false, src: '', alt: '' }"
     @open-lightbox.window="open = true; src = $event.detail.src; alt = $event.detail.alt"
     @keydown.escape.window="open = false"
     x-cloak>
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="fixed inset-0 bg-black/90" @click="open = false"></div>
        <div class="relative z-10">
            <button @click="open = false"
                    class="absolute -top-10 right-0 text-white hover:text-gray-300">
                <i class="fa-solid fa-times text-2xl"></i>
            </button>
            <img :src="src"
                 :alt="alt"
                 class="max-w-full max-h-[90vh] object-contain">
        </div>
    </div>
</div>
