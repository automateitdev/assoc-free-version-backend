<?php

namespace App\Http\Controllers\institutePortal\feesManagement;

use App\Helpers\ApiResponseHelper;
use App\Models\FeeHead;
use App\Models\PayApply;
use App\Models\FeeAmount;
use App\Models\ClassDetails;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Traits\AmountSetTraits;
use App\Models\InstituteClassMap;
use Illuminate\Support\Facades\DB;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\FeeHeadResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CoreInstituteResource;

class AmountSettingController extends Controller
{
    use AmountSetTraits;

    public function index()
    {
        $feeHead = FeeHead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $academicYears = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 1);
            })
            ->with('coresubcategories.corecategory')
            ->get();
        $groups = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 5);
            })
            ->with('coresubcategories.corecategory')
            ->get();
        $category = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 7);
            })
            ->with('coresubcategories.corecategory')
            ->get();
        $instituteClassMap = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)->with('classDetails.shifts', 'classDetails.sections', 'classDetails.groups')->get();
        return response()->json([
            'instituteClassMap' => $instituteClassMap,
            'academicYears' => CoreInstituteResource::collection($academicYears),
            'category' => CoreInstituteResource::collection($category),
            'groups' => CoreInstituteResource::collection($groups),
            'feeHead' => FeeHeadResource::collection($feeHead),
        ]);
    }

    public function feeAmountStore(Request $request)
    {
        $rules = [
            'class_id' => 'required',
            'group_id' => 'required',
            'academic_year_id' => 'required',
            'student_category_id' => 'required',
            'fee_head_id' => 'required',
            'fee_amount' => 'required',
            'fine_amount' => 'nullable'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        $group_id = $request->group_id;
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $checkDuplicate = FeeAmount::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('class_id', $request->class_id)
            ->where('group_id', $request->group_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('student_category_id', $request->student_category_id)
            ->where('fee_head_id', $request->fee_head_id)
            ->first();

        if ($checkDuplicate) {
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Amount already set!']);
            return response()->json(
                [
                    'errors' => $systemError,
                    'payload' => null,
                ],
                Response::HTTP_CONFLICT
            );
        }
        $pivotId = [];

        $instituteClassMaps = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('class_id', $request->class_id)
            ->with('classDetails', function ($query) use ($group_id) {
                $query->where('group_id', $group_id);
            })
            ->first();
        foreach ($instituteClassMaps->classDetails as $classDetail) {
            $pivotId[] = $classDetail->pivot->id;
        }

        $input = new FeeAmount();
        $input->institute_details_id = Auth::user()->institute_details_id;
        $input->class_id = $request->class_id;
        $input->group_id = $request->group_id;
        $input->academic_year_id = $request->academic_year_id;
        $input->student_category_id = $request->student_category_id;
        $input->fee_head_id = $request->fee_head_id;
        $input->fee_amount = $request->fee_amount;
        $input->fine_amount = $request->fine_amount;
        $input->save();

        $this->StudentWisePayappliesUpdate($request, Auth::user()->institute_details_id, $pivotId, "no", 'no');
        return response()->json([
            'status' => 'success',
            'message' => 'Amount successfully added',
            'data' => $input,
        ]);
    }

    public function feeAmountShow(Request $request)
    {
        $rules = [
            'academic_year_id' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $feeamounts = FeeAmount::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)->with('class', 'classDetail', 'categories.coresubcategories', 'feehead')->get();

        $groupedFeeAmounts = [];
        foreach ($feeamounts as $feeamount) {
            // return $feeamount;
            $key = implode('_', [
                $feeamount->institute_details_id,
                $feeamount->class->core_subcategory_name,
                $feeamount->classDetail->groups->core_subcategory_name,
                $feeamount->academic_year_id,
                $feeamount->categories->coresubcategories->core_subcategory_name,
                $feeamount->feehead->name,
            ]);

            if (!isset($groupedFeeAmounts[$key])) {
                // If the key does not exist, initialize it with the common fields
                $groupedFeeAmounts[$key] = [
                    'id' => $feeamount->id,
                    'institute_details_id' => $feeamount->institute_details_id,
                    'class_id' => $feeamount->class->core_subcategory_name,
                    'group_id' => $feeamount->classDetail->groups->core_subcategory_name,
                    'academic_year_id' => $feeamount->academic_year_id,
                    'student_category_id' => $feeamount->categories->coresubcategories->core_subcategory_name,
                    'fee_head_id' => $feeamount->feehead->name,
                    'fee_amount' => $feeamount->fee_amount,
                    'fine_amount' => $feeamount->fine_amount,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'classwiseFeeAmountSetup' => array_values($groupedFeeAmounts),
        ]);
        // return response()->json(['feeamounts' => array_values($groupedFeeAmounts)]);
    }
    public function feeAmountEdit($id)
    {
        $check = FeeAmount::find($id);
        $instituteClassmap = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('class_id', $check->class_id)->first();
        $classDetails = ClassDetails::where('group_id', $check->group_id)->get()->pluck('id')->toArray();


        $pivot_id = DB::table('class_details_institute_class_map')->where('institute_class_map_id', $instituteClassmap->id)
            ->whereIn('class_details_id', $classDetails)
            ->get()
            ->pluck('id')
            ->toArray();
        $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereIn('combinations_pivot_id', $pivot_id)
            ->where('academic_year_id', $check->academic_year_id)
            ->where('fee_head_id', $check->fee_head_id)
            ->pluck('payment_state')->toArray();

        if (in_array("PAID", $payapplies)) {
            return response()->json(['status' => 'error', 'message' => 'This fee amount cannot be edited.'], Response::HTTP_BAD_REQUEST);
        }
        $feeamounts = FeeAmount::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('class_id', $check->class_id)
            ->where('group_id', $check->group_id)
            ->where('academic_year_id', $check->academic_year_id)
            ->where('student_category_id', $check->student_category_id)
            ->where('fee_head_id', $check->fee_head_id)
            ->with('class', 'classDetail', 'categories.coresubcategories', 'feehead')->get();

        // Grouping the results by common fields
        $groupedFeeAmounts = [];
        foreach ($feeamounts as $feeamount) {
            // return $feeamount;
            $key = implode('_', [
                $feeamount->institute_details_id,
                $feeamount->class->core_subcategory_name,
                $feeamount->classDetail->groups->core_subcategory_name,
                $feeamount->academic_year_id,
                $feeamount->categories->coresubcategories->core_subcategory_name,
                $feeamount->feehead->name,
            ]);

            if (!isset($groupedFeeAmounts[$key])) {
                // If the key does not exist, initialize it with the common fields
                $groupedFeeAmounts[$key] = [
                    'id' => $feeamount->id,
                    'institute_details_id' => $feeamount->institute_details_id,
                    'class' => $feeamount->class->core_subcategory_name,
                    'class_id' => $feeamount->class_id,
                    'group' => $feeamount->classDetail->groups->core_subcategory_name,
                    'group_id' => $feeamount->group_id,
                    'academic_year_id' => $feeamount->academic_year_id,
                    'student_category_name' => $feeamount->categories->coresubcategories->core_subcategory_name,
                    'student_category_id' => $feeamount->student_category_id,
                    'fee_head_name' => $feeamount->feehead->name,
                    'fee_head_id' => $feeamount->fee_head_id,
                    'fee_amount' => $feeamount->fee_amount,
                    'fine_amount' => $feeamount->fine_amount,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'feeamounts' => array_values($groupedFeeAmounts),
        ]);
        // return response()->json(['feeamounts' => array_values($groupedFeeAmounts)]);
    }

    public function feeAmountUpdate(Request $request)
    {
        $rules = [
            'class_id' => 'required',
            'group_id' => 'required',
            'academic_year_id' => 'required',
            'student_category_id' => 'required',
            'fee_head_id' => 'required',
            'fee_amount' => 'required',
            'fine_amount' => 'nullable'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        $group_id = $request->group_id;
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $feeAmounts = FeeAmount::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('class_id', $request->class_id)
            ->where('group_id', $request->group_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('student_category_id', $request->student_category_id)
            ->where('fee_head_id', $request->fee_head_id)
            ->get();
        foreach ($feeAmounts as $updatefee) {
            $update = FeeAmount::find($updatefee->id)->update(
                [
                    'fee_amount' => $request->fee_amount,
                    'fine_amount' => $request->fine_amount,
                ]
            );
        }

        $pivotId = [];

        $instituteClassMaps = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('class_id', $request->class_id)
            ->with('classDetails', function ($query) use ($group_id) {
                $query->where('group_id', $group_id);
            })
            ->first();
        foreach ($instituteClassMaps->classDetails as $classDetail) {
            $pivotId[] = $classDetail->pivot->id;
        }

        $this->PayappliesUpdate($request, Auth::user()->institute_details_id, $pivotId, "no", 'no');

        // $feesubhead = FeeMapping::where('institute_details_id', Auth::user()->institute_details_id)
        //     ->where('fee_head_id', $request->fee_head_id)
        //     ->get()
        //     ->pluck('fee_subhead_id');
        // $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
        //     ->whereIn('combinations_pivot_id', $pivotId)
        //     ->where('academic_year_id', $request->academic_year_id)
        //     ->where('fee_head_id', $request->fee_head_id)
        //     ->whereIn('fee_subhead_id', $feesubhead)
        //     ->get();


        // foreach ($payapplies as $payapplie) {
        //     $payUpdate = [
        //         'id' => $payapplie->id,
        //         'payable' => $request->fee_amount,
        //         'total_amount' => $request->fee_amount,
        //         'fine_amount' => $request->fine_amount ?? null
        //     ];
        //     dispatch(new PayapplyUpdateJob($payUpdate));
        // }

        return response()->json([
            'status' => 'success',
            'message' => 'Amount Updated successfully',
            'data' => $update,
        ]);
    }
}
