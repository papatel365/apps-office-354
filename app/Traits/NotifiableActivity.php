<?php

namespace App\Traits;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

trait NotifiableActivity
{
    /**
     * Boot the trait.
     */
    public static function bootNotifiableActivity()
    {
        // Create notification on create
        static::created(function ($model) {
            try {
                $notificationService = app(NotificationService::class);
                $companyId = $model->getCompanyId();

                if ($companyId) {
                    $notificationService->notifyModelEvent('created', get_class($model), $model, $companyId);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create notification for created event: ' . $e->getMessage());
            }
        });

        // Create notification on update (only if there are actual changes)
        static::updated(function ($model) {
            try {
                // Only notify for significant changes (not just timestamps)
                $changes = $model->getChanges();
                unset($changes['updated_at']); // Remove timestamp changes

                if (empty($changes)) {
                    return;
                }

                $notificationService = app(NotificationService::class);
                $companyId = $model->getCompanyId();

                if ($companyId) {
                    $notificationService->notifyModelEvent('updated', get_class($model), $model, $companyId);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create notification for updated event: ' . $e->getMessage());
            }
        });

        // Create notification on delete
        static::deleted(function ($model) {
            try {
                $notificationService = app(NotificationService::class);
                $companyId = $model->getCompanyId();

                if ($companyId) {
                    $notificationService->notifyModelEvent('deleted', get_class($model), $model, $companyId);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create notification for deleted event: ' . $e->getMessage());
            }
        });
    }

    /**
     * Get the company ID for the notification.
     * Override this method in models that don't follow the standard pattern.
     */
    public function getCompanyId(): ?int
    {
        // Check for company_id directly
        if (isset($this->company_id)) {
            return $this->company_id;
        }

        // Check for company relation
        if (method_exists($this, 'company') && $this->company) {
            return $this->company->id;
        }

        // Check for BelongsToCompany trait
        if (in_array(\App\Core\Traits\BelongsToCompany::class, class_uses_recursive($this))) {
            return $this->company_id ?? null;
        }

        // Check user's company
        if (method_exists($this, 'user') && $this->user) {
            return $this->user->company_id;
        }

        // Check if model has company relation
        $relations = ['company', 'employee', 'user'];
        foreach ($relations as $relation) {
            if (method_exists($this, $relation) && $this->{$relation}) {
                if (isset($this->{$relation}->company_id)) {
                    return $this->{$relation}->company_id;
                }
            }
        }

        return null;
    }

    /**
     * Check if notifications should be created for this model.
     */
    public function shouldCreateNotification(): bool
    {
        return true;
    }
}
