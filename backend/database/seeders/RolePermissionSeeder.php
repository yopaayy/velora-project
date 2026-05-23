<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Tenant
            ['module' => 'tenant', 'name' => 'business.view', 'display_name' => 'View Business', 'description' => 'Can view business settings'],
            ['module' => 'tenant', 'name' => 'business.edit', 'display_name' => 'Edit Business', 'description' => 'Can edit business settings'],
            ['module' => 'tenant', 'name' => 'branch.view', 'display_name' => 'View Branches', 'description' => 'Can view branches'],
            ['module' => 'tenant', 'name' => 'branch.create', 'display_name' => 'Create Branch', 'description' => 'Can create new branches'],
            ['module' => 'tenant', 'name' => 'branch.edit', 'display_name' => 'Edit Branch', 'description' => 'Can edit branches'],
            ['module' => 'tenant', 'name' => 'branch.delete', 'display_name' => 'Delete Branch', 'description' => 'Can delete branches'],
            
            // POS
            ['module' => 'pos', 'name' => 'product.view', 'display_name' => 'View Products', 'description' => 'Can view products'],
            ['module' => 'pos', 'name' => 'product.create', 'display_name' => 'Create Product', 'description' => 'Can create new products'],
            ['module' => 'pos', 'name' => 'product.edit', 'display_name' => 'Edit Product', 'description' => 'Can edit products'],
            ['module' => 'pos', 'name' => 'product.delete', 'display_name' => 'Delete Product', 'description' => 'Can delete products'],
            
            // Inventory
            ['module' => 'inventory', 'name' => 'stock.view', 'display_name' => 'View Stock', 'description' => 'Can view stock levels'],
            ['module' => 'inventory', 'name' => 'stock.adjust', 'display_name' => 'Adjust Stock', 'description' => 'Can make manual stock adjustments'],
            ['module' => 'inventory', 'name' => 'stock.transfer', 'display_name' => 'Transfer Stock', 'description' => 'Can transfer stock between branches'],
            ['module' => 'inventory', 'name' => 'stock.opname', 'display_name' => 'Stock Opname', 'description' => 'Can perform stock opname'],
            
            // Sales
            ['module' => 'sales', 'name' => 'pos.access', 'display_name' => 'Access POS', 'description' => 'Can open and use POS register'],
            ['module' => 'sales', 'name' => 'transaction.view', 'display_name' => 'View Transactions', 'description' => 'Can view past transactions'],
            ['module' => 'sales', 'name' => 'transaction.void', 'display_name' => 'Void Transaction', 'description' => 'Can void transactions'],
            ['module' => 'sales', 'name' => 'transaction.refund', 'display_name' => 'Refund Transaction', 'description' => 'Can process refunds'],
            
            // Purchasing
            ['module' => 'purchasing', 'name' => 'purchase.view', 'display_name' => 'View Purchases', 'description' => 'Can view purchase orders'],
            ['module' => 'purchasing', 'name' => 'purchase.create', 'display_name' => 'Create Purchase', 'description' => 'Can create purchase orders'],
            
            // Report
            ['module' => 'report', 'name' => 'report.sales', 'display_name' => 'View Sales Reports', 'description' => 'Can view sales reports'],
            ['module' => 'report', 'name' => 'report.inventory', 'display_name' => 'View Inventory Reports', 'description' => 'Can view inventory reports'],
            ['module' => 'report', 'name' => 'report.finance', 'display_name' => 'View Financial Reports', 'description' => 'Can view financial reports'],
            
            // Staff
            ['module' => 'staff', 'name' => 'staff.view', 'display_name' => 'View Staff', 'description' => 'Can view staff members'],
            ['module' => 'staff', 'name' => 'staff.manage', 'display_name' => 'Manage Staff', 'description' => 'Can add, edit, and assign roles to staff'],
            
            // Settings
            ['module' => 'setting', 'name' => 'setting.manage', 'display_name' => 'Manage Settings', 'description' => 'Can manage global application settings'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                $perm
            );
        }
    }
}
