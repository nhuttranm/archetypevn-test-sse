<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApprovalRuleRequest;
use App\Models\ApprovalRule;
use App\Workflows\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalRuleController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    /**
     * GET /api/approval-rules
     */
    public function index(): JsonResponse
    {
        $rules = ApprovalRule::orderBy('current_state')
            ->orderBy('priority')
            ->get();

        return response()->json(['data' => $rules]);
    }

    /**
     * POST /api/approval-rules
     */
    public function store(StoreApprovalRuleRequest $request): JsonResponse
    {
        $rule = ApprovalRule::create($request->validated());

        return response()->json(['data' => $rule], 201);
    }

    /**
     * PUT /api/approval-rules/{id}
     */
    public function update(StoreApprovalRuleRequest $request, ApprovalRule $approvalRule): JsonResponse
    {
        $approvalRule->update($request->validated());

        return response()->json(['data' => $approvalRule]);
    }

    /**
     * DELETE /api/approval-rules/{id}
     */
    public function destroy(ApprovalRule $approvalRule): JsonResponse
    {
        $approvalRule->delete();

        return response()->json(['message' => 'Approval rule deleted.']);
    }

    /**
     * GET /api/approval-rules/chain
     */
    public function chain(): JsonResponse
    {
        $chain = $this->workflowService->getApprovalChain();

        return response()->json(['data' => $chain]);
    }
}
