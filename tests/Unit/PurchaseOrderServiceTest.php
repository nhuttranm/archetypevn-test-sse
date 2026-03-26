<?php

namespace Tests\Unit;

use App\Models\ApprovalRule;
use App\Models\Department;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Services\PurchaseOrderService;
use App\Workflows\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseOrderService $service;
    protected User $staff;
    protected User $manager;
    protected User $director;
    protected User $finance;
    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PurchaseOrderService::class);

        $dept = Department::create(['name' => 'IT', 'code' => 'IT']);

        $this->staff = User::create([
            'name' => 'Staff', 'email' => 'staff@test.com',
            'password' => bcrypt('password'), 'role' => 'staff',
            'department_id' => $dept->id,
        ]);

        $this->manager = User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('password'), 'role' => 'manager',
            'department_id' => $dept->id,
        ]);

        $this->director = User::create([
            'name' => 'Director', 'email' => 'director@test.com',
            'password' => bcrypt('password'), 'role' => 'director',
            'department_id' => $dept->id,
        ]);

        $this->finance = User::create([
            'name' => 'Finance', 'email' => 'finance@test.com',
            'password' => bcrypt('password'), 'role' => 'finance',
            'department_id' => $dept->id,
        ]);

        $this->vendor = Vendor::create(['name' => 'Test Vendor', 'code' => 'TV-001']);

        // Setup approval rules
        ApprovalRule::create(['current_state' => 'draft', 'next_state' => 'pending_manager', 'required_role' => 'staff']);
        ApprovalRule::create(['current_state' => 'pending_manager', 'next_state' => 'pending_finance', 'required_role' => 'manager', 'condition_expression' => ['department_match' => true]]);
        ApprovalRule::create(['current_state' => 'pending_finance', 'next_state' => 'approved', 'required_role' => 'finance']);
    }

    public function test_can_create_purchase_order(): void
    {
        $this->actingAs($this->staff);

        $po = $this->service->create([
            'vendor_id' => $this->vendor->id,
            'items' => [
                ['description' => 'Test Item', 'quantity' => 2, 'unit_price' => 100.00],
            ],
        ], $this->staff);

        $this->assertNotNull($po);
        $this->assertEquals('draft', $po->status);
        $this->assertEquals(200.00, $po->total_amount);
        $this->assertCount(1, $po->items);
    }

    public function test_can_submit_purchase_order(): void
    {
        $this->actingAs($this->staff);

        $po = $this->service->create([
            'vendor_id' => $this->vendor->id,
            'items' => [
                ['description' => 'Item 1', 'quantity' => 1, 'unit_price' => 500.00],
            ],
        ], $this->staff);

        $submitted = $this->service->submit($po, $this->staff);

        $this->assertEquals('pending_manager', $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
    }

    public function test_cannot_submit_empty_po(): void
    {
        $this->actingAs($this->staff);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'department_id' => $this->staff->department_id,
            'vendor_id' => $this->vendor->id,
            'created_by' => $this->staff->id,
            'status' => 'draft',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service->submit($po, $this->staff);
    }

    public function test_manager_can_approve(): void
    {
        $this->actingAs($this->staff);

        $po = $this->service->create([
            'vendor_id' => $this->vendor->id,
            'items' => [['description' => 'Item', 'quantity' => 1, 'unit_price' => 500.00]],
        ], $this->staff);

        $po = $this->service->submit($po, $this->staff);

        $this->actingAs($this->manager);
        $approved = $this->service->approve($po, $this->manager, 'LGTM');

        $this->assertEquals('pending_finance', $approved->status);
    }

    public function test_finance_can_final_approve(): void
    {
        $this->actingAs($this->staff);

        $po = $this->service->create([
            'vendor_id' => $this->vendor->id,
            'items' => [['description' => 'Item', 'quantity' => 1, 'unit_price' => 500.00]],
        ], $this->staff);

        $po = $this->service->submit($po, $this->staff);
        $this->actingAs($this->manager);
        $po = $this->service->approve($po, $this->manager);
        $this->actingAs($this->finance);
        $po = $this->service->approve($po, $this->finance, 'Budget cleared');

        $this->assertEquals('approved', $po->status);
        $this->assertNotNull($po->approved_at);
    }

    public function test_can_reject_purchase_order(): void
    {
        $this->actingAs($this->staff);

        $po = $this->service->create([
            'vendor_id' => $this->vendor->id,
            'items' => [['description' => 'Item', 'quantity' => 1, 'unit_price' => 500.00]],
        ], $this->staff);

        $po = $this->service->submit($po, $this->staff);

        $this->actingAs($this->manager);
        $rejected = $this->service->reject($po, $this->manager, 'Over budget');

        $this->assertEquals('rejected', $rejected->status);
        $this->assertEquals('Over budget', $rejected->rejection_reason);
    }

    public function test_cannot_edit_submitted_po(): void
    {
        $this->actingAs($this->staff);

        $po = $this->service->create([
            'vendor_id' => $this->vendor->id,
            'items' => [['description' => 'Item', 'quantity' => 1, 'unit_price' => 500.00]],
        ], $this->staff);

        $po = $this->service->submit($po, $this->staff);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service->update($po, ['notes' => 'Updated'], $this->staff);
    }

    public function test_audit_log_created_on_status_change(): void
    {
        $this->actingAs($this->staff);

        $po = $this->service->create([
            'vendor_id' => $this->vendor->id,
            'items' => [['description' => 'Item', 'quantity' => 1, 'unit_price' => 500.00]],
        ], $this->staff);

        $this->assertCount(1, $po->statusLogs);

        $po = $this->service->submit($po, $this->staff);
        $po->refresh();

        $this->assertCount(2, $po->statusLogs);
    }
}
