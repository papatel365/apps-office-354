<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class UserDisplayNameComposer
{
    /**
     * Bind display_name to all views.
     * This ensures the employee relation is eager loaded to avoid N+1 queries.
     */
    public function compose(View $view): void
    {
        $user = Auth::user();

        if ($user) {
            // Eager load employee relation to avoid N+1 query
            $user->loadMissing('employee');

            // Use the display_name accessor which already has the fallback logic
            $view->with('displayName', $user->display_name);
        } else {
            $view->with('displayName', 'Guest');
        }
    }
}
