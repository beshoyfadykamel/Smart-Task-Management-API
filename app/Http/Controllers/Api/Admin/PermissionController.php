<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PermissionResource;
use App\Traits\Api\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Permission::class);
        $permissions = Permission::all();
        return $this->success(['permissions' => PermissionResource::collection($permissions)], 'Permissions retrieved successfully', 200);
    }
}
