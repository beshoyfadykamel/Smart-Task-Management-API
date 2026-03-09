<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Traits\Api\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use ApiResponse, AuthorizesRequests;
    /**
     * List all permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->authorize('viewAny', Permission::class);
        $permissions = Permission::all(['id', 'name']);
        return $this->success(['permissions' => $permissions], 'Permissions retrieved successfully', 200);
    }
}
