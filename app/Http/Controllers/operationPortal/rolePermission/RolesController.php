<?php

namespace App\Http\Controllers\operationPortal\rolePermission;

use Illuminate\Http\Request;
use App\Helpers\ApiResponseHelper;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class RolesController extends Controller
{
    function __construct()
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $perPage = $request->input('per_page', 20); // default per page

        if($request->portal == "operation")
        {
            $roles = Role::where('guard_name', 'admin-api')->get();
        }else{
            $roles = Role::where('guard_name', 'api')->get();
        }

        return response()->json($roles);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'permissions' => 'required|array|min:1',
            'portal' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }
        try {

            if ($request->portal == "operation") {

                $role = Role::create(
                    [
                        'name' => $request->get('name'),
                        'guard_name' => 'admin-api'
                    ]
                );
                // Assuming permissions are passed as an array in the request with key 'permissions'
                $permissions = $request->get('permissions', []);

                // Retrieve permissions with guard_name equal to 'admin-api'
                $permissions = Permission::where('guard_name', 'admin-api')->whereIn('name', $permissions)->get();

                // Sync the retrieved permissions
                $role->syncPermissions($permissions);
                // $role->syncPermissions($request->get('permission'));
                return response()->json([
                    'message' => 'Role Assign Successfully',
                    'data' => $role,
                ]);
            } else {

                $role = Role::create(
                    [
                        'name' => $request->get('name'),
                        'guard_name' => 'api'
                    ]
                );
                // Assuming permissions are passed as an array in the request with key 'permissions'
                $permissions = $request->get('permissions', []);

                // Retrieve permissions with guard_name equal to 'admin-api'
                $permissions = Permission::where('guard_name', 'api')->whereIn('name', $permissions)->get();

                // Sync the retrieved permissions
                $role->syncPermissions($permissions);
                // $role->syncPermissions($request->get('permission'));
                return response()->json([
                    'message' => 'Role Assign Successfully',
                    'data' => $role,
                ]);
            }
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Role Duplicate Entry']);
                return response()->json([
                    'errors' => $systemError,
                    'payload' => null,
                ], 500);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $role = $role;
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $permissions = Permission::get();

        return response()->json([
            'data' => [
                'role' => $role,
                'permissions' => $rolePermissions,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $rules = [
            'name' => 'required',
            'permission' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $role->update($request->only('name'));

        $role->syncPermissions($request->get('permission'));

        return response()->json([
            'message' => 'Role Update Successfully',
            'data' => $role,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json([
            'message' => 'Role Deleted Successfully',
        ]);
    }
}
