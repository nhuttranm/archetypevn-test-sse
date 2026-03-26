<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalActionRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Thin Controller - delegates all logic to PurchaseOrderService.
 */
class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $service
    ) {}

    /**
     * GET /api/purchase-orders
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PurchaseOrder::with(['vendor', 'department', 'creator'])
            ->latest()
            ->where('is_latest', true);

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->byDepartment($request->input('department_id'));
        }

        // Search by PO number
        if ($request->filled('search')) {
            $query->where('po_number', 'like', '%' . $request->input('search') . '%');
        }

        $purchaseOrders = $query->paginate($request->input('per_page', 15));

        return PurchaseOrderResource::collection($purchaseOrders);
    }

    /**
     * POST /api/purchase-orders
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        $po = $this->service->create($request->validated(), $request->user());

        return (new PurchaseOrderResource($po))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/purchase-orders/{id}
     */
    public function show(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load([
            'vendor', 'department', 'creator',
            'items', 'statusLogs.actor', 'parentPo',
        ]);

        return new PurchaseOrderResource($purchaseOrder);
    }

    /**
     * PUT /api/purchase-orders/{id}
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $this->authorize('update', $purchaseOrder);

        $po = $this->service->update($purchaseOrder, $request->validated(), $request->user());

        return new PurchaseOrderResource($po);
    }

    /**
     * DELETE /api/purchase-orders/{id}
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('delete', $purchaseOrder);

        $this->service->delete($purchaseOrder, request()->user());

        return response()->json(['message' => 'Purchase order deleted successfully.'], 200);
    }

    /**
     * POST /api/purchase-orders/{id}/submit
     */
    public function submit(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $this->authorize('submit', $purchaseOrder);

        $po = $this->service->submit($purchaseOrder, request()->user());

        return new PurchaseOrderResource($po);
    }

    /**
     * POST /api/purchase-orders/{id}/approve
     */
    public function approve(ApprovalActionRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $this->authorize('approve', $purchaseOrder);

        $po = $this->service->approve($purchaseOrder, $request->user(), $request->input('comment'));

        return new PurchaseOrderResource($po);
    }

    /**
     * POST /api/purchase-orders/{id}/reject
     */
    public function reject(ApprovalActionRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $this->authorize('reject', $purchaseOrder);

        $po = $this->service->reject($purchaseOrder, $request->user(), $request->input('reason'));

        return new PurchaseOrderResource($po);
    }

    /**
     * POST /api/purchase-orders/{id}/revise
     */
    public function revise(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $this->authorize('revise', $purchaseOrder);

        $po = $this->service->createRevision($purchaseOrder, request()->user());

        return new PurchaseOrderResource($po);
    }

    /**
     * GET /api/purchase-orders/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $stats = $this->service->getDashboardStats($request->user());

        return response()->json(['data' => $stats]);
    }
}
