<?php

namespace App\Services\CRM;

use App\Core\Traits\HasActivityLog;
use App\Modules\System\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProfilePhotoService
{
    use HasActivityLog;

    /**
     * Image manager instance
     */
    protected ImageManager $imageManager;

    /**
     * Storage disk for profile photos
     */
    protected string $disk = 'public';

    /**
     * Storage path for profile photos
     */
    protected string $path = 'profile';

    /**
     * Maximum dimensions for profile photo
     */
    protected int $maxWidth = 600;
    protected int $maxHeight = 600;

    /**
     * Image quality for compression (0-100)
     */
    protected int $quality = 85;

    /**
     * Allowed mime types
     */
    protected array $allowedMimes = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Maximum file size in KB
     */
    protected int $maxSize = 5120; // 5MB

    /**
     * Create a new service instance
     */
    public function __construct()
    {
        // Create image manager with GD driver (default for most servers)
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Upload and process a new profile photo
     *
     * @param User $user
     * @param UploadedFile $file
     * @return array
     */
    public function upload(User $user, UploadedFile $file): array
    {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename with UUID
        $filename = $this->generateFilename($file);

        // Process and save the image
        $path = $this->processAndSave($file, $filename);

        // Delete old photo if exists and not default
        $oldPhoto = $user->profile_photo;
        if ($oldPhoto && !$this->isDefaultAvatar($oldPhoto)) {
            $this->deletePhoto($oldPhoto);
        }

        // Update user's profile photo
        $user->update([
            'profile_photo' => $path,
        ]);

        // Log activity (wrapped in try-catch to prevent logging failures from affecting the upload)
        try {
            $user->logActivity(
                'Mengubah Foto Profile',
                'profile',
                'updated',
                [
                    'old_photo' => $oldPhoto,
                    'new_photo' => $path,
                ]
            );
        } catch (\Exception $e) {
            \Log::warning('ProfilePhotoService: Failed to log activity', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'success' => true,
            'path' => $path,
            'url' => $this->getUrl($path),
            'filename' => $filename,
        ];
    }

    /**
     * Delete user's profile photo
     *
     * @param User $user
     * @return array
     */
    public function delete(User $user): array
    {
        $oldPhoto = $user->profile_photo;

        // Don't delete if it's a default avatar
        if (!$oldPhoto || $this->isDefaultAvatar($oldPhoto)) {
            return [
                'success' => false,
                'message' => 'Tidak ada foto profile untuk dihapus',
            ];
        }

        // Delete the file
        $this->deletePhoto($oldPhoto);

        // Clear user's profile photo
        $user->update([
            'profile_photo' => null,
        ]);

        // Log activity
        $user->logActivity(
            'Menghapus Foto Profile',
            'profile',
            'deleted',
            [
                'old_photo' => $oldPhoto,
            ]
        );

        return [
            'success' => true,
            'message' => 'Foto profile berhasil dihapus',
        ];
    }

    /**
     * Get the URL for a profile photo
     *
     * @param string|null $path
     * @return string|null
     */
    public function getUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Get the full path for a profile photo
     *
     * @param string|null $path
     * @return string|null
     */
    public function getFullPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk($this->disk)->path($path);
    }

    /**
     * Check if the path is a default avatar
     *
     * @param string|null $path
     * @return bool
     */
    public function isDefaultAvatar(?string $path): bool
    {
        if (!$path) {
            return true;
        }

        // Check if it's a URL from a third-party service like ui-avatars.com
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return true;
        }

        // Check if it's a default avatar pattern
        if (str_contains($path, 'default') || str_contains($path, 'avatar')) {
            return true;
        }

        return false;
    }

    /**
     * Get user's profile photo URL or default
     *
     * @param User $user
     * @return string
     */
    public function getPhotoUrl(User $user): string
    {
        if ($user->profile_photo && !$this->isDefaultAvatar($user->profile_photo)) {
            return $this->getUrl($user->profile_photo);
        }

        // Return default avatar URL
        return $this->getDefaultAvatarUrl($user->name);
    }

    /**
     * Get default avatar URL using UI Avatars
     *
     * @param string $name
     * @param int $size
     * @return string
     */
    public function getDefaultAvatarUrl(string $name, int $size = 120): string
    {
        $encodedName = urlencode($name);
        return "https://ui-avatars.com/api/?name={$encodedName}&background=667eea&color=fff&size={$size}&bold=true";
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @throws \InvalidArgumentException
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->maxSize * 1024) {
            throw new \InvalidArgumentException("Ukuran file maksimal adalah {$this->maxSize}KB (5MB)");
        }

        // Check mime type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedMimes)) {
            throw new \InvalidArgumentException('Format file harus: ' . implode(', ', $this->allowedMimes));
        }

        // Check if it's actually an image
        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
            throw new \InvalidArgumentException('File harus berupa gambar');
        }
    }

    /**
     * Generate unique filename
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $uuid = (string) Str::uuid();

        return "{$uuid}.{$extension}";
    }

    /**
     * Process and save the image
     *
     * @param UploadedFile $file
     * @param string $filename
     * @return string
     */
    protected function processAndSave(UploadedFile $file, string $filename): string
    {
        try {
            // Get the extension from original file
            $extension = strtolower($file->getClientOriginalExtension());

            // Read the uploaded file
            $image = $this->imageManager->read($file->getPathname());

            // Get original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Calculate crop dimensions (make it square)
            $size = min($originalWidth, $originalHeight);

            // Center crop coordinates
            $x = (int) floor(($originalWidth - $size) / 2);
            $y = (int) floor(($originalHeight - $size) / 2);

            // Crop to square
            $image = $image->crop($size, $size, $x, $y);

            // Resize to max dimensions (Intervention Image v3 resize maintains aspect ratio)
            $image = $image->resize($this->maxWidth, $this->maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Encode based on format using specific format methods
            $encodedImage = match($extension) {
                'jpg', 'jpeg' => $image->toJpeg($this->quality),
                'png' => $image->toPng(),
                'webp' => $image->toWebp($this->quality),
                default => $image->toJpeg($this->quality), // Default fallback to JPEG
            };

            // Save to storage
            $path = $this->path . '/' . $filename;
            Storage::disk($this->disk)->put($path, $encodedImage);

            return $path;
        } catch (\Exception $e) {
            \Log::error('ProfilePhotoService::processAndSave - FAILED', [
                'error' => $e->getMessage(),
                'file' => $filename,
            ]);
            throw $e;
        }
    }

    /**
     * Delete a photo from storage
     *
     * @param string $path
     * @return bool
     */
    protected function deletePhoto(string $path): bool
    {
        if ($this->isDefaultAvatar($path)) {
            return false;
        }

        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Get storage path
     *
     * @return string
     */
    public function getStoragePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    /**
     * Get backup files directory path
     *
     * @return string
     */
    public function getBackupPath(): string
    {
        return storage_path('app/backups');
    }

    /**
     * Check if a photo exists
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }
}
