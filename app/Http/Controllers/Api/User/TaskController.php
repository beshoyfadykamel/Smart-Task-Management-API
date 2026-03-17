<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\TasksResource;
use App\Traits\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tasks = Auth::user()->tasks()
            ->with('group')
            ->latest()
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return $this->successPaginated(
            $tasks,
            TasksResource::collection($tasks),
            'tasks',
            'Tasks retrieved successfully',
            200,
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
