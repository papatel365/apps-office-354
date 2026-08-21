<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Log;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get valid statuses from Model for consistency
        $validStatuses = Asset::getValidStatusesString();

        Log::info('[ASSET REQUEST] Valid statuses from Model: ' . $validStatuses);
        Log::info('[ASSET REQUEST] Status from request: ' . ($this->input('status') ?? 'NULL'));

        $rules = [
            'category_id' => 'required|exists:asset_categories,id',
            // Name is now auto-generated, no longer required in input
            // For update, we may still accept it if needed
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'serial_number' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_expires' => 'nullable|date|after:purchase_date',
            'expected_lifespan_months' => 'nullable|integer|min:1',
            'depreciation_method' => 'nullable|in:none,straight_line,declining_balance',
            'salvage_value' => 'nullable|numeric|min:0',
            // FIXED: Use all valid statuses from Model constant
            'status' => 'nullable|in:' . $validStatuses,
            'allocated_to' => 'nullable|string|max:255',
            'maintenance_duration_days' => 'nullable|integer|min:1|max:365',
            'maintenance_start_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'custom_fields' => 'nullable|array',

            // New fields for Fisik/Akses categorization
            'product_name' => 'nullable|string|max:255',
            'access_type' => 'nullable|in:lisensi,ipam,upass',

            // License fields
            'license_name' => 'nullable|string|max:255',
            'license_key' => 'nullable|string|max:500',
            'license_vendor' => 'nullable|string|max:255',
            'license_start_date' => 'nullable|date',
            'license_end_date' => 'nullable|date|after_or_equal:license_start_date',

            // IPAM fields
            'ipam_ip_address' => 'nullable|ip',
            'ipam_subnet' => 'nullable|string|max:45',
            'ipam_gateway' => 'nullable|ip',
            'ipam_vlan' => 'nullable|string|max:50',
            'ipam_hostname' => 'nullable|string|max:255',
            'ipam_network' => 'nullable|string|max:100',

            // Upass fields
            'access_username' => 'nullable|string|max:255',
            'access_password' => 'nullable|string|max:255',

            // Photo validation rules - multiple photos without hardcoded limit
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB per file
            'remove_photos' => 'nullable|array',
            'remove_photos.*' => 'exists:asset_photos,id',

            // Single photo (legacy support)
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_photo' => 'nullable|boolean',

            // Warranty card (optional)
            'warranty_card' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_warranty_card' => 'nullable|boolean',
        ];

        // For update requests, make photo optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['photo'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120';
            $rules['warranty_card'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120';
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $categoryId = $this->input('category_id');

            Log::info('[ASSET REQUEST VALIDATOR] Category ID from input: ' . ($categoryId ?? 'null'));

            if (!$categoryId) {
                Log::info('[ASSET REQUEST VALIDATOR] No category ID, skipping category-based validation');
                return;
            }

            // Get category from database - this is the source of truth
            $category = AssetCategory::find($categoryId);

            Log::info('[ASSET REQUEST VALIDATOR] Category from DB: ' . ($category ? $category->name . ' (type: ' . $category->category_type . ')' : 'NOT FOUND'));

            if (!$category) {
                Log::info('[ASSET REQUEST VALIDATOR] Category not found in DB, skipping category-based validation');
                return;
            }

            $categoryType = $category->category_type;

            Log::info('[ASSET REQUEST VALIDATOR] Category type: ' . $categoryType);

            // Validation based on category type from DATABASE (not from browser input)
            if ($categoryType === 'fisik') {
                Log::info('[ASSET REQUEST VALIDATOR] Validating FISIK category');
                // For physical category, product_name should be filled
                $productName = $this->input('product_name');
                Log::info('[ASSET REQUEST VALIDATOR] product_name from input: ' . ($productName ?? 'null'));
                if (empty($productName)) {
                    Log::info('[ASSET REQUEST VALIDATOR] Adding validation error for product_name');
                    $validator->errors()->add('product_name', 'Nama Produk / Barang wajib diisi untuk kategori Fisik.');
                }
            } elseif ($categoryType === 'akses') {
                Log::info('[ASSET REQUEST VALIDATOR] Validating AKSES category');
                // For access category, access_type should be selected
                $accessType = $this->input('access_type');
                Log::info('[ASSET REQUEST VALIDATOR] access_type from input: ' . ($accessType ?? 'null'));
                if (empty($accessType)) {
                    Log::info('[ASSET REQUEST VALIDATOR] Adding validation error for access_type');
                    $validator->errors()->add('access_type', 'Jenis Akses wajib dipilih untuk kategori Akses.');
                }

                // Additional validation based on access_type
                if ($accessType === 'upass') {
                    Log::info('[ASSET REQUEST VALIDATOR] Validating UPASS subtype');
                    $username = $this->input('access_username');
                    Log::info('[ASSET REQUEST VALIDATOR] access_username from input: ' . ($username ?? 'null'));
                    if (empty($username)) {
                        Log::info('[ASSET REQUEST VALIDATOR] Adding validation error for access_username');
                        $validator->errors()->add('access_username', 'Username wajib diisi untuk jenis akses Upass.');
                    }
                }
            } else {
                Log::info('[ASSET REQUEST VALIDATOR] Unknown category type: ' . $categoryType);
            }

            Log::info('[ASSET REQUEST VALIDATOR] Current errors: ' . json_encode($validator->errors()->toArray()));
        });
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori asset wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus: JPG, JPEG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
            'photos.*.image' => 'Setiap file harus berupa gambar.',
            'photos.*.mimes' => 'Format foto harus: JPG, JPEG, PNG, atau WebP.',
            'photos.*.max' => 'Ukuran setiap foto maksimal 5 MB.',
            'warranty_card.image' => 'File kartu garansi harus berupa gambar.',
            'warranty_card.mimes' => 'Format kartu garansi harus: JPG, JPEG, PNG, atau WebP.',
            'warranty_card.max' => 'Ukuran kartu garansi maksimal 5 MB.',
            'ipam_ip_address.ip' => 'Format IP Address tidak valid.',
            'ipam_gateway.ip' => 'Format Gateway tidak valid.',
            'access_type.in' => 'Jenis Akses harus salah satu dari: Lisensi, IPAM, atau Upass.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        Log::info('[ASSET REQUEST] prepareForValidation called');

        // Ensure remove_photo is boolean
        if ($this->has('remove_photo')) {
            Log::info('[ASSET REQUEST] remove_photo detected, normalizing...');
            $this->merge([
                'remove_photo' => filter_var($this->remove_photo, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Normalize purchase_cost - remove thousand separators (dots) and convert to numeric
        if ($this->has('purchase_cost') && $this->purchase_cost !== null && $this->purchase_cost !== '') {
            Log::info('[ASSET REQUEST] purchase_cost detected: ' . $this->purchase_cost);
            // First remove "Rp" prefix (case insensitive), then remove dots and spaces
            $normalized = preg_replace('/^Rp\s*/i', '', $this->purchase_cost);
            $normalized = str_replace(['.', ' '], '', $normalized);
            Log::info('[ASSET REQUEST] purchase_cost normalized: ' . $normalized);
            // Convert to float/numeric
            if (is_numeric($normalized)) {
                $this->merge([
                    'purchase_cost' => (float) $normalized,
                ]);
                Log::info('[ASSET REQUEST] purchase_cost converted to float: ' . (float) $normalized);
            }
        }

        // Also normalize salvage_value for consistency
        if ($this->has('salvage_value') && $this->salvage_value !== null && $this->salvage_value !== '') {
            Log::info('[ASSET REQUEST] salvage_value detected: ' . $this->salvage_value);
            $normalized = preg_replace('/^Rp\s*/i', '', $this->salvage_value);
            $normalized = str_replace(['.', ' '], '', $normalized);
            if (is_numeric($normalized)) {
                $this->merge([
                    'salvage_value' => (float) $normalized,
                ]);
                Log::info('[ASSET REQUEST] salvage_value converted to float: ' . (float) $normalized);
            }
        }
    }

    /**
     * Handle a failed validation attempt.
     * Log all validation errors before throwing
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::error('========== ASSET REQUEST VALIDATION FAILED ==========');
        Log::error('VALIDATION ERRORS: ' . json_encode($validator->errors()->toArray(), JSON_PRETTY_PRINT));
        Log::error('REQUEST ALL DATA: ' . json_encode($this->all(), JSON_PRETTY_PRINT));
        Log::error('==============================================');

        parent::failedValidation($validator);
    }
}
