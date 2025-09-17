<?php

namespace App\Http\Controllers\institutePortal\master;

use App\Models\CoreCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CorecategoryResource;
use App\Http\Resources\CoreInstituteResource;
use App\Http\Resources\CoreSubcategoryResource;
use Illuminate\Support\Facades\Log;

class CoreInstituteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $corecategories = CoreCategory::all();
        $coreInstituteConfig = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)->get();
        return response()->json([
            'corecategories' => CorecategoryResource::collection($corecategories),
            'coreInstituteConfig' => CoreInstituteResource::collection($coreInstituteConfig)
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
            'coresubcategory_details_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validation passed, proceed to check for duplicates
        $subcat_ids = $request->input('coresubcategory_details_id');

        try {
            foreach ($subcat_ids as $subcat_id) {
                $input = new CoreInstituteConfig();
                $input->institute_details_id = Auth::user()->institute_details_id;
                $input->coresubcategory_details_id = $subcat_id;
                $input->save();
            }
            return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], 201);
        } catch (\Exception $e) {
            if ($e->getCode() == 23000) {
                return response()->json(['status' => 'error', 'message' => 'Duplicate entry'], Response::HTTP_CONFLICT);
            }
            return response()->json(['status' => 'error', 'message' => 'An Unexpected Error!'], Response::HTTP_CONFLICT);
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
    public function show(CoreCategory $corecategory)
    {
        return CoreSubcategoryResource::collection($corecategory->coresubcategories);
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
