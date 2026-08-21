<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'employee_id',
        'document_type',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'issued_date',
        'expiry_date',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'file_size' => 'integer',
    ];

    // Document types
    const TYPE_KTP = 'ktp';
    const TYPE_KK = 'kk';
    const TYPE_CV = 'cv';
    const TYPE_IJAZAH = 'ijazah';
    const TYPE_SERTIFIKAT = 'sertifikat';
    const TYPE_KONTRAK = 'kontrak';
    const TYPE_SURAT_PERINGATAN = 'surat_peringatan';
    const TYPE_PROMOSI = 'promosi';
    const TYPE_MUTASI = 'mutasi';
    const TYPE_OTHER = 'other';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeActive($q)
    {
        return $q->where(function ($query) {
            $query->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
        });
    }

    public function scopeExpiring($q)
    {
        return $q->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)]);
    }

    public function scopeExpired($q)
    {
        return $q->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expiry_date &&
               $this->expiry_date->isFuture() &&
               $this->expiry_date->diffInDays(now()) <= 30;
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match($this->document_type) {
            self::TYPE_KTP => 'KTP',
            self::TYPE_KK => 'Kartu Keluarga',
            self::TYPE_CV => 'CV / Resume',
            self::TYPE_IJAZAH => 'Ijazah',
            self::TYPE_SERTIFIKAT => 'Sertifikat',
            self::TYPE_KONTRAK => 'Kontrak Kerja',
            self::TYPE_SURAT_PERINGATAN => 'Surat Peringatan',
            self::TYPE_PROMOSI => 'Surat Promosi',
            self::TYPE_MUTASI => 'Surat Mutasi',
            default => 'Lainnya',
        };
    }
}
