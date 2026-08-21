<?php

namespace Database\Seeders;

use App\Modules\System\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * AdministratorSeeder
 *
 * Creates the Global Administrator account with full system access.
 * This seeder is IDEMPOTENT - safe to run multiple times.
 *
 * Account Details:
 * - Name: Administrator
 * - Email: admin@office354.com
 * - Username: admin
 * - Password: Admin@123456
 * - user_type: developer (bypasses tenant middleware)
 * - company_id: NULL (global admin)
 * - tenant_id: NULL (global admin)
 * - Role: developer (full access)
 *
 * IMPORTANT: This seeder does NOT create Company or Tenant.
 * It only creates the admin user with global access.
 */
class AdministratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('===========================================');
        $this->command->info('Administrator Account Setup');
        $this->command->info('===========================================');

        // Admin account details
        $adminEmail = 'admin@office354.com';
        $adminName = 'Administrator';
        $adminUsername = 'admin';
        $adminPassword = 'Admin@123456';

        // Get all sidebar permissions for developer role
        $allPermissions = \App\Services\SidebarMenuConfig::getAllPermissionKeys();

        $this->command->info('');
        $this->command->info('Sidebar permissions available: ' . count($allPermissions));

        // Ensure developer role exists (Spatie)
        $developerRole = Role::where('name', 'developer')->first();
        if (!$developerRole) {
            $developerRole = Role::create([
                'name' => 'developer',
                'guard_name' => 'web',
            ]);
            $this->command->info('Created developer role (ID: ' . $developerRole->id . ')');
        } else {
            $this->command->info('Developer role already exists (ID: ' . $developerRole->id . ')');
        }

        // Check if admin exists
        $existingAdmin = User::where('email', $adminEmail)->first();

        if ($existingAdmin) {
            // Update existing admin - ensure full access
            $existingAdmin->update([
                'name' => $adminName,
                'username' => $adminUsername,
                'password' => Hash::make($adminPassword),
                'user_type' => User::ROLE_DEVELOPER, // developer for global access
                'company_id' => null, // Global admin - no company
                'tenant_id' => null,  // Global admin - no tenant
                'is_active' => true,
                'is_owner' => false,
                'sidebar_permissions' => $allPermissions,
                'email_verified_at' => now(),
            ]);

            // Sync developer role
            $existingAdmin->syncRoles(['developer']);

            $this->command->info('');
            $this->command->info('-------------------------------------------');
            $this->command->info('Admin user UPDATED: ' . $adminEmail);
            $this->command->info('-------------------------------------------');
            $this->command->info('  - Name:     ' . $existingAdmin->name);
            $this->command->info('  - Username: ' . $existingAdmin->username);
            $this->command->info('  - Email:    ' . $existingAdmin->email);
            $this->command->info('  - user_type: ' . $existingAdmin->user_type);
            $this->command->info('  - company_id: NULL (global admin)');
            $this->command->info('  - tenant_id: NULL (global admin)');
            $this->command->info('  - is_active: ' . ($existingAdmin->is_active ? 'Yes' : 'No'));
            $this->command->info('  - Roles: ' . $existingAdmin->getRoleNames()->implode(', '));
            $this->command->info('  - Sidebar Permissions: ' . count($existingAdmin->sidebar_permissions ?? []) . ' items');
            $this->command->info('');
            $this->command->warn('Admin already existed. Password was reset to: Admin@123456');

        } else {
            // Create new admin user
            $admin = User::create([
                'name' => $adminName,
                'username' => $adminUsername,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'user_type' => User::ROLE_DEVELOPER, // developer for global access
                'company_id' => null, // Global admin - no company
                'tenant_id' => null,  // Global admin - no tenant
                'is_active' => true,
                'is_owner' => false,
                'sidebar_permissions' => $allPermissions,
                'email_verified_at' => now(),
            ]);

            // Assign developer role
            $admin->assignRole('developer');

            $this->command->info('');
            $this->command->info('===========================================');
            $this->command->info('ADMIN ACCOUNT CREATED SUCCESSFULLY!');
            $this->command->info('===========================================');
            $this->command->info('');
            $this->command->info('ACCOUNT DETAILS:');
            $this->command->info('  Name:      ' . $admin->name);
            $this->command->info('  Username:  ' . $admin->username);
            $this->command->info('  Email:     ' . $admin->email);
            $this->command->info('  Password:   Admin@123456');
            $this->command->info('');
            $this->command->info('ACCESS LEVEL:');
            $this->command->info('  user_type:  developer (full system access)');
            $this->command->info('  Role:       developer (bypasses tenant middleware)');
            $this->command->info('  company_id: NULL (global administrator)');
            $this->command->info('  tenant_id:  NULL (global administrator)');
            $this->command->info('');
            $this->command->info('PERMISSIONS:');
            $this->command->info('  Total Sidebar Permissions: ' . count($allPermissions));
            $this->command->info('');
            $this->command->info('SIDEBAR ACCESS:');
            $this->command->info('  - Beranda (dashboard)');
            $this->command->info('  - Administrasi > Data Karyawan');
            $this->command->info('  - Administrasi > Absensi');
            $this->command->info('  - Administrasi > Laporan');
            $this->command->info('  - Pengaturan > Backup');
            $this->command->info('  - Pengaturan > Hak Akses');
            $this->command->info('  - Pengaturan > Umum');
            $this->command->info('  - HRD Expert');
            $this->command->info('  - And all other modules...');
            $this->command->info('');
            $this->command->info('BYPASSES:');
            $this->command->info('  - TenantMiddleware (no company_id required)');
            $this->command->info('  - All module access');
            $this->command->info('');
        }

        $this->command->info('===========================================');
        $this->command->info('You can now login at: /login');
        $this->command->info('  Email:    admin@office354.com');
        $this->command->info('  Password: Admin@123456');
        $this->command->info('===========================================');
        $this->command->info('');
    }
}
