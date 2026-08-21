<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\Company;
use App\Services\CRM\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    /**
     * Backup service instance
     */
    protected BackupService $backupService;

    /**
     * Create a new controller instance.
     */
    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Get single company ID for this application.
     * Returns the active company if exactly 1 exists, null otherwise.
     *
     * @return int|null
     */
    protected function getActiveCompanyId(): ?int
    {
        $count = Company::count();

        if ($count !== 1) {
            return null; // 0 or >1 companies
        }

        $company = Company::first();
        return $company ? $company->id : null;
    }

    /**
     * Display backup dashboard
     */
    public function index(): View|RedirectResponse
    {
        $companyId = $this->getActiveCompanyId();

        // No active company → redirect to settings for setup
        if (!$companyId) {
            return redirect()->route('settings.index')
                ->with('info', 'Silakan lengkapi informasi perusahaan terlebih dahulu.');
        }

        // Get statistics
        $statistics = $this->backupService->getStatistics($companyId);

        // Get recent backups
        $recentBackups = Backup::forCompany($companyId)->latest()->take(10)->get();

        // Get settings
        $settings = BackupSetting::getOrCreateForCompany($companyId);

        // Get next scheduled backup
        $nextScheduled = $settings->next_scheduled_run;

        return view('crm.backup.index', compact(
            'statistics',
            'recentBackups',
            'settings',
            'nextScheduled'
        ));
    }

    /**
     * Display backup history
     */
    public function history(Request $request): View
    {
        $companyId = $this->getActiveCompanyId();

        if (!$companyId) {
            return redirect()->route('settings.index');
        }

        $query = Backup::forCompany($companyId);

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('backup_type', $request->type);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $backups = $query->latest()->paginate(15);

        return view('crm.backup.history', compact('backups'));
    }

    /**
     * Create a database backup
     */
    public function backupDatabase(): JsonResponse
    {
        $companyId = $this->getActiveCompanyId();

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company context not available',
            ], 400);
        }

        $user = auth()->user();

        try {
            $result = $this->backupService->backupDatabase($companyId, $user->id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup database berhasil dibuat',
                    'data' => [
                        'backup_id' => $result['backup']->id,
                        'filename' => $result['filename'],
                        'filesize' => $result['filesize'],
                        'formatted_filesize' => $result['backup']->formatted_filesize,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Gagal membuat backup database',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Backup database failed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a file backup
     */
    public function backupFiles(): JsonResponse
    {
        $companyId = $this->getActiveCompanyId();

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company context not available',
            ], 400);
        }

        $user = auth()->user();

        try {
            $result = $this->backupService->backupFiles($companyId, $user->id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup file berhasil dibuat',
                    'data' => [
                        'backup_id' => $result['backup']->id,
                        'filename' => $result['filename'],
                        'filesize' => $result['filesize'],
                        'formatted_filesize' => $result['backup']->formatted_filesize,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Gagal membuat backup file',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Backup files failed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a full backup
     */
    public function backupFull(): JsonResponse
    {
        $companyId = $this->getActiveCompanyId();

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company context not available',
            ], 400);
        }

        $user = auth()->user();

        try {
            $result = $this->backupService->backupFull($companyId, $user->id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Full backup berhasil dibuat',
                    'data' => [
                        'backup_id' => $result['backup']->id,
                        'filename' => $result['filename'],
                        'filesize' => $result['filesize'],
                        'formatted_filesize' => $result['backup']->formatted_filesize,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Gagal membuat full backup',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Full backup failed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function download(Backup $backup): Response|JsonResponse
    {
        $user = auth()->user();

        // Check if user has permission
        if (!$this->canAccessBackup($user, $backup)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke backup ini',
            ], 403);
        }

        // Check if file exists
        if (!$backup->file_exists) {
            return response()->json([
                'success' => false,
                'message' => 'File backup tidak ditemukan',
            ], 404);
        }

        $fullPath = storage_path('app/' . $backup->file_path);

        // Log activity
        $backup->logActivity('Backup didownload', 'backup', 'downloaded');

        return response()->download($fullPath, $backup->filename, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Show restore wizard
     */
    public function restoreShow(Backup $backup): View
    {
        $user = auth()->user();

        // Check if user has permission
        if (!$this->canAccessBackup($user, $backup)) {
            abort(403, 'Anda tidak memiliki akses ke backup ini');
        }

        // Check if file exists
        if (!$backup->file_exists) {
            abort(404, 'File backup tidak ditemukan');
        }

        return view('crm.backup.restore', compact('backup'));
    }

    /**
     * Restore from backup
     */
    public function restore(Request $request, Backup $backup): JsonResponse
    {
        $user = auth()->user();

        // Check if user has permission
        if (!$this->canAccessBackup($user, $backup)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke backup ini',
            ], 403);
        }

        // Validate options
        $request->validate([
            'options' => 'required|array',
            'options.*' => 'in:database,files,all',
        ]);

        try {
            $result = $this->backupService->restore($backup, $request->options);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Gagal restore backup',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Restore failed: ' . $e->getMessage(), [
                'exception' => $e,
                'backup_id' => $backup->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a backup
     */
    public function destroy(Backup $backup): JsonResponse
    {
        $user = auth()->user();

        // Check if user has permission
        if (!$this->canAccessBackup($user, $backup)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke backup ini',
            ], 403);
        }

        try {
            $result = $this->backupService->deleteBackup($backup);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Delete backup failed: ' . $e->getMessage(), [
                'exception' => $e,
                'backup_id' => $backup->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get backup statistics
     */
    public function statistics(): JsonResponse
    {
        $companyId = $this->getActiveCompanyId();

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company context not available',
            ], 400);
        }

        $statistics = $this->backupService->getStatistics($companyId);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Update backup settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $companyId = $this->getActiveCompanyId();

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company context not available',
            ], 400);
        }

        $request->validate([
            'schedule_type' => 'required|in:manual,daily,weekly,monthly',
            'backup_time' => 'required|date_format:H:i',
            'backup_day' => 'nullable|string',
            'retention_count' => 'required|integer|min:1|max:365',
            'is_enabled' => 'boolean',
        ]);

        try {
            $settings = BackupSetting::getOrCreateForCompany($companyId);

            $settings->update([
                'schedule_type' => $request->schedule_type,
                'backup_time' => $request->backup_time,
                'backup_day' => $request->backup_day,
                'retention_count' => $request->retention_count,
                'is_enabled' => $request->boolean('is_enabled'),
            ]);

            // Cleanup old backups
            if ($settings->retention_count) {
                $this->backupService->cleanupOldBackups($settings);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan backup berhasil disimpan',
                'data' => $settings,
            ]);

        } catch (\Exception $e) {
            Log::error('Update backup settings failed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload and restore from backup file
     */
    public function uploadRestore(Request $request): JsonResponse
    {
        $companyId = $this->getActiveCompanyId();

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company context not available',
            ], 400);
        }

        $user = auth()->user();

        $request->validate([
            'backup_file' => 'required|file|mimes:zip,sql,gz|max:204800',
            'options' => 'required|array',
            'options.*' => 'in:database,files,all',
        ]);

        try {
            $file = $request->file('backup_file');
            $extension = $file->getClientOriginalExtension();

            // Create temporary backup record
            $backup = Backup::create([
                'backup_type' => str_ends_with($file->getClientOriginalName(), '.sql') || str_ends_with($file->getClientOriginalName(), '.sql.gz')
                    ? Backup::TYPE_DATABASE
                    : Backup::TYPE_FULL,
                'company_id' => $companyId,
                'status' => Backup::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'created_by' => $user->id,
                'filename' => $file->getClientOriginalName(),
                'filesize' => $file->getSize(),
            ]);

            // Store the uploaded file temporarily
            $tempPath = $file->storeAs('backups/temp', $backup->uuid . '.' . $extension);

            // Update backup with path
            $backup->update(['path' => $tempPath]);

            // Restore from the file
            $result = $this->backupService->restore($backup, $request->options);

            // Cleanup temp file
            Storage::delete($tempPath);

            // Delete the backup record
            $backup->delete();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Restore dari file backup berhasil',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Gagal restore dari file backup',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Upload restore failed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if user can access backup
     */
    protected function canAccessBackup($user, Backup $backup): bool
    {
        // User must belong to the same company
        $activeCompanyId = $this->getActiveCompanyId();
        if ($activeCompanyId && $backup->company_id !== $activeCompanyId) {
            return false;
        }

        // Developer can access all
        if ($user->is_developer) {
            return true;
        }

        // Owner and Director can access
        if ($user->is_owner || $user->is_director || $user->is_company_admin) {
            return true;
        }

        // Check if user has specific permission
        return $user->hasSidebarPermission('backup');
    }
}
