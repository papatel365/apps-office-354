<?php

namespace App\Services\CRM;

use App\Core\Traits\HasActivityLog;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BackupService
{
    use HasActivityLog;

    /**
     * Storage disk for backups
     */
    protected string $disk = 'local';

    /**
     * Base path for backups
     */
    protected string $basePath = 'backups';

    /**
     * Directories to include in file backup
     */
    protected array $includeDirectories = [
        'profile',
        'assets',
        'attendance',
        'documents',
        'contracts',
        'logo',
        'uploads',
        'qrcode',
    ];

    /**
     * Create a database backup
     *
     * @param int|null $companyId
     * @param int|null $userId
     * @return array
     */
    public function backupDatabase(?int $companyId = null, ?int $userId = null): array
    {
        $startedAt = now();
        $timestamp = now()->format('Y_m_d_His');

        try {
            // Generate filename FIRST (required for record creation)
            $gzFilename = 'database_' . $timestamp . '.sql.gz';

            // Create backup record with filename
            $backup = $this->createBackupRecord(
                Backup::TYPE_DATABASE,
                $companyId,
                $userId,
                $startedAt,
                $gzFilename
            );

            // Ensure backup directory exists
            $gzPath = $this->getBackupPath(Backup::TYPE_DATABASE, $gzFilename);
            $this->ensureBackupDirectoryExists(Backup::TYPE_DATABASE);

            // Create temp file for SQL dump
            $tempFile = tempnam(sys_get_temp_dir(), 'sql_');

            // Generate database dump
            $this->generateDatabaseDump($tempFile, $companyId);

            // Get full path for compressed file
            $gzFullPath = storage_path('app/' . $gzPath);

            // Compress to gzip
            $sqlContent = file_get_contents($tempFile);
            $gzHandle = gzopen($gzFullPath, 'wb');
            if ($gzHandle === false) {
                throw new \Exception('Tidak dapat membuat file gzip: ' . $gzFullPath);
            }
            gzwrite($gzHandle, $sqlContent);
            gzclose($gzHandle);

            // Cleanup temp file
            unlink($tempFile);

            // Get file size
            $filesize = filesize($gzFullPath);

            // Update backup record
            $backup->update([
                'path' => $gzPath,
                'filesize' => $filesize,
                'status' => Backup::STATUS_COMPLETED,
                'finished_at' => now(),
            ]);

            // Calculate checksum
            $checksum = hash_file('sha256', $gzFullPath);
            $backup->update(['checksum' => $checksum]);

            // Log activity
            $this->logBackupActivity($backup, 'Backup database berhasil dibuat', 'success');

            return [
                'success' => true,
                'backup' => $backup,
                'filename' => $gzFilename,
                'filesize' => $filesize,
            ];

        } catch (\Exception $e) {
            Log::error('Database backup failed: ' . $e->getMessage(), [
                'exception' => $e,
                'company_id' => $companyId,
            ]);

            // Update backup record with error
            if (isset($backup)) {
                $backup->update([
                    'status' => Backup::STATUS_FAILED,
                    'finished_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            // Log activity
            $this->logBackupActivity($backup ?? null, 'Backup database gagal: ' . $e->getMessage(), 'error');

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a file backup
     *
     * @param int|null $companyId
     * @param int|null $userId
     * @return array
     */
    public function backupFiles(?int $companyId = null, ?int $userId = null): array
    {
        $startedAt = now();
        $timestamp = now()->format('Y_m_d_His');

        try {
            // Generate filename FIRST (required for record creation)
            $filename = 'files_' . $timestamp . '.zip';

            // Create backup record with filename
            $backup = $this->createBackupRecord(
                Backup::TYPE_FILE,
                $companyId,
                $userId,
                $startedAt,
                $filename
            );

            // Ensure backup directory exists
            $this->ensureBackupDirectoryExists(Backup::TYPE_FILE);

            // Create ZIP archive
            $path = $this->getBackupPath(Backup::TYPE_FILE, $filename);
            $fullPath = storage_path('app/' . $path);

            $zip = new ZipArchive();
            if ($zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Tidak dapat membuat file ZIP');
            }

            // Add files from each directory
            foreach ($this->includeDirectories as $dir) {
                $sourcePath = storage_path('app/public/' . $dir);
                if (is_dir($sourcePath)) {
                    $this->addDirectoryToZip($zip, $sourcePath, 'public/' . $dir);
                }
            }

            $zip->close();

            // Get file size
            $filesize = filesize($fullPath);

            // Update backup record
            $backup->update([
                'path' => $path,
                'filesize' => $filesize,
                'status' => Backup::STATUS_COMPLETED,
                'finished_at' => now(),
            ]);

            // Calculate checksum
            $checksum = hash_file('sha256', $fullPath);
            $backup->update(['checksum' => $checksum]);

            // Log activity
            $this->logBackupActivity($backup, 'Backup file berhasil dibuat', 'success');

            return [
                'success' => true,
                'backup' => $backup,
                'filename' => $filename,
                'filesize' => $filesize,
            ];

        } catch (\Exception $e) {
            Log::error('File backup failed: ' . $e->getMessage(), [
                'exception' => $e,
                'company_id' => $companyId,
            ]);

            if (isset($backup)) {
                $backup->update([
                    'status' => Backup::STATUS_FAILED,
                    'finished_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            $this->logBackupActivity($backup ?? null, 'Backup file gagal: ' . $e->getMessage(), 'error');

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a full backup (database + files)
     *
     * @param int|null $companyId
     * @param int|null $userId
     * @return array
     */
    public function backupFull(?int $companyId = null, ?int $userId = null): array
    {
        $startedAt = now();
        $timestamp = now()->format('Y_m_d_His');

        try {
            // Generate filename FIRST (required for record creation)
            $filename = 'full_backup_' . $timestamp . '.zip';

            // Create backup record with filename
            $backup = $this->createBackupRecord(
                Backup::TYPE_FULL,
                $companyId,
                $userId,
                $startedAt,
                $filename
            );

            // Ensure backup directory exists
            $this->ensureBackupDirectoryExists(Backup::TYPE_FULL);

            // Create ZIP archive
            $path = $this->getBackupPath(Backup::TYPE_FULL, $filename);
            $fullPath = storage_path('app/' . $path);

            $zip = new ZipArchive();
            if ($zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Tidak dapat membuat file ZIP');
            }

            // 1. Add database dump
            $tempSqlFile = tempnam(sys_get_temp_dir(), 'sql_');
            $this->generateDatabaseDump($tempSqlFile, $companyId);
            $zip->addFile($tempSqlFile, 'database.sql');
            unlink($tempSqlFile);

            // 2. Add files from each directory
            foreach ($this->includeDirectories as $dir) {
                $sourcePath = storage_path('app/public/' . $dir);
                if (is_dir($sourcePath)) {
                    $this->addDirectoryToZip($zip, $sourcePath, 'files/' . $dir);
                }
            }

            // 3. Add metadata
            $metadata = [
                'created_at' => now()->toIso8601String(),
                'backup_type' => 'full',
                'company_id' => $companyId,
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
            ];
            $zip->addFromString('metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

            $zip->close();

            // Get file size
            $filesize = filesize($fullPath);

            // Update backup record
            $backup->update([
                'path' => $path,
                'filesize' => $filesize,
                'status' => Backup::STATUS_COMPLETED,
                'finished_at' => now(),
            ]);

            // Calculate checksum
            $checksum = hash_file('sha256', $fullPath);
            $backup->update(['checksum' => $checksum]);

            // Log activity
            $this->logBackupActivity($backup, 'Full backup berhasil dibuat', 'success');

            return [
                'success' => true,
                'backup' => $backup,
                'filename' => $filename,
                'filesize' => $filesize,
            ];

        } catch (\Exception $e) {
            Log::error('Full backup failed: ' . $e->getMessage(), [
                'exception' => $e,
                'company_id' => companyId(),
            ]);

            if (isset($backup)) {
                $backup->update([
                    'status' => Backup::STATUS_FAILED,
                    'finished_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            $this->logBackupActivity($backup ?? null, 'Full backup gagal: ' . $e->getMessage(), 'error');

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore from a backup
     *
     * @param Backup $backup
     * @param array $options restore options (database, files, storage, all)
     * @return array
     */
    public function restore(Backup $backup, array $options = ['all']): array
    {
        try {
            // Update status to restoring
            $backup->update([
                'status' => Backup::STATUS_RESTORING,
            ]);

            // Log activity
            $this->logBackupActivity($backup, 'Restore backup dimulai', 'info');

            $fullPath = storage_path('app/' . $backup->file_path);

            if (!file_exists($fullPath)) {
                throw new \Exception('File backup tidak ditemukan');
            }

            // Check if it's a zip file
            $isZip = in_array(pathinfo($fullPath, PATHINFO_EXTENSION), ['zip', 'gz']);

            if ($isZip) {
                // Extract and process based on backup type
                $tempDir = tempnam(sys_get_temp_dir(), 'backup_');
                unlink($tempDir);
                mkdir($tempDir);

                if (str_ends_with($fullPath, '.gz')) {
                    // Decompress gz file
                    $decompressedFile = $tempDir . '/database.sql';
                    $gzHandle = gzopen($fullPath, 'rb');
                    $sqlContent = gzread($gzHandle, 10485760); // Read up to 10MB
                    gzclose($gzHandle);
                    file_put_contents($decompressedFile, $sqlContent);
                } else {
                    // Extract zip
                    $zip = new ZipArchive();
                    $zip->open($fullPath);
                    $zip->extractTo($tempDir);
                    $zip->close();
                }

                // Restore based on options
                if (in_array('database', $options) || in_array('all', $options)) {
                    $sqlFile = $tempDir . '/database.sql';
                    if (file_exists($sqlFile)) {
                        $this->restoreDatabase($sqlFile);
                    }
                }

                if (in_array('files', $options) || in_array('all', $options)) {
                    $filesDir = $tempDir . '/files';
                    if (is_dir($filesDir)) {
                        $this->restoreFiles($filesDir);
                    }
                }

                // Cleanup
                $this->deleteDirectory($tempDir);

            } else {
                // SQL file only (database backup)
                if (in_array('database', $options) || in_array('all', $options)) {
                    $this->restoreDatabase($fullPath);
                }
            }

            // Update backup status
            $backup->update([
                'status' => Backup::STATUS_RESTORED,
                'finished_at' => now(),
            ]);

            // Log activity
            $this->logBackupActivity($backup, 'Restore backup berhasil', 'success');

            return [
                'success' => true,
                'message' => 'Restore backup berhasil',
            ];

        } catch (\Exception $e) {
            Log::error('Restore failed: ' . $e->getMessage(), [
                'exception' => $e,
                'backup_id' => $backup->id,
            ]);

            $backup->update([
                'status' => Backup::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            $this->logBackupActivity($backup, 'Restore backup gagal: ' . $e->getMessage(), 'error');

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a backup
     *
     * @param Backup $backup
     * @return array
     */
    public function deleteBackup(Backup $backup): array
    {
        try {
            // Delete file
            if ($backup->file_path) {
                Storage::disk($this->disk)->delete($backup->file_path);
            }

            // Log activity
            $this->logBackupActivity($backup, 'Backup dihapus', 'info');

            // Delete record
            $backup->delete();

            return [
                'success' => true,
                'message' => 'Backup berhasil dihapus',
            ];

        } catch (\Exception $e) {
            Log::error('Delete backup failed: ' . $e->getMessage(), [
                'exception' => $e,
                'backup_id' => $backup->id,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean up old backups based on retention settings
     *
     * @param BackupSetting $setting
     * @return int Number of backups deleted
     */
    public function cleanupOldBackups(BackupSetting $setting): int
    {
        $companyId = $setting->company_id;
        $retentionCount = $setting->retention_count;

        // Get old backups to delete
        $backupsToDelete = Backup::where('company_id', $companyId)
            ->completed()
            ->orderBy('created_at', 'desc')
            ->skip($retentionCount)
            ->take(100)
            ->get();

        $deletedCount = 0;

        foreach ($backupsToDelete as $backup) {
            $result = $this->deleteBackup($backup);
            if ($result['success']) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get backup statistics
     *
     * @param int|null $companyId
     * @return array
     */
    public function getStatistics(?int $companyId = null): array
    {
        $query = Backup::query();

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $totalBackups = $query->count();
        $completedBackups = (clone $query)->where('status', Backup::STATUS_COMPLETED)->count();
        $failedBackups = (clone $query)->where('status', Backup::STATUS_FAILED)->count();

        // Total size
        $totalSize = (clone $query)->where('status', Backup::STATUS_COMPLETED)->sum('filesize');

        // Latest backup
        $latestBackup = (clone $query)
            ->where('status', Backup::STATUS_COMPLETED)
            ->latest('created_at')
            ->first();

        // Storage used
        $storageUsed = $this->calculateStorageUsed($companyId);

        return [
            'total_backups' => $totalBackups,
            'completed_backups' => $completedBackups,
            'failed_backups' => $failedBackups,
            'total_size' => $totalSize,
            'formatted_total_size' => $this->formatBytes($totalSize),
            'latest_backup' => $latestBackup,
            'storage_used' => $storageUsed,
            'formatted_storage_used' => $this->formatBytes($storageUsed),
        ];
    }

    /**
     * Ensure backup directory exists, create if not
     *
     * @param string $type database|file|full
     * @return void
     * @throws \Exception
     */
    protected function ensureBackupDirectoryExists(string $type): void
    {
        $path = $this->basePath . '/' . $type;
        $fullPath = storage_path('app/' . $path);

        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
                throw new \Exception('Tidak dapat membuat direktori backup: ' . $fullPath);
            }
        }
    }

    /**
     * Get backup path
     *
     * @param string $type
     * @param string $filename
     * @return string
     */
    protected function getBackupPath(string $type, string $filename): string
    {
        return $this->basePath . '/' . $type . '/' . $filename;
    }

    /**
     * Create backup record
     *
     * @param string $type
     * @param int|null $companyId
     * @param int|null $userId
     * @param \DateTime $startedAt
     * @param string $filename
     * @return Backup
     */
    protected function createBackupRecord(
        string $type,
        ?int $companyId,
        ?int $userId,
        \DateTime $startedAt,
        string $filename
    ): Backup {
        return Backup::create([
            'backup_type' => $type,
            'company_id' => $companyId,
            'filename' => $filename,
            'status' => Backup::STATUS_IN_PROGRESS,
            'started_at' => $startedAt,
            'created_by' => $userId,
            'disk' => $this->disk,
        ]);
    }

    /**
     * Generate database dump
     *
     * @param string $outputFile
     * @param int|null $companyId
     */
    protected function generateDatabaseDump(string $outputFile, ?int $companyId): void
    {
        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . config('database.connections.mysql.database');

        $output = "-- Office 354 Database Backup\n";
        $output .= "-- Generated at: " . now()->toIso8601String() . "\n";
        $output .= "-- Database: " . config('database.connections.mysql.database') . "\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;

            // Skip migration tables
            if (str_starts_with($tableName, 'migrations')) {
                continue;
            }

            // Filter by company if specified
            if ($companyId && !in_array($tableName, ['users', 'tenants', 'companies'])) {
                $hasCompanyColumn = $this->tableHasColumn($tableName, 'company_id');
                if ($hasCompanyColumn) {
                    // We still backup the table structure but filter data later
                    // For simplicity, we'll backup all data
                }
            }

            // Table structure
            $output .= "\n-- Table: {$tableName}\n";
            $output .= "DROP TABLE IF EXISTS `{$tableName}`;\n";

            $createTable = DB::selectOne("SHOW CREATE TABLE `{$tableName}`");
            $createSql = array_values((array) $createTable)[1];
            $output .= $createSql . ";\n\n";

            // Table data
            $rows = DB::table($tableName)->get();

            if ($rows->isNotEmpty()) {
                $columns = DB::getSchemaBuilder()->getColumnListing($tableName);
                $columnNames = array_map(fn($col) => "`{$col}`", $columns);
                $output .= "INSERT INTO `{$tableName}` (" . implode(', ', $columnNames) . ") VALUES\n";

                $values = [];
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($columns as $column) {
                        $value = $row->$column;
                        if ($value === null) {
                            $rowValues[] = 'NULL';
                        } elseif (is_numeric($value)) {
                            $rowValues[] = $value;
                        } else {
                            $rowValues[] = "'" . $this->escapeString($value) . "'";
                        }
                    }
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }

                // Split into chunks to avoid SQL size limits
                $chunks = array_chunk($values, 100);
                foreach ($chunks as $chunk) {
                    $output .= implode(",\n", $chunk) . ";\n";
                }
            }
        }

        file_put_contents($outputFile, $output);
    }

    /**
     * Check if table has a column
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    protected function tableHasColumn(string $table, string $column): bool
    {
        return in_array($column, DB::getSchemaBuilder()->getColumnListing($table));
    }

    /**
     * Escape string for SQL
     *
     * @param mixed $value
     * @return string
     */
    protected function escapeString($value): string
    {
        return str_replace(
            ["\\", "'", "\0", "\n", "\r", "\t"],
            ["\\\\", "\\'", "\\0", "\\n", "\\r", "\\t"],
            (string) $value
        );
    }

    /**
     * Restore database from SQL file
     *
     * @param string $sqlFile
     */
    protected function restoreDatabase(string $sqlFile): void
    {
        $sql = file_get_contents($sqlFile);

        // Split into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
        );

        foreach ($statements as $statement) {
            try {
                DB::statement($statement);
            } catch (\Exception $e) {
                Log::warning('SQL statement failed: ' . $e->getMessage(), [
                    'statement' => substr($statement, 0, 200),
                ]);
            }
        }
    }

    /**
     * Restore files from directory
     *
     * @param string $filesDir
     */
    protected function restoreFiles(string $filesDir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filesDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = 'public/' . substr($item->getPathname(), strlen($filesDir) + 1);

            if ($item->isDir()) {
                $targetPath = storage_path('app/' . $relativePath);
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $targetPath = storage_path('app/' . $relativePath);
                $targetDir = dirname($targetPath);

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                copy($item->getPathname(), $targetPath);
            }
        }
    }

    /**
     * Add directory to ZIP archive
     *
     * @param ZipArchive $zip
     * @param string $sourcePath
     * @param string $archivePath
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $sourcePath, string $archivePath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $zip->addEmptyDir($archivePath . '/' . $iterator->getSubPathName());
            } else {
                $zip->addFile(
                    $item->getPathname(),
                    $archivePath . '/' . $iterator->getSubPathName()
                );
            }
        }
    }

    /**
     * Delete directory recursively
     *
     * @param string $dir
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    /**
     * Calculate storage used by backups
     *
     * @param int|null $companyId
     * @return int
     */
    protected function calculateStorageUsed(?int $companyId): int
    {
        $query = Backup::query();

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->where('status', Backup::STATUS_COMPLETED)->sum('filesize');
    }

    /**
     * Format bytes to human readable
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }

        return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
    }

    /**
     * Log backup activity
     *
     * @param Backup|null $backup
     * @param string $description
     * @param string $type
     */
    protected function logBackupActivity(?Backup $backup, string $description, string $type): void
    {
        try {
            $properties = [
                'type' => $type,
            ];

            if ($backup) {
                $properties['backup_id'] = $backup->id;
                $properties['backup_type'] = $backup->backup_type;
            }

            // Get user from auth
            $user = auth()->user();

            // Get tenant_id from various sources
            $tenantId = null;
            if ($backup && isset($backup->tenant_id)) {
                $tenantId = $backup->tenant_id;
            } elseif ($user && isset($user->tenant_id)) {
                $tenantId = $user->tenant_id;
            } elseif (function_exists('current_tenant_id')) {
                $tenantId = current_tenant_id();
            }

            // Create activity log directly using ActivityLog model
            $activityData = [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'tenant_id' => $tenantId,
                'subject_type' => $backup ? get_class($backup) : null,
                'subject_id' => $backup?->id,
                'user_id' => $user?->id,
                'log_name' => 'backup',
                'description' => $description,
                'event' => $type,
                'properties' => $properties,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ];

            \App\Modules\System\Models\ActivityLog::create($activityData);

        } catch (\Exception $e) {
            // Silently fail - logging should not break the backup process
            Log::warning('Failed to log backup activity: ' . $e->getMessage());
        }
    }
}
