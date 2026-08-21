/**
 * CRM Sidebar JavaScript
 * AlpineJS-based global sidebar store
 * This file is loaded via @vite and creates the single source of truth for sidebar state
 */

// Initialize Alpine store for global sidebar state
document.addEventListener('alpine:init', () => {
    // Single source of truth for sidebar state
    // This store is shared across all layouts and components
    Alpine.store('sidebar', {
        // Desktop sidebar visibility
        visible: localStorage.getItem('crm_sidebar_visible') !== 'false',

        // Mobile drawer state
        mobileOpen: false,

        /**
         * Initialize store state
         */
        init() {
            // No dark mode - feature removed
        },

        /**
         * Toggle desktop sidebar visibility
         */
        toggle() {
            this.visible = !this.visible;
            localStorage.setItem('crm_sidebar_visible', this.visible);
        },

        /**
         * Toggle mobile drawer
         */
        toggleMobile() {
            this.mobileOpen = !this.mobileOpen;
            if (this.mobileOpen) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },

        /**
         * Close mobile drawer
         */
        closeMobile() {
            this.mobileOpen = false;
            document.body.style.overflow = '';
        }
    });
});

/**
 * Handle escape key to close mobile menu
 */
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        // Close mobile sidebar on escape
        if (window.Alpine && Alpine.store('sidebar')) {
            Alpine.store('sidebar').closeMobile();
        }
    }
});
