{{-- Step 3: Penempatan Staff (Unified - Create & Edit) --}}
{{-- ARCHITECTURE: Single Source of Truth is window.wizardData (employeeWizard in wizard.blade.php) --}}
@php
    $isEditMode = isset($mode) && $mode === 'edit';
    $emp = $employee ?? null;

    // Get placements from controller (for dropdown options)
    $placementsData = $placements ?? collect();
@endphp

<div x-data="placementStep()" class="bg-white rounded-xl border border-gray-200 p-6">
    <style>[x-cloak] { display: none !important; }</style>
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold mr-3">3</span>
        Penempatan Staff
    </h3>

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Lokasi Penempatan --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Lokasi Penempatan <span class="text-red-500">*</span>
                </label>
                <select name="placement[placement_id]" x-model="wizard.formData.placement.placement_id"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">Pilih Lokasi</option>
                    <template x-for="pl in placements" :key="pl.id">
                        <option :value="pl.id" x-text="pl.name + (pl.city ? ' - ' + pl.city : '')"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    <a href="{{ route('pengaturan.umum.index') }}?tab=locations" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                        <i class="fa-solid fa-external-link-alt mr-1"></i>Kelola di Pengaturan Umum
                    </a>
                </p>
                <template x-if="placements.length === 0">
                    <p class="mt-1 text-sm text-amber-600">
                        <i class="fa-solid fa-exclamation-triangle mr-1"></i>
                        Belum ada lokasi penempatan. <a href="{{ route('pengaturan.umum.index') }}?tab=locations" target="_blank" class="underline">Tambah di Pengaturan Umum</a>
                    </p>
                </template>
            </div>

            {{-- Tanggal Mulai --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tanggal Mulai Penempatan <span class="text-red-500">*</span>
                </label>
                <input type="date" name="placement[start_date]" x-model="wizard.formData.placement.start_date"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>

            {{-- Catatan --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Penempatan</label>
                <textarea name="placement[notes]" x-model="wizard.formData.placement.notes" rows="2"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                    placeholder="Catatan khusus untuk penempatan ini"></textarea>
            </div>
        </div>
    </div>
</div>

@php
    $wizardPlacementsJson = $placementsData->toJson(JSON_UNESCAPED_UNICODE);
@endphp

@push('scripts')
<script>
/**
 * PLACEMENT STEP - READ ONLY EXCEPT DROPDOWN
 *
 * ARCHITECTURE:
 * - Single Source of Truth: window.wizardData (employeeWizard in wizard.blade.php)
 * - This component:
 *   1. Loads dropdown options (placements)
 *   2. Uses wizard's formData for all values
 *
 * DO NOT:
 * - Create separate formData state
 * - Override wizard's placement data
 * - Use internal state for placement_id, start_date, or notes
 */
document.addEventListener('alpine:init', function() {
    Alpine.data('placementStep', function() {
        return {
            /**
             * Reference to wizard (single source of truth)
             */
            get wizard() {
                return window.wizardData;
            },

            /**
             * Dropdown options for placements
             * Loaded from PHP/Blade, NOT from wizard state
             */
            placements: {!! $wizardPlacementsJson !!}.map(function(p) {
                return {
                    id: String(p.id),
                    name: p.name || '',
                    code: p.code || '',
                    address: p.address || '',
                    city: p.city || ''
                };
            }),

            /**
             * INIT - READ ONLY
             * Data is already populated by wizard's populateFromEmployee()
             * This component only ensures dropdown options are available
             */
            init: function() {
                console.log('[PLACEMENT] Initialized - using wizard data as source of truth');
                console.log('[PLACEMENT] Current placement_id:', this.wizard?.formData?.placement?.placement_id);
                console.log('[PLACEMENT] Current notes:', this.wizard?.formData?.placement?.notes);
            }
        };
    });
});
</script>
@endpush
