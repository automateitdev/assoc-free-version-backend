<?php

namespace App\Http\Controllers\institutePortal\master;

use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\InstituteDepartmentMap;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $departments = Department::join('institute_department_maps', 'departments.id', '=', 'institute_department_maps.department_id')
                ->where('institute_department_maps.institute_id', Auth::user()->institute_details_id)
                ->select('departments.*')
                ->get();

            return response()->json([
                'errors' => null,
                'payload' => $departments,
            ], 200);
        } catch (\Exception $e) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::HTTP_INTERNAL_SERVER_ERROR, ['An unexpected error occurred! Try later.']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
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
            'name' => 'required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        // Trim and camel case the department name
        $departmentName = Str::camel(trim($request->name));

        try {
            DB::beginTransaction();

            // Check if the department already exists
            $existingDepartment = Department::where('name', $departmentName)->first();

            if ($existingDepartment) {
                // Ensure no duplicate mapping exists
                $existingMapping = InstituteDepartmentMap::where('institute_id', Auth::user()->institute_details_id)
                    ->where('department_id', $existingDepartment->id)
                    ->first();

                if (!$existingMapping) {
                    // Map department to institute
                    $instituteDepartmentMap = new InstituteDepartmentMap();
                    $instituteDepartmentMap->institute_id = Auth::user()->institute_details_id;
                    $instituteDepartmentMap->department_id = $existingDepartment->id;
                    $instituteDepartmentMap->save();
                }

                DB::commit();

                return response()->json([
                    'errors' => null,
                    'payload' => $existingDepartment,
                ], 200);
            } else {
                // Save new department
                $department = new Department();
                $department->name = $departmentName;
                $department->save();

                // Map department to institute
                $instituteDepartmentMap = new InstituteDepartmentMap();
                $instituteDepartmentMap->institute_id = Auth::user()->institute_details_id;
                $instituteDepartmentMap->department_id = $department->id;
                $instituteDepartmentMap->save();

                DB::commit();

                return response()->json([
                    'errors' => null,
                    'payload' => $department,
                ], 200);
            }
        } catch (QueryException $e) {
            DB::rollBack();
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) { // Duplicate entry
                return response()->json([
                    'status' => 'error',
                    'message' => 'Duplicate entry'
                ], Response::HTTP_CONFLICT);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Database error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
    public function edit($id)
    {
        try {
            // Fetch department by ID
            $department = Department::findOrFail($id);

            return response()->json([
                'errors' => null,
                'payload' => $department,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        // Trim and camel case the department name
        $departmentName = Str::camel(trim($request->name));

        try {
            DB::beginTransaction();

            // Find the department
            $department = Department::findOrFail($id);

            // Check if the name already exists
            $existingDepartment = Department::where('name', $departmentName)->where('id', '!=', $id)->first();
            if ($existingDepartment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Department name already exists',
                ], Response::HTTP_CONFLICT);
            }

            // Update the department
            $department->name = $departmentName;
            $department->save();

            DB::commit();

            return response()->json([
                'errors' => null,
                'payload' => $department,
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Find the department
            $department = Department::findOrFail($id);

            // Delete the mappings first
            InstituteDepartmentMap::where('department_id', $department->id)->delete();

            // Delete the department
            $department->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Department deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
