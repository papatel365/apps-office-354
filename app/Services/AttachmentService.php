<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService extends BaseService
{
    /**
     * Upload file and create attachment record
     */
    public function upload(
        UploadedFile $file,
        string $attachableType,
        int $attachableId,
        string $disk = 'private',
        ?int $uploadedBy = null
    ): Attachment {
        // Generate path: attachments/{type}/{id}/{uuid}.{ext}
        $extension = $file->getClientOriginalExtension();
        $filename = \Str::uuid() . '.' . $extension;
        $subfolder = $this->getAttachmentFolder($attachableType, $attachableId);
        $path = "{$subfolder}/{$filename}";

        // Store file
        $storedPath = $file->storeAs(
            $subfolder,
            $filename,
            $disk
        );

        // Create attachment record
        return Attachment::create([
            'uuid' => \Str::uuid()->toString(),
            'tenant_id' => $this->tenantId(),
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => $disk,
            'uploaded_by' => $uploadedBy ?? auth()->id(),
        ]);
    }

    /**
     * Delete attachment and file
     */
    public function delete(Attachment $attachment): void
    {
        // Delete physical file
        $this->deleteFile($attachment);

        // Delete record
        $attachment->delete();
    }

    /**
     * Replace existing file with new one (keeps attachment record)
     */
    public function replace(Attachment $attachment, UploadedFile $newFile): Attachment
    {
        // Delete old file
        $this->deleteFile($attachment);

        // Store new file
        $extension = $newFile->getClientOriginalExtension();
        $filename = \Str::uuid() . '.' . $extension;
        $subfolder = $this->getAttachmentFolder($attachment->attachable_type, $attachment->attachable_id);
        $newPath = $newFile->storeAs($subfolder, $filename, $attachment->disk);

        // Update record
        $attachment->update([
            'file_name' => $newFile->getClientOriginalName(),
            'file_path' => $newPath,
            'file_type' => $newFile->getMimeType(),
            'file_size' => $newFile->getSize(),
        ]);

        return $attachment->fresh();
    }

    /**
     * Generate signed download URL
     */
    public function getSignedUrl(Attachment $attachment, int $minutes = 5): string
    {
        if ($attachment->disk === 'public') {
            return url("storage/{$attachment->file_path}");
        }

        return Storage::disk($attachment->disk)->temporaryUrl(
            $attachment->file_path,
            now()->addMinutes($minutes)
        );
    }

    /**
     * Delete physical file from disk
     */
    protected function deleteFile(Attachment $attachment): void
    {
        if (empty($attachment->file_path)) {
            return;
        }

        Storage::disk($attachment->disk)->delete($attachment->file_path);
    }

    /**
     * Get folder path for attachment type
     */
    protected function getAttachmentFolder(string $attachableType, int $attachableId): string
    {
        // Extract short model name
        $type = class_basename($attachableType);

        return "attachments/{$type}/{$attachableId}";
    }
}
