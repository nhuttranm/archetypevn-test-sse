<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\VendorResource;
use App\Models\Department;
use App\Models\Vendor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LookupController extends Controller
{
    /**
     * GET /api/departments
     */
    public function departments(): AnonymousResourceCollection
    {
        return DepartmentResource::collection(Department::active()->get());
    }

    /**
     * GET /api/vendors
     */
    public function vendors(): AnonymousResourceCollection
    {
        return VendorResource::collection(Vendor::active()->get());
    }
}
