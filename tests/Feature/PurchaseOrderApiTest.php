<?php

namespace Tests\Feature;

use App\Models\ApprovalRule;
use App\Models\Department;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;
    protected User $manager;
    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->vendor = Vendor::create(['name' => 'Test Vendor', 'code' => 'TV-001']);

        ApprovalRule::create(['current_state' => 'draft', 'next_state' => 'pending_manager', 'required_role' => 'staff']);
        ApprovalRule::create(['current_state' => 'pending_manager', 'next_state' => 'pending_finance', 'required_role' => 'manager', 'condition_expression' => ['department_match' => true]]);
    }

    public function test_can_list_purchase_orders(): void
    {
        $response = $this->actingAs($this->staff)
            ->getJson('/api/purchase-orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_can_create_purchase_order(): void
    {
        $response = $this->actingAs($this->staff)
            ->postJson('/api/purchase-orders', [
                'vendor_id' => $this->vendor->id,
                'items' => [
                    ['description' => 'Test Item', 'quantity' => 2, 'unit_price' => 100.00],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total_amount', 200.0);
    }

    public function test_validation_fails_without_items(): void
    {
        $response = $this->actingAs($this->staff)
            ->postJson('/api/purchase-orders', [
                'vendor_id' => $this->vendor->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_unauthenticated_access_blocked(): void
    {
        $response = $this->getJson('/api/purchase-orders');

        $response->assertStatus(401);
    }

    public function test_login_returns_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'staff@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'staff@test.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
    }
}
