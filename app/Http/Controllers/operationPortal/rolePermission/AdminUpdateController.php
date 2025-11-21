<?php

namespace App\Http\Controllers\operationPortal\rolePermission;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Signature;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminUpdateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users);
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
            'name' => 'required|unique:roles,name',
            'email_or_mobile' => 'required_without_all:email,mobile',
            'email' => 'nullable|unique:users,email',
            'mobile' => 'nullable|unique:users,mobile',
            'password' => 'required',
            'role' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $input = new User();
        $input->email = $request->email;
        $input->mobile = $request->mobile;
        $input->name = $request->name;
        $input->user_type = $request->role;
        $input->password = Hash::make($request->password);
        $input->save();
        $input->syncRoles($request->get('role'));

        return response()->json([
            'message' => 'User Assign Successfully',
            'data' => $input,
        ]);
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(User $user, Request $request)
    {

        $rules = [
            'status' => 'nullable',
            'role' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        if (!empty($request->status)) {
            $input = User::find($user);
            $input->status = $request->status;
            $input->user_type = $request->role;
            $input->save();
            $input->syncRoles($request->get('role'));
            return response()->json([
                'message' => 'User Update Successfully',
                'data' => $input,
            ]);
        } else {
            $user->syncRoles($request->get('role'));
            return response()->json([
                'message' => 'User Update Successfully',
                'data' => $user,
            ]);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
