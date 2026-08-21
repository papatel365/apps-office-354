{{-- resources/views/crm/staff/data-karyawan/partials/wizard-step-8-sidebar-permissions.blade.php --}}
{{--
    Step 8: Hak Akses Sidebar
    Tree View UI for setting sidebar menu permissions per employee
    Uses SidebarMenuConfig as single source of truth
--}}

@php
    $menuTree = $sidebarMenuTree ?? [];
    $employeePermissions = $employeePermissions ?? [];
    $stats = $sidebarPermissionStats ?? ['total' => 0, 'enabled' => 0, 'disabled' => 0, 'has_custom' => false];
    $isEdit = isset($employee) && $employee !== null;

    // Pre-compute data for JavaScript
    $allPermissionKeys = [];
    $parentKeys = [];
    $parentPermissionKeys = [];
    $menuTreeForJs = [];

    foreach ($menuTree as $parent) {
        $parentKeys[] = $parent['key'];
        $parentPermissionKeys[] = $parent['permission_key'];
        $allPermissionKeys[] = $parent['permission_key'];

        $parentNode = [
            'key' => $parent['permission_key'],
            'label' => $parent['label'],
            'icon' => $parent['icon'] ?? 'fa-folder',
            'hasChildren' => !empty($parent['children']),
            'children' => [],
        ];

        if (!empty($parent['children'])) {
            foreach ($parent['children'] as $child) {
                $allPermissionKeys[] = $child['permission_key'];
                $parentNode['children'][] = [
                    'key' => $child['permission_key'],
                    'label' => $child['label'],
                    'icon' => $child['icon'] ?? 'fa-chevron-right',
                ];
            }
        }

        $menuTreeForJs[] = $parentNode;
    }

    // Compute enabled keys for edit mode
    $enabledKeys = [];
    if ($isEdit && !empty($employeePermissions)) {
        // employeePermissions format: ['menu_key' => can_view (bool)]
        $enabledKeys = array_keys(array_filter($employeePermissions, fn($v) => $v === true));
    } else {
        // Create mode: all enabled by default
        $enabledKeys = $allPermissionKeys;
    }

    // JSON encode for JavaScript
    $menuTreeJson = json_encode($menuTreeForJs);
    $enabledKeysJson = json_encode($enabledKeys);
    $allPermissionKeysJson = json_encode(array_unique($allPermissionKeys));
    $parentPermissionKeysJson = json_encode($parentPermissionKeys);
@endphp

