/**
 * Sidebar Menu Alpine Component
 *
 * This component MUST be imported BEFORE Alpine.start() is called.
 * It registers the sidebarMenu component for dynamic-sidebar.blade.php
 */

import Alpine from 'alpinejs';

// Server-side rendered state placeholder
// This will be set by the blade template
let serverSideState = {};

/**
 * Set the server-side rendered state from blade template
 * Called by the inline script in dynamic-sidebar.blade.php
 */
window.setSidebarMenuState = function(state) {
    serverSideState = state || {};
};

/**
 * Register the sidebarMenu Alpine component
 */
document.addEventListener('alpine:init', function() {
    console.log('[SIDEBAR] alpine:init fired - registering sidebarMenu');

    Alpine.data('sidebarMenu', function() {
        // Merge server-side state with default
        const initialState = Object.keys(serverSideState).length > 0 ? serverSideState : {};

        return {
            // Initialize with server-side state from blade template
            openDropdowns: initialState,

            init: function() {
                console.log('[SIDEBAR] sidebarMenu component initialized');
                // Load saved state from localStorage
                var saved = localStorage.getItem('sidebar_dropdowns_v5');
                if (saved) {
                    try {
                        var savedState = JSON.parse(saved);
                        // Merge saved state with server-side state
                        for (var key in savedState) {
                            if (!this.openDropdowns.hasOwnProperty(key)) {
                                this.openDropdowns[key] = savedState[key];
                            }
                        }
                    } catch(e) {
                        console.error('[SIDEBAR] Error parsing localStorage:', e);
                    }
                }
            },

            /**
             * Check if sidebar is collapsed (not visible)
             */
            isCollapsed: function() {
                return !Alpine.store('sidebar').visible;
            },

            /**
             * Toggle dropdown state
             */
            toggleDropdown: function(name) {
                this.openDropdowns[name] = !this.openDropdowns[name];
                localStorage.setItem('sidebar_dropdowns_v5', JSON.stringify(this.openDropdowns));
            },

            /**
             * Open a dropdown specifically (used for auto-expand + auto-open)
             */
            openDropdown: function(name) {
                this.openDropdowns[name] = true;
                localStorage.setItem('sidebar_dropdowns_v5', JSON.stringify(this.openDropdowns));
            },

            /**
             * Handle dropdown click - auto-expands sidebar if collapsed
             * Then opens the dropdown
             */
            handleDropdownClick: function(name, redirectUrl) {
                // Check if sidebar is collapsed
                if (this.isCollapsed()) {
                    // Expand sidebar first
                    Alpine.store('sidebar').visible = true;
                    localStorage.setItem('crm_sidebar_visible', 'true');

                    // Wait for DOM update, then open dropdown
                    this.$nextTick(() => {
                        this.openDropdown(name);

                        // If redirectUrl is provided, navigate to it
                        if (redirectUrl && redirectUrl !== '' && redirectUrl.indexOf('/') >= 0) {
                            window.location.href = redirectUrl;
                        }
                    });
                } else {
                    // Normal toggle behavior when expanded
                    this.toggleDropdown(name);

                    // If redirectUrl is a valid URL, redirect to it
                    if (redirectUrl && redirectUrl !== '' && redirectUrl.indexOf('/') >= 0) {
                        window.location.href = redirectUrl;
                    }
                }
            },

            /**
             * Handle standalone menu item click (like Beranda)
             * If sidebar is collapsed, just expand it first, then navigate
             */
            handleItemClick: function(route) {
                if (this.isCollapsed()) {
                    // Expand sidebar first
                    Alpine.store('sidebar').visible = true;
                    localStorage.setItem('crm_sidebar_visible', 'true');
                }
                // Let default navigation happen via href
            },

            isDropdownOpen: function(name) {
                if (this.openDropdowns && this.openDropdowns.hasOwnProperty(name)) {
                    return this.openDropdowns[name];
                }
                return false;
            }
        };
    });

    console.log('[SIDEBAR] sidebarMenu registered successfully');
});
