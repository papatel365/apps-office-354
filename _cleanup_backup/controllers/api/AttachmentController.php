<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __construct(
        protected AttachmentService $attachmentService
    ) {}

    /**
     * List attachments for polymorphic relation
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'attachable_type' => 'required|string',
            'attachable_id' => 'required|integer',
        ]);

        $attachments = Attachment::forTenant(auth()->user()->tenant_id)
            ->where('attachable_type', $request->attachable_type)
            ->where('attachable_id', $request->attachable_id)
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $attachments]);
    }

    /**
     * Upload new attachment
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'attachable_type' => 'required|string',
            'attachable_id' => 'required|integer',
            'file' => "required|file|max:" . Attachment::MAX_FILE_SIZE,
            'disk' => 'nullable|string|in:private,public',
        ]);

        $file = $request->file('file');
        $disk = $request->input('disk', 'private');

        // Validate MIME type
        $mimeType = $file->getMimeType();
        if (!Attachment::isAllowedMimeType($mimeType)) {
            return response()->json([
                'message' => 'File type not allowed',
            ], 422);
        }

        // Validate polymorphic model exists
        $modelClass = $request->attachable_type;
        $modelId = $request->attachable_id;
        if (!class_exists($modelClass) || !$modelClass::find($modelId)) {
            return response()->json(['message' => 'Parent model not found'], 404);
        }

        try {
            $attachment = $this->attachmentService->upload(
                file: $file,
                attachableType: $request->attachable_type,
                attachableId: $request->attachable_id,
                disk: $disk,
                uploadedBy: auth()->id()
            );

            return response()->json([
                'data' => $attachment->load('uploader'),
                'message' => 'File berhasil diupload',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Upload gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single attachment with signed URL
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $attachment = Attachment::findOrFail($id);

        if (!$attachment->canBeAccessedBy(auth()->user()->tenant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $attachment]);
    }

    /**
     * Get download URL (signed URL for private files)
     */
    public function url(int $id): JsonResponse
    {
        $attachment = Attachment::findOrFail($id);

        if (!$attachment->canBeAccessedBy(auth()->user()->tenant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $signedUrl = $attachment->preview_url;

        return response()->json([
            'url' => $signedUrl,
            'expires' => now()->addMinutes(5)->toIso8601String(),
        ]);
    }

    /**
     * Stream download (for public files) or redirect to signed URL
     */
    public function download(int $id): \Illuminate\Http\Response
    {
        $attachment = Attachment::findOrFail($id);

        if (!$attachment->canBeAccessedBy(auth()->user()->tenant_id)) {
            abort(403);
        }

        if ($attachment->disk === 'public') {
            return response()->download(
                Storage::disk('public')->path($attachment->file_path),
                $attachment->file_name
            );
        }

        // Private disk - redirect to signed URL
        return redirect($attachment->signed_url);
    }

    /**
     * Preview file (inline)
     */
    public function preview(int $id)
    {
        $attachment = Attachment::findOrFail($id);

        if (!$attachment->canBeAccessedBy(auth()->user()->tenant_id)) {
            abort(403);
        }

        if ($attachment->disk === 'public') {
            $path = Storage::disk('public')->path($attachment->file_path);
        } else {
            $path = Storage::disk($attachment->disk)->path($attachment->file_path);
        }

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }

    /**
     * Delete attachment
     */
    public function destroy(int $id): JsonResponse
    {
        $attachment = Attachment::findOrFail($id);

        if (!$attachment->canBeAccessedBy(auth()->user()->tenant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->attachmentService->delete($attachment);

        return response()->json(['message' => 'Lampiran berhasil dihapus']);
    }
}
