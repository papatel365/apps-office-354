<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectMember;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function index(Request $request, $projectId)
    {
        $members = ProjectMember::where('project_id', $projectId)->with('user')->get();
        return response()->json(['data' => $members]);
    }

    public function store(Request $request, $projectId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|max:100',
        ]);

        $exists = ProjectMember::where('project_id', $projectId)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'User already a member'], 400);
        }

        $member = ProjectMember::create([
            'project_id' => $projectId,
            'user_id' => $request->user_id,
            'role' => $request->role,
        ]);

        return response()->json(['data' => $member->load('user'), 'message' => 'Member added successfully'], 201);
    }

    public function destroy($projectId, $id)
    {
        $member = ProjectMember::where('project_id', $projectId)->findOrFail($id);
        $member->delete();

        return response()->json(['message' => 'Member removed successfully']);
    }
}
