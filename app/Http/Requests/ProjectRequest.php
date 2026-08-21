<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:not_started,in_progress,on_hold,cancelled,completed',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:start_date',
            'billing_type' => 'nullable|in:fixed,hourly,task_based',
            'budget' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'progress_percent' => 'nullable|integer|min:0|max:100',
            'visible_to_client' => 'nullable|boolean',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
            'tags' => 'nullable|string|max:500',
        ];
    }
}
