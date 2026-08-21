{{-- resources/views/components/dashboard/activity-widget.blade.php --}}
{{--
    Activity Widget
    Shows recent activities filtered by user's permissions
    - Projects activities shown only if user has 'projects' permission
    - Task activities shown only if user has 'tasks' permission
    - Staff activities shown only if user has staff permissions
--}}

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900">
            <i class="fa-solid fa-clock-rotate-left text-gray-500 mr-2"></i>
            Aktivitas Terbaru
        </h3>
        <span class="text-xs text-gray-400">
            <i class="fa-solid fa-history mr-1"></i>
            Real-time
        </span>
    </div>
    <ul role="list" class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
        @forelse($recentActivities as $activity)
            <li class="px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 mt-1">
                        @if($activity->user)
                            <img class="h-9 w-9 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($activity->user->name) }}&background=667eea&color=fff" alt="">
                        @else
                            <div class="h-9 w-9 rounded-full bg-gray-200 flex items-center justify-center">
                                <i class="fa-solid fa-robot text-gray-400"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $activity->user?->name ?? 'System' }}
                        </p>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $activity->description ?? $activity->activity_type }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xs text-gray-400">
                            {{ $activity->created_at->diffForHumans() }}
                        </p>
                        @php
                            $typeColors = [
                                'success' => 'bg-green-100 text-green-800',
                                'warning' => 'bg-yellow-100 text-yellow-800',
                                'error' => 'bg-red-100 text-red-800',
                                'info' => 'bg-blue-100 text-blue-800',
                            ];
                            $typeIcons = [
                                'success' => 'fa-check-circle text-green-500',
                                'warning' => 'fa-exclamation-circle text-yellow-500',
                                'error' => 'fa-times-circle text-red-500',
                                'info' => 'fa-info-circle text-blue-500',
                            ];
                        @endphp
                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$activity->type] ?? 'bg-gray-100 text-gray-800' }}">
                            <i class="fa-solid {{ $typeIcons[$activity->type] ?? 'fa-info-circle' }} mr-1"></i>
                            {{ $activity->type ?? 'info' }}
                        </span>
                    </div>
                </div>
            </li>
        @empty
            <li class="px-5 py-12 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-inbox text-2xl text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500">Belum ada aktivitas</p>
                <p class="text-xs text-gray-400 mt-1">Aktivitas akan muncul di sini</p>
            </li>
        @endforelse
    </ul>
</div>
