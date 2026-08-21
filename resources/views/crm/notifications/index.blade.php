{{-- resources/views/crm/notifications/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Notifikasi')

@section('page-title', 'Notifikasi')

@push('page-actions')
    <form action="{{ route('notifications.read-all') }}" method="POST" class="inline">
        @csrf
        @method('PUT')
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            <i class="fa-solid fa-check-double mr-2"></i>
            Tandai Semua Sudah Dibaca
        </button>
    </form>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Notifikasi</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-bell text-gray-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Belum Dibaca</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $stats['unread'] }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-envelope-open-text text-indigo-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Sudah Dibaca</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['read'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-envelope text-green-600"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <a href="{{ route('notifications.index') }}"
                   class="py-4 px-6 text-sm font-medium border-b-2 {{ !request('is_read') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Semua
                </a>
                <a href="{{ route('notifications.index', ['is_read' => '0']) }}"
                   class="py-4 px-6 text-sm font-medium border-b-2 {{ request('is_read') === '0' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Belum Dibaca
                    @if($stats['unread'] > 0)
                        <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-indigo-100 text-indigo-600">{{ $stats['unread'] }}</span>
                    @endif
                </a>
                <a href="{{ route('notifications.index', ['is_read' => '1']) }}"
                   class="py-4 px-6 text-sm font-medium border-b-2 {{ request('is_read') === '1' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Sudah Dibaca
                </a>
            </nav>
        </div>

        {{-- Notifications List --}}
        <div class="divide-y divide-gray-100">
            @forelse($notifications as $notification)
                <div class="p-4 hover:bg-gray-50 transition-colors {{ $notification->is_read ? 'opacity-75' : '' }}">
                    <div class="flex items-start gap-4">
                        {{-- Icon --}}
                        <div class="flex-shrink-0">
                            @if($notification->is_read)
                                <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100">
                                    <i class="{{ $notification->action_icon ?? 'fa-solid fa-bell' }} text-gray-400"></i>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $notification->severity_bg ?? 'bg-indigo-100' }}">
                                    <i class="{{ $notification->action_icon ?? 'fa-solid fa-bell' }} {{ $notification->severity ?? 'text-indigo-600' }}"></i>
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 {{ $notification->is_read ? '' : 'font-semibold' }}">
                                        {{ $notification->title }}
                                    </h4>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ $notification->message }}
                                    </p>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            {{ $notification->module }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ $notification->time_ago }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center gap-2 ml-4">
                                    @if(!$notification->is_read)
                                        <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit"
                                                    class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                                    title="Tandai sudah dibaca">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Hapus notifikasi"
                                                onclick="return confirm('Yakin ingin menghapus notifikasi ini?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Link to related content --}}
                            @if($notification->link_url)
                                <a href="{{ $notification->link_url }}"
                                   class="mt-2 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                                    <span>Lihat detail</span>
                                    <i class="fa-solid fa-arrow-right ml-1"></i>
                                </a>
                            @endif
                        </div>

                        {{-- Unread indicator --}}
                        @if(!$notification->is_read)
                            <div class="flex-shrink-0">
                                <span class="w-2 h-2 bg-indigo-600 rounded-full block"></span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-bell-slash text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Tidak ada notifikasi</h3>
                    <p class="text-gray-500 text-sm">
                        @if(request('is_read') === '0')
                            Semua notifikasi sudah dibaca.
                        @elseif(request('is_read') === '1')
                            Belum ada notifikasi yang dibaca.
                        @else
                            Anda tidak memiliki notifikasi saat ini.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $notifications->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
