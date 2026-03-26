<?php

namespace Database\Seeders;

use App\Models\ApprovalRule;
use App\Models\Department;
use App\Models\PoItem;
use App\Models\PoStatusLog;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Departments ──────────────────────────
        $it = Department::create(['name' => 'Information Technology', 'code' => 'IT', 'description' => 'IT Department']);
        $hr = Department::create(['name' => 'Human Resources', 'code' => 'HR', 'description' => 'HR Department']);
        $mkt = Department::create(['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Marketing Department']);
        $ops = Department::create(['name' => 'Operations', 'code' => 'OPS', 'description' => 'Operations Department']);
        $fin = Department::create(['name' => 'Finance', 'code' => 'FIN', 'description' => 'Finance Department']);

        // ── Users ────────────────────────────────
        // Staff users
        $staff1 = User::create([
            'name' => 'Nguyen Van A',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'department_id' => $it->id,
        ]);
        $staff2 = User::create([
            'name' => 'Tran Thi B',
            'email' => 'staff2@example.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'department_id' => $hr->id,
        ]);

        // Manager
        $manager = User::create([
            'name' => 'Le Van C',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'department_id' => $it->id,
        ]);
        $manager2 = User::create([
            'name' => 'Pham Thi D',
            'email' => 'manager2@example.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'department_id' => $hr->id,
        ]);

        // Director
        $director = User::create([
            'name' => 'Hoang Van E',
            'email' => 'director@example.com',
            'password' => Hash::make('password'),
            'role' => 'director',
            'department_id' => $it->id,
        ]);

        // Finance
        $finance = User::create([
            'name' => 'Vo Thi F',
            'email' => 'finance@example.com',
            'password' => Hash::make('password'),
            'role' => 'finance',
            'department_id' => $fin->id,
        ]);

        // ── Vendors ──────────────────────────────
        $vendors = [
            Vendor::create([
                'name' => 'TechCorp Solutions',
                'code' => 'VND-001',
                'email' => 'sales@techcorp.vn',
                'phone' => '028 1234 5678',
                'address' => '123 Nguyen Hue, District 1, HCMC',
                'contact_person' => 'Nguyen Tech',
            ]),
            Vendor::create([
                'name' => 'Office Supply Pro',
                'code' => 'VND-002',
                'email' => 'order@officesupply.vn',
                'phone' => '028 8765 4321',
                'address' => '456 Le Loi, District 3, HCMC',
                'contact_person' => 'Tran Supply',
            ]),
            Vendor::create([
                'name' => 'CloudFirst Services',
                'code' => 'VND-003',
                'email' => 'info@cloudfirst.io',
                'phone' => '024 9876 5432',
                'address' => '789 Kim Ma, Ba Dinh, Hanoi',
                'contact_person' => 'Le Cloud',
            ]),
            Vendor::create([
                'name' => 'DataCenter Vietnam',
                'code' => 'VND-004',
                'email' => 'sales@dcvn.com',
                'phone' => '028 5555 6666',
                'address' => '321 Vo Van Tan, District 3, HCMC',
                'contact_person' => 'Pham Data',
            ]),
        ];

        // ── Approval Rules (Workflow Config) ─────
        // Default flow: draft → pending_manager → pending_director → pending_finance → approved
        ApprovalRule::create([
            'name' => 'Staff Submit to Manager',
            'current_state' => 'draft',
            'next_state' => 'pending_manager',
            'required_role' => 'staff',
            'condition_expression' => null,
            'priority' => 1,
        ]);

        ApprovalRule::create([
            'name' => 'Manager Approve (< $10,000)',
            'current_state' => 'pending_manager',
            'next_state' => 'pending_finance',
            'required_role' => 'manager',
            'condition_expression' => ['max_amount' => 10000, 'department_match' => true],
            'priority' => 1,
        ]);

        ApprovalRule::create([
            'name' => 'Manager Approve (≥ $10,000)',
            'current_state' => 'pending_manager',
            'next_state' => 'pending_director',
            'required_role' => 'manager',
            'condition_expression' => ['min_amount' => 10000, 'department_match' => true],
            'priority' => 2,
        ]);

        ApprovalRule::create([
            'name' => 'Director Approve',
            'current_state' => 'pending_director',
            'next_state' => 'pending_finance',
            'required_role' => 'director',
            'condition_expression' => null,
            'priority' => 1,
        ]);

        ApprovalRule::create([
            'name' => 'Finance Final Approve',
            'current_state' => 'pending_finance',
            'next_state' => 'approved',
            'required_role' => 'finance',
            'condition_expression' => null,
            'priority' => 1,
        ]);

        // ── Sample Purchase Orders ───────────────
        // PO 1: Draft
        $po1 = PurchaseOrder::create([
            'po_number' => 'PO-202601-0001',
            'department_id' => $it->id,
            'vendor_id' => $vendors[0]->id,
            'created_by' => $staff1->id,
            'total_amount' => 5500.00,
            'status' => 'draft',
        ]);
        PoItem::create(['purchase_order_id' => $po1->id, 'description' => 'Dell Laptop XPS 15', 'quantity' => 2, 'unit_price' => 2000.00, 'total_price' => 4000.00]);
        PoItem::create(['purchase_order_id' => $po1->id, 'description' => 'USB-C Docking Station', 'quantity' => 3, 'unit_price' => 500.00, 'total_price' => 1500.00]);
        PoStatusLog::create(['purchase_order_id' => $po1->id, 'acted_by' => $staff1->id, 'from_status' => null, 'to_status' => 'draft', 'comment' => 'Purchase order created']);

        // PO 2: Pending Manager
        $po2 = PurchaseOrder::create([
            'po_number' => 'PO-202601-0002',
            'department_id' => $it->id,
            'vendor_id' => $vendors[2]->id,
            'created_by' => $staff1->id,
            'total_amount' => 15000.00,
            'status' => 'pending_manager',
            'submitted_at' => now()->subDays(2),
        ]);
        PoItem::create(['purchase_order_id' => $po2->id, 'description' => 'AWS Cloud Credits (Annual)', 'quantity' => 1, 'unit_price' => 12000.00, 'total_price' => 12000.00]);
        PoItem::create(['purchase_order_id' => $po2->id, 'description' => 'Cloud Monitoring Tool License', 'quantity' => 1, 'unit_price' => 3000.00, 'total_price' => 3000.00]);
        PoStatusLog::create(['purchase_order_id' => $po2->id, 'acted_by' => $staff1->id, 'from_status' => null, 'to_status' => 'draft', 'comment' => 'Purchase order created']);
        PoStatusLog::create(['purchase_order_id' => $po2->id, 'acted_by' => $staff1->id, 'from_status' => 'draft', 'to_status' => 'pending_manager', 'comment' => 'Submitted for approval']);

        // PO 3: Pending Director
        $po3 = PurchaseOrder::create([
            'po_number' => 'PO-202601-0003',
            'department_id' => $it->id,
            'vendor_id' => $vendors[3]->id,
            'created_by' => $staff1->id,
            'total_amount' => 45000.00,
            'status' => 'pending_director',
            'submitted_at' => now()->subDays(5),
        ]);
        PoItem::create(['purchase_order_id' => $po3->id, 'description' => 'Server Rack (42U)', 'quantity' => 2, 'unit_price' => 15000.00, 'total_price' => 30000.00]);
        PoItem::create(['purchase_order_id' => $po3->id, 'description' => 'Network Switch 48-port', 'quantity' => 5, 'unit_price' => 3000.00, 'total_price' => 15000.00]);
        PoStatusLog::create(['purchase_order_id' => $po3->id, 'acted_by' => $staff1->id, 'from_status' => null, 'to_status' => 'draft', 'comment' => 'Purchase order created']);
        PoStatusLog::create(['purchase_order_id' => $po3->id, 'acted_by' => $staff1->id, 'from_status' => 'draft', 'to_status' => 'pending_manager', 'comment' => 'Submitted for approval']);
        PoStatusLog::create(['purchase_order_id' => $po3->id, 'acted_by' => $manager->id, 'from_status' => 'pending_manager', 'to_status' => 'pending_director', 'comment' => 'Approved by manager - routing to director for high value PO']);

        // PO 4: Approved
        $po4 = PurchaseOrder::create([
            'po_number' => 'PO-202601-0004',
            'department_id' => $hr->id,
            'vendor_id' => $vendors[1]->id,
            'created_by' => $staff2->id,
            'total_amount' => 3200.00,
            'status' => 'approved',
            'submitted_at' => now()->subDays(10),
            'approved_at' => now()->subDays(7),
        ]);
        PoItem::create(['purchase_order_id' => $po4->id, 'description' => 'Ergonomic Office Chairs', 'quantity' => 8, 'unit_price' => 400.00, 'total_price' => 3200.00]);
        PoStatusLog::create(['purchase_order_id' => $po4->id, 'acted_by' => $staff2->id, 'from_status' => null, 'to_status' => 'draft', 'comment' => 'Purchase order created']);
        PoStatusLog::create(['purchase_order_id' => $po4->id, 'acted_by' => $staff2->id, 'from_status' => 'draft', 'to_status' => 'pending_manager', 'comment' => 'Submitted for approval']);
        PoStatusLog::create(['purchase_order_id' => $po4->id, 'acted_by' => $manager2->id, 'from_status' => 'pending_manager', 'to_status' => 'pending_finance', 'comment' => 'Approved by manager']);
        PoStatusLog::create(['purchase_order_id' => $po4->id, 'acted_by' => $finance->id, 'from_status' => 'pending_finance', 'to_status' => 'approved', 'comment' => 'Final approval by finance']);

        // PO 5: Rejected
        $po5 = PurchaseOrder::create([
            'po_number' => 'PO-202601-0005',
            'department_id' => $it->id,
            'vendor_id' => $vendors[0]->id,
            'created_by' => $staff1->id,
            'total_amount' => 75000.00,
            'status' => 'rejected',
            'rejection_reason' => 'Budget exceeded for Q1. Please resubmit in Q2 with revised quantities.',
            'submitted_at' => now()->subDays(8),
            'rejected_at' => now()->subDays(6),
        ]);
        PoItem::create(['purchase_order_id' => $po5->id, 'description' => 'MacBook Pro M3 Max', 'quantity' => 15, 'unit_price' => 5000.00, 'total_price' => 75000.00]);
        PoStatusLog::create(['purchase_order_id' => $po5->id, 'acted_by' => $staff1->id, 'from_status' => null, 'to_status' => 'draft', 'comment' => 'Purchase order created']);
        PoStatusLog::create(['purchase_order_id' => $po5->id, 'acted_by' => $staff1->id, 'from_status' => 'draft', 'to_status' => 'pending_manager', 'comment' => 'Submitted for approval']);
        PoStatusLog::create(['purchase_order_id' => $po5->id, 'acted_by' => $manager->id, 'from_status' => 'pending_manager', 'to_status' => 'pending_director', 'comment' => 'Approved by manager']);
        PoStatusLog::create(['purchase_order_id' => $po5->id, 'acted_by' => $director->id, 'from_status' => 'pending_director', 'to_status' => 'rejected', 'comment' => 'Budget exceeded for Q1. Please resubmit in Q2.']);

        // PO 6: Pending Finance
        $po6 = PurchaseOrder::create([
            'po_number' => 'PO-202601-0006',
            'department_id' => $mkt->id,
            'vendor_id' => $vendors[1]->id,
            'created_by' => $staff1->id, // Using IT staff for simplicity
            'total_amount' => 8500.00,
            'status' => 'pending_finance',
            'submitted_at' => now()->subDays(3),
            'notes' => 'Urgent - needed for upcoming product launch event',
        ]);
        PoItem::create(['purchase_order_id' => $po6->id, 'description' => 'Marketing Banners (Large)', 'quantity' => 20, 'unit_price' => 250.00, 'total_price' => 5000.00]);
        PoItem::create(['purchase_order_id' => $po6->id, 'description' => 'Promotional Merchandise Kits', 'quantity' => 100, 'unit_price' => 35.00, 'total_price' => 3500.00]);
        PoStatusLog::create(['purchase_order_id' => $po6->id, 'acted_by' => $staff1->id, 'from_status' => null, 'to_status' => 'draft', 'comment' => 'Purchase order created']);
        PoStatusLog::create(['purchase_order_id' => $po6->id, 'acted_by' => $staff1->id, 'from_status' => 'draft', 'to_status' => 'pending_manager', 'comment' => 'Submitted']);
        PoStatusLog::create(['purchase_order_id' => $po6->id, 'acted_by' => $manager->id, 'from_status' => 'pending_manager', 'to_status' => 'pending_finance', 'comment' => 'Approved - under $10K threshold']);

        echo "✅ Database seeded successfully!\n";
        echo "📧 Login credentials (password: 'password' for all):\n";
        echo "   Staff:    staff@example.com\n";
        echo "   Manager:  manager@example.com\n";
        echo "   Director: director@example.com\n";
        echo "   Finance:  finance@example.com\n";
    }
}
