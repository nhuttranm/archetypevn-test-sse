<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PoStatusLogResource;
use App\Models\PoStatusLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    /**
     * GET /api/audit-logs
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PoStatusLog::with(['actor', 'purchaseOrder'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('purchase_order_id')) {
            $query->where('purchase_order_id', $request->input('purchase_order_id'));
        }

        if ($request->filled('acted_by')) {
            $query->where('acted_by', $request->input('acted_by'));
        }

        $logs = $query->paginate($request->input('per_page', 20));

        return PoStatusLogResource::collection($logs);
    }

    /**
     * GET /api/audit-logs/{purchaseOrderId}
     */
    public function show(int $purchaseOrderId): AnonymousResourceCollection
    {
        $logs = PoStatusLog::with(['actor'])
            ->where('purchase_order_id', $purchaseOrderId)
            ->orderBy('created_at', 'asc')
            ->get();

        return PoStatusLogResource::collection($logs);
    }
}
