<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Modules\System\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectMemberController extends Controller
{
    /**
     * Get available users for adding to project.
     * Returns ALL active users from the tenant/company who are NOT already members.
     */
    public function availableUsers(Request $request, Project $project): JsonResponse
    {
        $tenantId = $project->tenant_id;
        $companyId = $project->company_id;
        $existingMemberIds = $project->members()->pluck('user_id')->toArray();

        // Get ALL active users from tenant or company
        $usersQuery = User::query()
            ->where(function ($q) use ($tenantId, $companyId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhere('company_id', $companyId);
            })
            ->where('is_active', true)
            ->whereNotIn('id', $existingMemberIds)
            ->orderBy('name');

        $users = $usersQuery->get(['id', 'name', 'email'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            });

        return response()->json([
            'success' => true,
            'users' => $users,
            'total' => $users->count(),
        ]);
    }

    /**
     * Store multiple project members at once.
     * Accepts an array of user_ids and adds all valid ones.
     * Partial failures are handled gracefully - valid ones are added, invalid ones are reported.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
            'role' => 'nullable|string|max:50',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $userIds = $request->user_ids;
        $role = $request->role ?? 'member';
        $hourlyRate = $request->hourly_rate ?? 0;

        // Get existing member IDs to filter out duplicates
        $existingMemberIds = $project->members()->pluck('user_id')->toArray();

        // Separate user IDs into new (to add) and existing (to skip)
        $newUserIds = array_diff($userIds, $existingMemberIds);
        $duplicateUserIds = array_intersect($userIds, $existingMemberIds);

        // Get user details for the users being added
        $newUsers = User::whereIn('id', $newUserIds)->get()->keyBy('id');

        $addedMembers = [];
        $errors = [];

        // Add each new member
        foreach ($newUserIds as $userId) {
            try {
                $member = DB::transaction(function () use ($project, $userId, $role, $hourlyRate, $newUsers) {
                    $member = ProjectMember::create([
                        'project_id' => $project->id,
                        'user_id' => $userId,
                        'tenant_id' => $project->tenant_id,
                        'role' => $role,
                        'hourly_rate' => $hourlyRate,
                    ]);

                    // Log activity
                    $userName = $newUsers[$userId]->name ?? 'Unknown';
                    $project->logActivity(
                        'Menambahkan anggota ' . $userName . ' sebagai ' . $role,
                        'project',
                        'member_added',
                        [
                            'member_id' => $member->id,
                            'user_id' => $userId,
                            'role' => $role,
                        ]
                    );

                    return [
                        'id' => $member->id,
                        'user_id' => $member->user_id,
                        'role' => $member->role,
                        'hourly_rate' => $member->hourly_rate,
                        'user' => [
                            'id' => $newUsers[$userId]->id,
                            'name' => $newUsers[$userId]->name,
                            'email' => $newUsers[$userId]->email,
                        ],
                    ];
                });

                $addedMembers[] = $member;
            } catch (\Exception $e) {
                Log::error('Failed to add project member', [
                    'project_id' => $project->id,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = 'Gagal menambahkan user ID ' . $userId;
            }
        }

        // Get names of duplicate users
        $duplicateNames = User::whereIn('id', $duplicateUserIds)->pluck('name')->toArray();

        // Build response message
        $messages = [];
        if (count($addedMembers) > 0) {
            $messages[] = count($addedMembers) . ' anggota berhasil ditambahkan';
        }
        if (count($duplicateUserIds) > 0) {
            $messages[] = count($duplicateUserIds) . ' anggota sudah ada sebelumnya';
        }
        if (count($errors) > 0) {
            $messages[] = count($errors) . ' anggota gagal ditambahkan';
        }

        return response()->json([
            'success' => count($addedMembers) > 0,
            'message' => implode('. ', $messages),
            'added_count' => count($addedMembers),
            'duplicate_count' => count($duplicateUserIds),
            'error_count' => count($errors),
            'members' => $addedMembers,
            'duplicates' => $duplicateNames,
        ], count($addedMembers) > 0 ? 201 : 400);
    }

    /**
     * Update the specified project member.
     */
    public function update(Request $request, Project $project, ProjectMember $member): JsonResponse
    {
        // Verify member belongs to this project
        if ($member->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anggota tidak ditemukan di project ini',
            ], 404);
        }

        $request->validate([
            'role' => 'nullable|string|max:50',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $oldRole = $member->role;
        $member->update($request->only(['role', 'hourly_rate']));

        // Log activity if role changed
        if ($oldRole !== $member->role) {
            $project->logActivity(
                'Mengubah role ' . $member->user->name . ' dari ' . $oldRole . ' menjadi ' . $member->role,
                'project',
                'member_role_changed',
                [
                    'member_id' => $member->id,
                    'old_role' => $oldRole,
                    'new_role' => $member->role,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Anggota berhasil diperbarui',
            'member' => $member->load('user'),
        ]);
    }

    /**
     * Remove the specified project member.
     */
    public function destroy(Project $project, ProjectMember $member): JsonResponse
    {
        // Verify member belongs to this project
        if ($member->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anggota tidak ditemukan di project ini',
            ], 404);
        }

        $memberName = $member->user->name;
        $memberId = $member->id;

        $member->delete();

        // Log activity
        $project->logActivity(
            'Menghapus anggota ' . $memberName,
            'project',
            'member_removed',
            [
                'member_id' => $memberId,
                'user_name' => $memberName,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Anggota berhasil dihapus',
        ]);
    }
}
