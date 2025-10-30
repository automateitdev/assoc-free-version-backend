<?php

namespace App\Http\Controllers\operationPortal\global;

use App\Helpers\ApiResponseHelper;
use App\Models\CoreCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\CoreSubcategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CoreSubcategoryResource;
use App\Utils\ServerErrorMask;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CoreSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $corecategories = CoreCategory::with('coresubcategories')->get();
        // $coreSubcategories = CoreSubcategory::with('corecategory')->get();
        return response()->json(
            // [
                $corecategories
                // 'coreSubcategories' => $coreSubcategories,
            // ]
        );
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
            'core_category_id' => 'required|integer',
            'core_subcategory_name' => [
                'required',
                'string',
            ],
        ];

        if ($request->core_category_id !== 8 && $request->core_category_id !== 10) {
            $rules['core_subcategory_name'][] = 'regex:/^[a-zA-Z0-9\s\-]+$/';
        }


        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            $formatError = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formatError,
                'payload' => null,
            ], 400);
        }

        try {
            $inputName = $this->normalizeCategoryName($request->input('core_subcategory_name'));

            // Check if a category with the normalized name already exists
            $existingCategory = CoreSubcategory::where('core_subcategory_name', $inputName)->where('core_category_id', $request->core_category_id)->exists();

            if ($existingCategory) {
                $formatError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, 'Duplicate entry');
                return response()->json([
                    'errors' => $formatError,
                    'payload' => null,
                ], 400);
            }

            // Create a new subcategory
            $subcategory = new CoreSubcategory();
            $subcategory->core_subcategory_name = $inputName;
            $subcategory->core_category_id = $request->core_category_id;
            $subcategory->save();
            return response()->json(['status' => 'success', 'message' => 'Core Sub Category Created Successfully!'], 201);
        } catch (QueryException $e) {
            Log::error("FAIL: Subcategory save: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SYSTEM_BUSY);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        } catch (ModelNotFoundException $e) {
            Log::error("FAIL: Subcategory save: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, 'Not Found');
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 404);
        } catch (\Exception $e) {
            Log::error("FAIL: Subcategory save: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        }
    }
    private function normalizeCategoryName($name)
    {
        // Normalize the category name by making it lowercase and removing special characters
        $normalized = strtolower($name);

        // Replace non-alphanumeric characters, spaces, and multiple consecutive spaces with hyphens
        $normalized = preg_replace('/[^a-z0-9\s]+/', '-', $normalized);

        // Trim any leading or trailing hyphens
        $normalized = trim($normalized, '-');

        // Add parentheses around the month range if it exists
        $normalized = preg_replace('/([a-z]+-[a-z]+)$/', '($1)', $normalized);

        return $normalized;
    }


    public function findsubcategory(Request $request)
    {

        // return CoreSubcategoryResource::collection($corecategory->coresubcategories);
        $coresubcategories = CoreSubcategory::where('core_category_id', $request->id)->get();
        return response()->json(CoreSubcategoryResource::collection($coresubcategories));
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