{{-- Alpine Component Registration --}}
@once
@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('sidebarPermissionTree', function() {
        return {
            // State
            selectedMenus: [],
            expandedNodes: [],
            searchQuery: '',
            menuTree: [],
            allKeys: [],
            parentKeys: [],

            // Computed properties
            get enabledCount() {
                const uniqueSelected = [...new Set(this.selectedMenus)];
                return uniqueSelected.filter(k => this.allKeys.includes(k)).length;
            },

            get totalCount() {
                return this.allKeys.length;
            },

            // Initialize from PHP data
            init() {
                this.menuTree = {!! $menuTreeJson !!};
                this.allKeys = {!! $allPermissionKeysJson !!};
                this.parentKeys = {!! $parentPermissionKeysJson !!};
                this.selectedMenus = {!! $enabledKeysJson !!};
                // Expand all parent nodes by default
                this.expandedNodes = [...this.parentKeys];
            },

            // Check if a menu is selected
            isSelected(key) {
                return this.selectedMenus.includes(key);
            },

            // Check if all children of a parent are selected
            areAllChildrenSelected(children) {
                if (!children || children.length === 0) return false;
                return children.every(c => this.selectedMenus.includes(c.key));
            },

            // Check if some children are selected (partial)
            areSomeChildrenSelected(children) {
                if (!children || children.length === 0) return false;
                const selectedCount = children.filter(c => this.selectedMenus.includes(c.key)).length;
                return selectedCount > 0 && selectedCount < children.length;
            },

            // Toggle menu selection (for child items)
            toggleMenu(key) {
                const index = this.selectedMenus.indexOf(key);
                if (index === -1) {
                    this.selectedMenus.push(key);
                } else {
                    this.selectedMenus.splice(index, 1);
                }
                this.updateHiddenInput();
            },

            // Toggle parent and all children
            toggleParent(parentKey, children) {
                if (!children) children = [];

                // Check if all children are selected
                const allChildrenSelected = children.length > 0 && children.every(c => this.selectedMenus.includes(c.key));

                if (allChildrenSelected) {
                    // Unselect all children
                    children.forEach(c => {
                        const idx = this.selectedMenus.indexOf(c.key);
                        if (idx !== -1) this.selectedMenus.splice(idx, 1);
                    });
                    // Also unselect parent
                    const parentIdx = this.selectedMenus.indexOf(parentKey);
                    if (parentIdx !== -1) this.selectedMenus.splice(parentIdx, 1);
                } else {
                    // Select parent and all children
                    if (!this.selectedMenus.includes(parentKey)) {
                        this.selectedMenus.push(parentKey);
                    }
                    children.forEach(c => {
                        if (!this.selectedMenus.includes(c.key)) {
                            this.selectedMenus.push(c.key);
                        }
                    });
                }
                this.updateHiddenInput();
            },

            // Select all menus
            selectAll() {
                this.selectedMenus = [...this.allKeys];
                this.updateHiddenInput();
            },

            // Deselect all menus
            deselectAll() {
                this.selectedMenus = [];
                this.updateHiddenInput();
            },

            // Expand all nodes
            expandAll() {
                this.expandedNodes = [...this.parentKeys];
            },

            // Collapse all nodes
            collapseAll() {
                this.expandedNodes = [];
            },

            // Toggle expand/collapse for a node
            toggleExpand(key) {
                const idx = this.expandedNodes.indexOf(key);
                if (idx === -1) {
                    this.expandedNodes.push(key);
                } else {
                    this.expandedNodes.splice(idx, 1);
                }
            },

            // Check if node is expanded
            isExpanded(key) {
                return this.expandedNodes.includes(key);
            },

            // Filter menu items based on search
            filteredMenuTree() {
                if (!this.searchQuery) return this.menuTree;

                const query = this.searchQuery.toLowerCase();
                return this.menuTree.filter(parent => {
                    // Check parent label
                    if (parent.label.toLowerCase().includes(query)) return true;
                    // Check children
                    if (parent.children && parent.children.some(child =>
                        child.label.toLowerCase().includes(query) || child.key.toLowerCase().includes(query)
                    )) return true;
                    return false;
                });
            },

            // Update hidden input for form submission
            updateHiddenInput() {
                const input = document.getElementById('sidebar_permissions_input');
                if (input) {
                    input.value = JSON.stringify(this.selectedMenus);
                }
            }
        };
    });
});
</script>
@endpush
@endonce

