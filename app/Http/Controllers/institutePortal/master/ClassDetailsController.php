<?php

namespace App\Http\Controllers\institutePortal\master;

use App\Models\ClassDetails;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\CoreSubcategory;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ClassDetailsResource;
use App\Http\Resources\CorecategoryResource;
use App\Http\Resources\CoreSubcategoryResource;

class ClassDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $allclasses = CoreSubcategory::where('core_category_id', 4)->get();
        $allgroups = CoreSubcategory::where('core_category_id', 5)->get();
        $allshifts = CoreSubcategory::where('core_category_id', 3)->get();
        $allsections = CoreSubcategory::where('core_category_id', 6)->get();
        $classdetails = ClassDetails::with('groups', 'shifts', 'sections')->get();
        return response()->json([
            'groups' => CoreSubcategoryResource::collection($allgroups),
            'shifts' => CoreSubcategoryResource::collection($allshifts),
            'sections' => CoreSubcategoryResource::collection($allsections),
            'classdetails' => ClassDetailsResource::collection($classdetails),
            'classes' => CoreSubcategoryResource::collection($allclasses)
         ]);
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
            'group_id' => 'required',
            'shift_id' => 'required',
            'section_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validation passed, proceed to check for duplicates
        $group_id = $request->input('group_id');
        $shift_id = $request->input('shift_id');
        $section_id = $request->input('section_id');

        try {
            $input = new ClassDetails();
            $input->group_id = $group_id;
            $input->shift_id = $shift_id;
            $input->section_id = $section_id;
            $input->save();

            return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
            
        }catch (QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {                
                return response()->json(['status' => 'error', 'message' => 'Duplicate entry'], Response::HTTP_CONFLICT);
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
    public function edit(string $id)
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
