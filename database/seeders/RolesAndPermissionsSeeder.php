<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for Invoices
        $invoicePermissions = [
            'view_invoices',
            'view_own_invoices',
            'create_invoices',
            'edit_invoices',
            'edit_own_invoices',
            'delete_invoices',
            'delete_own_invoices',
            'send_invoices',
            'approve_invoices',
            'mark_invoices_paid',
            'export_invoices',
            'generate_qr_invoices',
        ];

        foreach ($invoicePermissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create permissions for Contacts
        $contactPermissions = [
            'view_contacts',
            'create_contacts',
            'edit_contacts',
            'delete_contacts',
            'export_contacts',
        ];

        foreach ($contactPermissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create permissions for Company Settings
        $companyPermissions = [
            'view_company_settings',
            'edit_company_settings',
            'manage_company_users',
        ];

        foreach ($companyPermissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Roles

        // Admin role - full access
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Accountant role - can manage invoices and contacts
        $accountantRole = Role::create(['name' => 'accountant']);
        $accountantRole->givePermissionTo([
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'send_invoices',
            'mark_invoices_paid',
            'export_invoices',
            'generate_qr_invoices',
            'view_contacts',
            'create_contacts',
            'edit_contacts',
        ]);

        // Employee role - limited access
        $employeeRole = Role::create(['name' => 'employee']);
        $employeeRole->givePermissionTo([
            'view_own_invoices',
            'create_invoices',
            'edit_own_invoices',
            'view_contacts',
        ]);

        // Viewer role - read only
        $viewerRole = Role::create(['name' => 'viewer']);
        $viewerRole->givePermissionTo([
            'view_invoices',
            'view_contacts',
        ]);
    }
}