{{-- Main Component --}}
<div x-data="sidebarPermissionTree()">
    {{-- Hidden input for form submission --}}
    <input type="hidden" name="sidebar_permissions" id="sidebar_permissions_input" x-bind:value="JSON.stringify(selectedMenus)">

    {{-- Info Card --}}
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-0.5">
                <i class="fa-solid fa-info-circle text-blue-500 text-lg"></i>
            </div>
            <div>
                <p class="font-medium text-blue-800">Hak Akses Sidebar</p>
                <p class="text-sm text-blue-700 mt-1">
                    Atur menu mana saja yang dapat diakses oleh karyawan ini.
                    Menu yang tidak dicentang akan disembunyikan dari sidebar karyawan.
                    Jika kosong, karyawan akan menggunakan hak akses default dari role-nya.
                </p>
                @if($isEdit && $stats['has_custom'])
                    <p class="text-sm text-blue-700 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                            Mode Edit: Hak Akses kustom aktif
                        </span>
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-list text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900" x-text="enabledCount"></p>
                    <p class="text-sm text-gray-500">Menu Dipilih</p>
                </div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-check text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900" x-text="totalCount"></p>
                    <p class="text-sm text-gray-500">Total Menu</p>
                </div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                     :class="selectedMenus.length === 0 ? 'bg-amber-100' : 'bg-gray-100'">
                    <i class="fa-solid" :class="selectedMenus.length === 0 ? 'fa-triangle-exclamation text-amber-600' : 'fa-shield-halved text-gray-600'"></i>
                </div>
                <div>
                    <p class="text-sm font-medium" :class="selectedMenus.length === 0 ? 'text-amber-600' : 'text-gray-900'">
                        <span x-text="selectedMenus.length === 0 ? 'Default Role' : 'Custom'"></span>
                    </p>
                    <p class="text-sm text-gray-500">Mode Hak Akses</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Controls --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <button type="button"
                @click="selectAll()"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-check-double mr-1.5"></i>
            Centang Semua
        </button>
        <button type="button"
                @click="deselectAll()"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-times mr-1.5"></i>
            Hapus Semua
        </button>
        <button type="button"
                @click="expandAll()"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-plus mr-1.5"></i>
            Expand Semua
        </button>
        <button type="button"
                @click="collapseAll()"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-minus mr-1.5"></i>
            Collapse Semua
        </button>

        {{-- Search --}}
        <div class="flex-1 min-w-[200px] ml-auto">
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text"
                       x-model="searchQuery"
                       placeholder="Cari menu..."
                       class="w-full pl-10 pr-4 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
    </div>

    {{-- Tree View --}}
    <div class="bg-white border border-gray-200 rounded-lg max-h-[400px] overflow-y-auto">
        <div class="p-4 space-y-1">
            <template x-for="item in filteredMenuTree()" :key="item.key">
                <div class="border-b border-gray-100 last:border-b-0 pb-2 last:pb-0">
                    {{-- Parent Node --}}
                    <div class="flex items-center gap-2 py-2 px-2 rounded-lg hover:bg-gray-50 group transition-colors">
                        {{-- Expand/Collapse Toggle --}}
                        <button type="button"
                                @click="toggleExpand(item.key)"
                                class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors"
                                x-show="item.hasChildren">
                            <i class="fa-solid transition-transform duration-200"
                               :class="isExpanded(item.key) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                        </button>
                        <span x-show="!item.hasChildren" class="w-6"></span>

                        {{-- Checkbox with indeterminate state --}}
                        <label class="flex items-center gap-2 flex-1 cursor-pointer">
                            <input type="checkbox"
                                   :checked="isSelected(item.key)"
                                   @change="toggleParent(item.key, item.children)"
                                   class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                   :class="areSomeChildrenSelected(item.children) ? 'accent-indigo-600' : ''">
                            <i :class="'fa-solid ' + item.icon" class="text-gray-400 w-5"></i>
                            <span class="text-sm font-medium text-gray-900" x-text="item.label"></span>
                            <span class="text-xs text-gray-400" x-show="item.hasChildren"
                                  x-text="'(' + item.children.length + ' menu)'"></span>
                        </label>
                    </div>

                    {{-- Children Nodes --}}
                    <div x-show="isExpanded(item.key) && item.hasChildren"
                         x-collapse
                         class="ml-8 mt-1 space-y-1">
                        <template x-for="child in item.children" :key="child.key">
                            <div class="flex items-center gap-2 py-1.5 px-2 rounded-lg hover:bg-gray-50 transition-colors">
                                {{-- Indent --}}
                                <span class="w-4 border-l-2 border-gray-200 ml-2"></span>

                                {{-- Checkbox --}}
                                <label class="flex items-center gap-2 flex-1 cursor-pointer">
                                    <input type="checkbox"
                                           :checked="isSelected(child.key)"
                                           @change="toggleMenu(child.key)"
                                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <i :class="'fa-solid ' + child.icon" class="text-gray-400 w-4"></i>
                                    <span class="text-sm text-gray-700" x-text="child.label"></span>
                                </label>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Empty State --}}
            <div x-show="filteredMenuTree().length === 0" class="text-center py-8">
                <i class="fa-solid fa-search text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">Tidak ada menu yang cocok dengan pencarian</p>
            </div>
        </div>
    </div>

    {{-- Help Text --}}
    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
        <p class="text-xs text-gray-600">
            <i class="fa-solid fa-lightbulb mr-1"></i>
            <strong>Tips:</strong> Mengosongkan semua centang berarti karyawan akan menggunakan hak akses default dari role-nya.
            Centang menu tertentu untuk memberikan izin kustom per karyawan.
        </p>
    </div>
</div>
