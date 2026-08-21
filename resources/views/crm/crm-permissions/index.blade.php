{{-- resources/views/crm/crm-permissions/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    [x-cloak] { display: none !important; }
    .user-card { transition: all 0.2s; cursor: pointer; }
    .user-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .role-badge { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; }
    .role-owner { background-color: #fef3c7; color: #92400e; }
    .role-developer { background-color: #dbeafe; color: #1e40af; }
    .role-director { background-color: #ede9fe; color: #5b21b6; }
    .role-admin { background-color: #d1fae5; color: #065f46; }
    .role-manager { background-color: #e0e7ff; color: #3730a3; }
    .role-staff { background-color: #f3f4f6; color: #4b5563; }
    .superadmin-badge { background: linear-gradient(135deg,#f59e0b 0%,#ef4444 100%); color: white; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem; }
    .modal-content { background: white; border-radius: 16px; width: 100%; max-width: 800px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
    .modal-body { padding: 0; overflow-y: auto; flex: 1; }
    .modal-footer { padding: 16px 24px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; gap: 12px; flex-shrink: 0; }
    .perm-section { border-bottom: 1px solid #e5e7eb; padding: 20px 24px; }
    .perm-section:last-child { border-bottom: none; }
    .perm-section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.5px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
    .perm-group { border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 10px; overflow: hidden; }
    .perm-group-header { display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: #f9fafb; cursor: pointer; user-select: none; }
    .perm-group-header:hover { background: #f3f4f6; }
    .perm-group-toggle { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; transition: transform 0.2s; color: #6b7280; }
    .perm-group-toggle.expanded { transform: rotate(90deg); }
    .perm-group-icon { width: 32px; height: 32px; background: #e0e7ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4f46e5; font-size: 14px; }
    .perm-group-name { flex: 1; font-weight: 600; font-size: 14px; color: #111827; }
    .perm-group-body { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; border-top: 1px solid transparent; }
    .perm-group-body.expanded { max-height: 500px; border-top-color: #e5e7eb; }
    .perm-group-content { padding: 14px; }
    .perm-item { background: white; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 10px; overflow: hidden; }
    .perm-item:last-child { margin-bottom: 0; }
    .perm-item-header { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #f9fafb; cursor: pointer; user-select: none; }
    .perm-item-header:hover { background: #f3f4f6; }
    .perm-item-toggle { width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; transition: transform 0.2s; color: #6b7280; }
    .perm-item-toggle.expanded { transform: rotate(90deg); }
    .perm-item-icon { width: 28px; height: 28px; background: #e0e7ff; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #4f46e5; font-size: 12px; }
    .perm-item-name { flex: 1; font-weight: 600; font-size: 13px; color: #111827; }
    .perm-item-body { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; border-top: 1px solid transparent; }
    .perm-item-body.expanded { max-height: 300px; border-top-color: #e5e7eb; }
    .perm-item-content { padding: 12px; }
    .perm-scope-row { display: flex; align-items: center; gap: 20px; margin-bottom: 10px; }
    .perm-scope-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.5px; min-width: 100px; }
    .perm-scope-options { display: flex; gap: 20px; }
    .perm-scope-option { display: flex; align-items: center; gap: 6px; cursor: pointer; }
    .perm-scope-option input { accent-color: #4f46e5; cursor: pointer; }
    .perm-scope-option span { font-size: 13px; color: #374151; cursor: pointer; }
    .perm-cud-row { display: flex; gap: 8px; flex-wrap: wrap; }
    .perm-cud-item { display: flex; align-items: center; gap: 6px; padding: 6px 10px; background: #f9fafb; border-radius: 6px; cursor: pointer; transition: background 0.15s; }
    .perm-cud-item:hover { background: #e5e7ff; }
    .perm-cud-item input { accent-color: #4f46e5; cursor: pointer; }
    .perm-cud-item span { font-size: 12px; color: #374151; cursor: pointer; }
    .perm-checkbox { width: 16px; height: 16px; accent-color: #4f46e5; cursor: pointer; }
    .perm-superadmin-notice { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 14px 16px; margin: 16px 24px; display: flex; align-items: flex-start; gap: 12px; }
    .perm-superadmin-icon { width: 36px; height: 36px; background: #f59e0b; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; }
    .perm-superadmin-content h4 { font-weight: 600; color: #92400e; margin-bottom: 2px; font-size: 13px; }
    .perm-superadmin-content p { font-size: 12px; color: #b45309; }
    @media (max-width: 640px) {
        .perm-scope-row { flex-direction: column; align-items: flex-start; gap: 8px; }
        .perm-scope-options { flex-direction: column; gap: 6px; }
    }
</style>
@endpush

@section('title', 'Hak Akses')
@section('page-title', 'Hak Akses')

{{-- @push('page-actions')
    <a href="{{ route('beranda') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
        <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
    </a>
@endpush --}}

@section('content')
{{-- Header --}}
{{-- <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-6 mb-6 text-white">
    <div class="flex items-start justify-between">
        <div>
            <h2 class="text-xl font-bold mb-2">Pengaturan Hak Akses</h2>
            <p class="text-indigo-100 text-sm">Kelola hak akses setiap pengguna untuk menu CRM.</p>
        </div>
        <div class="bg-white/20 rounded-lg p-3">
            <i class="fa-solid fa-shield-halved text-2xl"></i>
        </div>
    </div>
</div> --}}

{{-- Info Cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-users text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ $users->count() }}</p>
                <p class="text-sm text-gray-500">Total Pengguna</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-user-check text-green-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ $users->where('is_superadmin', false)->count() }}</p>
                <p class="text-sm text-gray-500">Pengguna Aktif</p>
            </div>
        </div>
    </div>
    <!-- <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-crown text-amber-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ $users->where('is_superadmin', true)->count() }}</p>
                <p class="text-sm text-gray-500">Super Admin</p>
            </div>
        </div>
    </div> -->
</div>

{{-- Users Grid --}}
<div class="mb-4">
    <h3 class="text-lg font-semibold text-gray-900">Daftar Pengguna</h3>
    <p class="text-sm text-gray-500">Klik pada pengguna untuk mengatur perizinannya</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach($users as $u)
        <div class="user-card bg-white rounded-xl border border-gray-200 p-4"
             onclick="openPermissionModal({{ $u->id }}, '{{ addslashes($u->employee_full_name ?? $u->name) }}', '{{ \App\Helpers\RoleHelper::label($u->company_role ?? $u->user_type) }}')">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr($u->employee_full_name ?? $u->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $u->employee_full_name ?? $u->name }}</p>
                        <p class="text-xs text-gray-500">{{ $u->email }}</p>
                    </div>
                </div>
                @if(!($u->employee_is_active ?? true))
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                        <i class="fa-solid fa-user-clock mr-1"></i> Nonaktif
                    </span>
                @endif
            </div>

            <div class="flex items-center justify-between">
                <span class="role-badge role-{{ $u->company_role ?? $u->user_type ?? 'staff' }}">
                    {{ \App\Helpers\RoleHelper::label($u->company_role ?? $u->user_type) }}
                </span>
                @if($u->is_superadmin)
                    <span class="superadmin-badge">
                        <i class="fa-solid fa-crown mr-1"></i> Full Access
                    </span>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection

{{-- Permission Modal --}}
@section('modal')
<div id="permModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Atur Hak Akses</h3>
                <p class="text-sm text-gray-500 mt-0.5" id="permUserName">-</p>
            </div>
            <button onclick="closePermModal()" class="p-2 hover:bg-gray-100 rounded-lg">
                <i class="fa-solid fa-times text-gray-400"></i>
            </button>
        </div>

        <div class="modal-body">
            {{-- User Info --}}
            <div class="flex items-center gap-3 px-6 py-4 bg-gray-50 border-b border-gray-200">
                <div id="permUserAvatar" class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center text-white font-bold text-sm"></div>
                <div>
                    <p id="permUserName2" class="font-semibold text-gray-900 text-sm"></p>
                    <p id="permUserEmail" class="text-xs text-gray-500"></p>
                </div>
                <span id="permUserRole" class="role-badge ml-auto"></span>
            </div>

            {{-- Superadmin Notice --}}
            <!-- <div id="permSuperadminNotice" class="perm-superadmin-notice hidden">
                <div class="perm-superadmin-icon">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <div class="perm-superadmin-content">
                    <h4>Pengguna Super Admin</h4>
                    <p>Pengguna ini memiliki akses penuh. Hak Akses tidak dapat diubah.</p>
                </div>
            </div> -->

            {{-- Permission Content - SINGLE RENDER --}}
            <div id="permContent" style="display:none;">
                <div id="permGroups"></div>
            </div>
        </div>

        <div class="modal-footer">
            <button onclick="closePermModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                Batal
            </button>
            <button id="permResetBtn" onclick="resetPerms()" class="px-4 py-2 bg-amber-100 text-amber-700 rounded-lg hover:bg-amber-200 text-sm">
                <i class="fa-solid fa-rotate-left mr-1"></i> Reset
            </button>
            <button id="permSaveBtn" onclick="savePerms()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                <i class="fa-solid fa-save mr-1"></i> Simpan
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * UNIFIED PERMISSION ARCHITECTURE
 *
 * DATA STRUCTURE:
 * permissions = {
 *     projects: { sidebar: true, scope_own: true, scope_global: false, can_view: true, can_create: true, can_update: true, can_delete: true },
 *     tasks: { sidebar: false, scope_own: true, ... },
 *     ...
 * }
 *
 * FLOW:
 * 1. Load: GET /crm/permissions/{id} -> permissions object
 * 2. Render: renderPermissions(permissions, structure) -> HTML
 * 3. Save: savePerms() -> PUT /crm/permissions/{id} -> { permissions: {...} }
 */

// =====================================================
// GLOBAL STATE - SATU OBJECT
// =====================================================
window._permData = null;  // { user, is_superadmin, can_edit, permissions, structure }

// =====================================================
// OPEN MODAL
// =====================================================
function openPermissionModal(userId, userName, userRole) {
    console.log('[PERM] Opening modal for user:', userId, userName);

    const modal = document.getElementById('permModal');
    if (!modal) {
        console.error('[PERM] Modal not found');
        return;
    }

    // Show modal
    modal.style.display = 'flex';

    // Reset state
    window._permData = null;

    // Hide content initially
    const contentEl = document.getElementById('permContent');
    if (contentEl) contentEl.style.display = 'none';

    // Show loading
    const userNameEl = document.getElementById('permUserName');
    if (userNameEl) userNameEl.textContent = userName + ' (Memuat...)';

    // Fetch data
    console.log('[PERM] Fetching permissions from server...');
    fetch('{{ route('pengaturan.hak_akses.show', ['userId' => ':userId']) }}'.replace(':userId', userId), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => {
        console.log('[PERM] Response status:', r.status);
        return r.json();
    })
    .then(res => {
        console.log('[PERM] Server response:', res);

        if (res.success) {
            const data = res.data;

            // Store unified permission object
            window._permData = data;

            console.log('[PERM] Permissions loaded:', Object.keys(data.permissions).length, 'modules');
            console.log('[PERM] Sample permission:', data.permissions['projects']);

            // Set user info
            const user = data.user || {};
            const firstChar = (user.name || '-').charAt(0).toUpperCase();
            document.getElementById('permUserAvatar').textContent = firstChar;
            document.getElementById('permUserName2').textContent = user.name || '-';
            document.getElementById('permUserEmail').textContent = user.email || '-';
            document.getElementById('permUserName').textContent = user.name;

            const roleEl = document.getElementById('permUserRole');
            if (roleEl) {
                const role = user.role || 'staff';
                roleEl.textContent = role.charAt(0).toUpperCase() + role.slice(1);
                roleEl.className = 'role-badge role-' + role;
            }

            // Show/hide based on permissions
            const noticeEl = document.getElementById('permSuperadminNotice');
            const saveBtn = document.getElementById('permSaveBtn');
            const resetBtn = document.getElementById('permResetBtn');

            if (data.is_superadmin || !data.can_edit) {
                if (noticeEl) noticeEl.classList.remove('hidden');
                if (saveBtn) saveBtn.style.display = 'none';
                if (resetBtn) resetBtn.style.display = 'none';
            } else {
                if (noticeEl) noticeEl.classList.add('hidden');
                if (saveBtn) saveBtn.style.display = '';
                if (resetBtn) resetBtn.style.display = '';
            }

            // =====================================================
            // SINGLE RENDER - renderPermissions()
            // =====================================================
            console.log('[PERM] Rendering permissions...');
            renderPermissions(data.permissions, data.structure);

            if (contentEl) contentEl.style.display = 'block';
            console.log('[PERM] Render complete');

        } else {
            console.error('[PERM] Server error:', res.message);
            alert('Gagal: ' + (res.message || 'Error'));
            closePermModal();
        }
    })
    .catch(err => {
        console.error('[PERM] Fetch error:', err);
        alert('Terjadi kesalahan saat memuat');
        closePermModal();
    });
}

// =====================================================
// SINGLE RENDER FUNCTION
// =====================================================
function renderPermissions(permissions, structure) {
    const container = document.getElementById('permGroups');
    if (!container) {
        console.error('[RENDER] Container permGroups not found');
        return;
    }

    let html = '';

    for (const group of structure) {
        if (!group || !group.children) continue;

        html += `
        <div class="perm-section">
            <div class="perm-section-title">
                <i class="fa-solid ${group.icon || 'fa-folder'}"></i>
                ${group.label}
            </div>
        `;

        for (const child of group.children) {
            if (!child) continue;

            const moduleKey = child.key;
            const perm = permissions[moduleKey] || {
                sidebar: false,
                scope_own: false,
                scope_global: false,
                can_view: false,
                can_create: false,
                can_update: false,
                can_delete: false
            };

            console.log(`[RENDER] Module: ${moduleKey}, permission:`, perm);

            html += `
            <div class="perm-group">
                <div class="perm-group-header" onclick="toggleGroup('g_${moduleKey}', this)">
                    <div class="perm-group-toggle" id="g_toggle_${moduleKey}">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </div>
                    <div class="perm-group-icon"><i class="fa-solid ${child.icon || 'fa-file'}"></i></div>
                    <div class="perm-group-name">${child.label}</div>
                    <input type="checkbox" class="perm-checkbox"
                        data-module="${moduleKey}"
                        data-field="sidebar"
                        ${perm.sidebar ? 'checked' : ''}
                        onchange="updatePermission('${moduleKey}', 'sidebar', this.checked)">
                </div>
                <div class="perm-group-body" id="g_body_${moduleKey}">
                    <div class="perm-group-content">
                        <div class="perm-item">
                            <div class="perm-item-content">
                                <div class="perm-scope-row">
                                    <div class="perm-scope-label">HAK AKSES</div>
                                    <div class="perm-scope-options">
                                        <label class="perm-scope-option">
                                            <input type="checkbox" class="perm-checkbox"
                                                data-module="${moduleKey}"
                                                data-field="scope_own"
                                                ${perm.scope_own ? 'checked' : ''}
                                                onchange="updatePermission('${moduleKey}', 'scope_own', this.checked)">
                                            <span>Milik Sendiri</span>
                                        </label>
                                        <label class="perm-scope-option">
                                            <input type="checkbox" class="perm-checkbox"
                                                data-module="${moduleKey}"
                                                data-field="scope_global"
                                                ${perm.scope_global ? 'checked' : ''}
                                                onchange="updatePermission('${moduleKey}', 'scope_global', this.checked)">
                                            <span>Global</span>
                                        </label>
                                    </div>
                                </div>
                                {{-- HIDDEN: Action Permission (Buat/Edit/Hapus) - Feature Flag --}}
                                {{-- Untuk mengaktifkan kembali, hapus: style="display: none;" --}}
                                <div class="perm-cud-row" style="display: none;">
                                    <label class="perm-cud-item">
                                        <input type="checkbox" class="perm-checkbox"
                                            data-module="${moduleKey}"
                                            data-field="can_create"
                                            ${perm.can_create ? 'checked' : ''}
                                            onchange="updatePermission('${moduleKey}', 'can_create', this.checked)">
                                        <span>Buat</span>
                                    </label>
                                    <label class="perm-cud-item">
                                        <input type="checkbox" class="perm-checkbox"
                                            data-module="${moduleKey}"
                                            data-field="can_update"
                                            ${perm.can_update ? 'checked' : ''}
                                            onchange="updatePermission('${moduleKey}', 'can_update', this.checked)">
                                        <span>Ubah</span>
                                    </label>
                                    <label class="perm-cud-item">
                                        <input type="checkbox" class="perm-checkbox"
                                            data-module="${moduleKey}"
                                            data-field="can_delete"
                                            ${perm.can_delete ? 'checked' : ''}
                                            onchange="updatePermission('${moduleKey}', 'can_delete', this.checked)">
                                        <span>Hapus</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            `;
        }

        html += '</div>';
    }

    container.innerHTML = html;
    console.log('[RENDER] Complete - rendered', Object.keys(permissions).length, 'modules');
}

// =====================================================
// UPDATE PERMISSION (called when checkbox changes)
// =====================================================
function updatePermission(moduleKey, field, value) {
    if (!window._permData || !window._permData.permissions) {
        console.error('[UPDATE] No permission data');
        return;
    }

    console.log(`[UPDATE] ${moduleKey}.${field} = ${value}`);

    // Update the unified permission object
    if (!window._permData.permissions[moduleKey]) {
        window._permData.permissions[moduleKey] = {
            sidebar: false,
            scope_own: false,
            scope_global: false,
            can_view: false,
            can_create: false,
            can_update: false,
            can_delete: false
        };
    }

    window._permData.permissions[moduleKey][field] = value;

    // If sidebar is unchecked, reset all other permissions
    if (field === 'sidebar' && !value) {
        window._permData.permissions[moduleKey] = {
            sidebar: false,
            scope_own: false,
            scope_global: false,
            can_view: false,
            can_create: false,
            can_update: false,
            can_delete: false
        };
        // Re-render to show all checkboxes unchecked
        renderPermissions(window._permData.permissions, window._permData.structure);
    }

    console.log('[UPDATE] Current state:', window._permData.permissions);
}

// =====================================================
// TOGGLE GROUP EXPAND/COLLAPSE
// =====================================================
function toggleGroup(groupId, header) {
    const body = document.getElementById('g_body_' + groupId.replace('g_', ''));
    if (body) {
        body.classList.toggle('expanded');
    }
    if (header) {
        const toggle = header.querySelector('.perm-group-toggle');
        if (toggle) {
            toggle.classList.toggle('expanded');
        }
    }
}

// =====================================================
// CLOSE MODAL
// =====================================================
function closePermModal() {
    console.log('[PERM] Closing modal');
    const modal = document.getElementById('permModal');
    if (modal) modal.style.display = 'none';
    window._permData = null;
}

// =====================================================
// SAVE PERMISSIONS
// =====================================================
function savePerms() {
    console.log('[SAVE] Starting save...');

    if (!window._permData || !window._permData.permissions) {
        console.error('[SAVE] No permission data');
        alert('Sesi habis, buka modal lagi');
        return;
    }

    const userId = window._permData.user?.id;
    if (!userId) {
        console.error('[SAVE] No user ID');
        return;
    }

    const btn = document.getElementById('permSaveBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan...';
    }

    // =====================================================
    // READ FROM UNIFIED OBJECT
    // =====================================================
    const permissions = window._permData.permissions;

    console.log('[SAVE] Sending permissions:', permissions);

    fetch('{{ route('pengaturan.hak_akses.update', ['userId' => ':userId']) }}'.replace(':userId', userId), {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            permissions: permissions
        })
    })
    .then(r => {
        console.log('[SAVE] Response status:', r.status);
        return r.json();
    })
    .then(res => {
        console.log('[SAVE] Server response:', res);
        if (res.success) {
            alert('Hak Akses berhasil disimpan!');
            closePermModal();
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Gagal: ' + (res.message || 'Error'));
        }
    })
    .catch(err => {
        console.error('[SAVE] Error:', err);
        alert('Terjadi kesalahan');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save mr-1"></i> Simpan';
        }
    });
}

// =====================================================
// RESET PERMISSIONS
// =====================================================
function resetPerms() {
    console.log('[RESET] Starting reset...');

    if (!window._permData) {
        alert('Sesi habis, buka modal lagi');
        return;
    }

    if (!confirm('Reset perizinan ke default?')) return;

    const userId = window._permData.user?.id;
    if (!userId) return;

    fetch('{{ route('pengaturan.hak_akses.reset', ['userId' => ':userId']) }}'.replace(':userId', userId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Hak Akses berhasil direset!');
            closePermModal();
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Gagal: ' + (res.message || 'Error'));
        }
    })
    .catch(err => {
        console.error('[RESET] Error:', err);
        alert('Terjadi kesalahan');
    });
}

// =====================================================
// EVENT LISTENERS
// =====================================================
document.addEventListener('click', e => {
    if (e.target.id === 'permModal') {
        closePermModal();
    }
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closePermModal();
    }
});

// Debug helper
window._permDebug = function() {
    console.log('=== PERMISSION DEBUG ===');
    console.log('_permData:', window._permData);
    if (window._permData && window._permData.permissions) {
        console.log('Permissions:', window._permData.permissions);
    }
    console.log('========================');
};
</script>
@endpush
