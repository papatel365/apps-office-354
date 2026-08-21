<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\ProfilePhotoRequest;
use App\Services\CRM\ProfilePhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Profile photo service instance
     */
    protected ProfilePhotoService $profilePhotoService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ProfilePhotoService $profilePhotoService)
    {
        $this->profilePhotoService = $profilePhotoService;
    }
    /**
     * Display user profile page.
     */
    public function index(): View
    {
        $user = auth()->user();
        $company = $user->company;

        return view('crm.profile.index', compact('user', 'company'));
    }

    /**
     * Update user profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarPath = $avatar->store('avatars', 'public');
            $validated['avatar'] = '/storage/' . $avatarPath;
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
            ],
        ]);
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini tidak benar',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui',
        ]);
    }

    /**
     * Get user activity summary.
     */
    public function activity(): JsonResponse
    {
        $user = auth()->user();

        // Get recent activity counts
        $activity = [
            'total_logins' => 0,
            'last_login' => $user->last_login_at?->diffForHumans(),
            'created_at' => $user->created_at->diffForHumans(),
        ];

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }

    /**
     * Upload profile photo.
     */
    public function uploadPhoto(ProfilePhotoRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            $result = $this->profilePhotoService->upload($user, $request->file('photo'));

            return response()->json([
                'success' => true,
                'message' => 'Foto profile berhasil diupload',
                'data' => [
                    'photo_url' => $result['url'],
                    'photo_path' => $result['path'],
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('ProfileController::uploadPhoto - Exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupload foto: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete profile photo.
     */
    public function deletePhoto(): JsonResponse
    {
        $user = auth()->user();

        try {
            $result = $this->profilePhotoService->delete($user);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'photo_url' => $this->profilePhotoService->getPhotoUrl($user),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus foto: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current profile photo URL.
     */
    public function getPhoto(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'photo_url' => $this->profilePhotoService->getPhotoUrl($user),
                'has_photo' => $user->profile_photo && !$this->profilePhotoService->isDefaultAvatar($user->profile_photo),
            ],
        ]);
    }
}
