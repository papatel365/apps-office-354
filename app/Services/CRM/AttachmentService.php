<?php

namespace App\Services\CRM;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    /**
     * Upload file.
     */
    public function upload(UploadedFile $file, string $type, int $id, ?int $uploadedBy = null): Attachment
    {
        $model = $type::findOrFail($id);

        $path = $file->store('attachments/' . $type::class, 'public');

        return $model->attachments()->create([
            'uuid' => \Str::uuid(),
            'tenant_id' => auth()->user()->tenant_id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $uploadedBy ?? auth()->id(),
        ]);
    }

    /**
     * Delete attachment.
     */
    public function delete(Attachment $attachment): bool
    {
        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        return $attachment->delete();
    }

    /**
     * Download attachment.
     */
    public function download(Attachment $attachment)
    {
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }
}
