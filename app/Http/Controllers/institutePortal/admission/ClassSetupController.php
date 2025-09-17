<?php

namespace App\Http\Controllers\institutePortal\admission;

use App\Models\CoreCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\CoreSubcategory;
use App\Models\AdmissionStartup;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Models\AdmissionClassSetup;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CorecategoryResource;
use App\Http\Resources\CoreSubcategoryResource;
use App\Http\Resources\AdmissionClassSetupResource;

class ClassSetupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Auth::user()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Unauthorized']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 401);
        }
        // $category_set = CoreCategory::with('coresubcategories')->get();
        $category_set = CoreCategory::whereIn('id', [1, 3, 4, 5])->with('coresubcategories')->get();
        $admissionStartup = AdmissionStartup::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $groupedAdmissionStartup = $admissionStartup->groupBy('core_category_id')->map(function ($categoryGroup) {
            return [
                'catergory_id' => $categoryGroup->first()->core_category_id,
                'category_name' => $categoryGroup->first()->core_category_name,
                'subcategories' => $categoryGroup->map(function ($item) {
                    return [
                        'subcategory_id' => $item->core_subcategory_id,
                        'subcategory_name' => $item->core_subcategory_name,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();
        // $coreSubCategories = CoreSubcategory::all();
        $admissionClassSetup = AdmissionClassSetup::where('institute_details_id', Auth::user()->institute_details_id)->get();

        // Group by class_id and format the response
        $groupedClasses = $admissionClassSetup->groupBy('class_id')->map(function ($classGroup) {
            return [
                'class_id' => $classGroup->first()->class_id,
                'class_name' => $classGroup->first()->class_name,
                'groups' => $classGroup->map(function ($item) {
                    return [
                        'group_id' => $item->group_id,
                        'group_name' => $item->group_name,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();
        return response()->json([
            'admissionClassSetup' => $groupedClasses,
            'category_set' => $category_set,
            'admission_startup' => $groupedAdmissionStartup,
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
            'class_id' => 'required',
            'class_name' => 'required',
            'center_id' => 'required',
            'center_name' => 'required',
            'institute_id' => 'required|array|min:1',
            'institute_name' => 'required|array|min:1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();

        try {

            foreach ($request->group_id as $key => $center_id) {
                $check = AdmissionClassSetup::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('class_id', $request->class_id)
                    ->where('group_id', $center_id)->first();
                if ($check) {
                    continue;
                }
                $input = new AdmissionClassSetup();
                $input->institute_details_id = Auth::user()->institute_details_id;
                $input->class_id = $request->class_id;
                $input->class_name = $request->class_name;
                $input->institute_id = $request->institute_id;
                $input->institute_name = $request->institute_name;
                $input->center_id = $center_id;
                $input->center_name = $request->center_name[$key];
                $input->save();
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
        } catch (QueryException $e) {
            DB::rollBack();
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json(['status' => 'error', 'message' => 'Duplicate entry'], Response::HTTP_CONFLICT);
            }

            Log::error("class setup failed : $e");
            return response()->json(['status' => 'error', 'message' => 'Database error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("class setup failed : $e");
            return response()->json(['status' => 'error', 'message' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
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

    public function admissionStartup(Request $request)
    {
        $rules = [
            'coresubcategory_details_id' => 'required|array|min:1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        foreach ($request->coresubcategory_details_id as $core_subcategory_id) {

            $admissionStartup = new AdmissionStartup();

            $coresub = CoreSubcategory::find($core_subcategory_id);
            $coreCat = CoreCategory::find($coresub->core_category_id);
            $setupFound = AdmissionStartup::where('institute_details_id', Auth::user()->institute_details_id)->where('core_category_id', $coreCat->id)->where('core_subcategory_id', $core_subcategory_id)->exists();

            if ($setupFound) {
                continue;
            }

            $admissionStartup->institute_details_id = Auth::user()->institute_details_id;
            $admissionStartup->core_category_id = $coreCat->id;
            $admissionStartup->core_category_name = $coreCat->core_category_name;
            $admissionStartup->core_subcategory_id = $core_subcategory_id;
            $admissionStartup->core_subcategory_name = $coresub->core_subcategory_name;
            $admissionStartup->save();
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Created Successfully!',
                'admission_startup' => $admissionStartup
            ],
            Response::HTTP_CREATED
        );
    }
}
