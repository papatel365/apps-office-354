<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CompanyNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Display all notifications (web page).
     * Route: GET /notifications or GET /notifications/all
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $perPage = $request->input('per_page', 20);
        $module = $request->input('module');
        $isRead = $request->input('is_read');

        $query = CompanyNotification::forCompany($companyId)->latest();

        if ($module) {
            $query->where('module', $module);
        }

        if ($isRead !== null) {
            $query->where('is_read', $isRead === 'true' || $isRead === '1');
        }

        $notifications = $query->paginate($perPage);

        // Get stats
        $stats = [
            'total' => CompanyNotification::forCompany($companyId)->count(),
            'unread' => CompanyNotification::forCompany($companyId)->where('is_read', false)->count(),
            'read' => CompanyNotification::forCompany($companyId)->where('is_read', true)->count(),
        ];

        return view('crm.notifications.index', compact('notifications', 'stats'));
    }

    /**
     * Alias for index - display all notifications (web page).
     */
    public function all(Request $request): View
    {
        return $this->index($request);
    }

    /**
     * Get unread count (API/AJAX endpoint).
     * Returns JSON for dropdown badge.
     */
    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->company_id) {
            return response()->json([
                'success' => true,
                'data' => ['count' => 0],
            ]);
        }

        $count = $this->notificationService->getUnreadCount($user->company_id);

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    /**
     * Get notifications for dropdown (API/AJAX endpoint).
     * Returns JSON for navbar dropdown.
     */
    public function dropdown(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $limit = $request->input('limit', 10);

        $notifications = CompanyNotification::forCompany($companyId)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'uuid' => $notification->uuid,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'module' => $notification->module,
                    'action' => $notification->action,
                    'action_label' => $notification->action_label,
                    'action_icon' => $notification->action_icon,
                    'severity' => $notification->severity,
                    'severity_bg' => $notification->severity_bg,
                    'is_read' => $notification->is_read,
                    'time_ago' => $notification->time_ago,
                    'action_url' => $notification->link_url,
                    'created_at' => $notification->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read.
     * Can be called via AJAX or web form.
     */
    public function markAsRead(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        $notification = CompanyNotification::where('id', $id)
            ->where('company_id', $user->company_id)
            ->first();

        if (!$notification) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notifikasi tidak ditemukan',
                ], 404);
            }
            return redirect()->back()->with('error', 'Notifikasi tidak ditemukan');
        }

        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi telah dibaca',
            ]);
        }

        // Web form - redirect to previous page or notifications list
        return redirect()->back();
    }

    /**
     * Mark all notifications as read.
     * Can be called via AJAX or web form.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        if (!$user->company_id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company tidak ditemukan',
                ], 400);
            }
            return redirect()->back()->with('error', 'Company tidak ditemukan');
        }

        $count = $this->notificationService->markAllAsRead($user->company_id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} notifikasi telah ditandai dibaca",
            ]);
        }

        return redirect()->back()->with('success', "{$count} notifikasi telah ditandai dibaca");
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        $notification = CompanyNotification::where('id', $id)
            ->where('company_id', $user->company_id)
            ->first();

        if (!$notification) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notifikasi tidak ditemukan',
                ], 404);
            }
            return redirect()->back()->with('error', 'Notifikasi tidak ditemukan');
        }

        $notification->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil dihapus',
            ]);
        }

        return redirect()->back()->with('success', 'Notifikasi berhasil dihapus');
    }
}
