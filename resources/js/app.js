import './bootstrap';
import Alpine from 'alpinejs';

// Make Alpine available globally
window.Alpine = Alpine;

// Import Alpine Collapse plugin for dropdown menus
import collapse from '@alpinejs/collapse';
Alpine.plugin(collapse);

// Import sidebar functionality - registers Alpine.store('sidebar')
import './sidebar';

// Import sidebar menu component BEFORE Alpine starts
// This registers sidebarMenu before Alpine.start() processes x-data
import './sidebar-menu';

// Import global toast helper (available globally via window object)
import './helpers/toast-helper';

// Start Alpine AFTER all components are registered
Alpine.start();
