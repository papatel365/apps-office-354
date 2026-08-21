<?php

namespace App\Http\Requests;

use App\Modules\System\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get current user's company for validation
        $user = $this->user();
        $companyId = $user?->company_id;

        return [
            'project_id' => 'nullable|exists:projects,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|string|max:500',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:pending,in_progress,waiting_approval,completed,cancelled',
            'billable' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'duration_days' => 'nullable|integer|min:1|max:365',
            'progress' => 'nullable|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'milestone' => 'nullable|string|max:100',
            'visibility' => 'nullable|in:internal,project,client',
            'recurring_type' => 'nullable|in:weekly,biweekly,monthly,quarterly,biannually,yearly,custom',
            'recurring_interval' => 'nullable|integer|min:1|max:365',
            'recurring_custom_days' => 'nullable|date',

            // Assignee validation - only checks user exists and belongs to same company
            'assignee_data' => 'nullable|array',
            'assignee_data.*.user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($companyId) {
                    // SECURITY: Ensure assignee belongs to the same company
                    if ($companyId) {
                        $assignee = User::find($value);
                        if (!$assignee || $assignee->company_id !== $companyId) {
                            $fail('Assignee tidak terdaftar pada perusahaan aktif.');
                        }
                    }
                },
            ],
            'assignee_data.*.role' => 'nullable|string|max:50',
            'assignee_data.*.job_description' => 'nullable|string',

            // Backward compatibility - assignee_ids also needs validation
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => [
                'exists:users,id',
                function ($attribute, $value, $fail) use ($companyId) {
                    // SECURITY: Ensure assignee belongs to the same company
                    if ($companyId) {
                        $assignee = User::find($value);
                        if (!$assignee || $assignee->company_id !== $companyId) {
                            $fail('Assignee tidak terdaftar pada perusahaan aktif.');
                        }
                    }
                },
            ],

            // Followers
            'follower_ids' => 'nullable|array',
            'follower_ids.*' => 'exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'assignee_data.*.user_id.exists' => 'User assignee tidak ditemukan.',
            'assignee_ids.*.exists' => 'User assignee tidak ditemukan.',
            'assignee_data.*.user_id.required' => 'User assignee wajib dipilih.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $companyId = $user?->company_id;

            // Additional validation: check if any assignee_ids contain users from other companies
            if ($this->has('assignee_ids') && is_array($this->assignee_ids)) {
                foreach ($this->assignee_ids as $userId) {
                    $assignee = User::find($userId);

                    // Check company
                    if (!$assignee || ($companyId && $assignee->company_id !== $companyId)) {
                        $validator->errors()->add(
                            'assignee_ids',
                            'Salah satu assignee tidak terdaftar pada perusahaan aktif.'
                        );
                        break;
                    }
                }
            }

            // Additional validation: check assignee_data company
            if ($this->has('assignee_data') && is_array($this->assignee_data)) {
                foreach ($this->assignee_data as $index => $assignee) {
                    if (isset($assignee['user_id'])) {
                        $assigneeUser = User::find($assignee['user_id']);

                        if ($assigneeUser) {
                            // Check company
                            if ($companyId && $assigneeUser->company_id !== $companyId) {
                                $validator->errors()->add(
                                    "assignee_data.{$index}.user_id",
                                    'Assignee tidak terdaftar pada perusahaan aktif.'
                                );
                            }
                        }
                    }
                }
            }
        });
    }
}
