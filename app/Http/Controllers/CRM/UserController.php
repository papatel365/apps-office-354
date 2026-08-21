<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Modules\System\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Search users for autocomplete.
     */
    public function search(Request $request): JsonResponse
    {
        $users = User::active()
            ->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%")
                  ->orWhere('email', 'like', "%{$request->q}%");
            })
            ->limit(10)
            ->get(['id', 'uuid', 'name', 'email', 'avatar_url']);

        return $this->success($users);
    }
}
