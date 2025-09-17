<?php

namespace App\Http\Controllers\institutePortal\feesManagement;

use App\Helpers\ApiResponseHelper;
use App\Models\FeeHead;
use App\Models\PayApply;
use App\Models\FeeMapping;
use App\Models\FeeSubhead;
use App\Models\ClassDetails;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\InstituteClassMap;
use Illuminate\Support\Facades\DB;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\FeeHeadResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\FeeMappingResource;
use App\Http\Resources\FeeSubheadResource;
use App\Http\Resources\CoreInstituteResource;

class RemoveController extends Controller
{
    public function index()
    {
        $feeHead = FeeHead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $feeSubhead = FeeSubhead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $feeMapping = FeeMapping::where('institute_details_id', Auth::user()->institute_details_id)->with(['feeHead', 'feeSubhead'])->get();
        $academicYears = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 1);
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


        // Initialize an empty array to store the grouped data
        $groupedData = [];

        // Iterate through each FeeMapping record
        foreach ($feeMapping as $mapping) {
            // Extract necessary details
            $feeHeadId = $mapping->feeHead->id;
            $feeHeadName = $mapping->feeHead->name;
            $feeSubheadId = $mapping->feeSubhead->id;
            $feeSubheadName = $mapping->feeSubhead->name;

            // Group by fee_head_id
            if (!isset($groupedData[$feeHeadId])) {
                $groupedData[$feeHeadId] = [
                    'fee_head_id' => $feeHeadId,
                    'fee_head_name' => $feeHeadName,
                    'fee_subheads' => []
                ];
            }

            // Push fee_subhead details into the respective fee_head group
            $groupedData[$feeHeadId]['fee_subheads'][] = [
                'fee_subhead_id' => $feeSubheadId,
                'fee_subhead_name' => $feeSubheadName,
            ];
        }

        // Convert the grouped data array into a simple array
        $groupedData = array_values($groupedData);

        return response()->json([
            'status' => 'success',
            'feeMapping' => $groupedData,
            // 'feeMapping' => FeeMappingResource::collection($feeMapping),
            'instituteClassMap' => $instituteClassMap,
            'academicYears' => CoreInstituteResource::collection($academicYears),
            'feeHead' => FeeHeadResource::collection($feeHead),
            'feeSubhead' => FeeSubheadResource::collection($feeSubhead),
            'category' => CoreInstituteResource::collection($category),
        ]);
    }

    public function search(Request $request)
    {
        // $rules = [
        //     'combinations_pivot_id' => 'required',
        //     'academic_year_id' => 'required',
        // ];

        // // Validate the request data
        // $validator = Validator::make($request->all(), $rules);

        // // Check if validation fails
        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        // }
        $rules = [
            'institute_class_map_id' => 'required',
            'section_id' => 'required',
            'shift_id' => 'required',
            'academic_year_id' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }
        $classDetails = ClassDetails::where('shift_id', $request->shift_id)
            ->where('section_id', $request->section_id)->pluck('id')->toArray();
        $pivotIds = DB::table('class_details_institute_class_map')
            ->where('institute_class_map_id', $request->institute_class_map_id)
            ->whereIn('class_details_id', $classDetails)
            ->pluck('id')->toArray(); // Use pluck to get a single value;


        $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->whereIn('combinations_pivot_id', $pivotIds)
            ->get();

        $uniquePayApplies = collect([]);

        foreach ($payapplies as $payapply) {
            if (!$uniquePayApplies->contains(
                'student_id',
                $payapply->student_id
            )) {
                $uniquePayApplies->push($payapply);
            }
        }

        $payData = [];

        foreach ($uniquePayApplies as $pay) {
            $payData[] = [
                'id' => $pay->id,
                'student_id' => $pay->student_id,
                'custom_student_id' => $pay->academic_details->custom_student_id,
                'roll' => $pay->academic_details->class_roll,
                'name' => $pay->student_details->student_name,
                'Gender' => $pay->student_details->student_gender,
                'Religion' => $pay->student_details->student_religion,
                'Category' => $pay->academic_details->categories->coresubcategories->core_subcategory_name,
            ];
        }
        return response()->json([
            'status' => 'success',
            'student_list' => $payData,
        ]);
    }

    public function fee_head_remove(Request $request)
    {
        $rules = [
            'student_id' => 'required|array|min:1',
            'fee_head_id' => 'required|array|min:1',
            'academic_year_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        foreach ($request->student_id as $id) {
            foreach ($request->fee_head_id as $feehead) {
                $payapplies = PayApply::where('student_id', $id)
                    ->where('fee_head_id', $feehead)
                    ->where('academic_year_id', $request->academic_year_id)
                    ->where('payment_state', 'UNPAID')
                    ->first();
                if ($payapplies) {
                    $payapplies->update(['payment_state' => 'REMOVE']);
                }
            }
        }
        return response()->json([
            'status' => 'success',
        ]);
    }

    public function fee_subhead_remove(Request $request)
    {
        $rules = [
            'student_id' => 'required|array|min:1',
            'fee_subhead_id' => 'required|array|min:1',
            'academic_year_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        foreach ($request->student_id as $id) {
            foreach ($request->fee_subhead_id as $subhead) {
                $payapplies = PayApply::where('student_id', $id)
                    ->where('fee_subhead_id', $subhead)
                    ->where('academic_year_id', $request->academic_year_id)
                    ->where('payment_state', 'UNPAID')
                    ->first();
                if ($payapplies) {
                    $payapplies->update(['payment_state' => 'REMOVE']);
                }
            }
        }
        return response()->json([
            'status' => 'success',
        ]);
    }

    public function fee_head_show(Request $request)
    {

        $rules = [
            'student_id' => 'required',
            'academic_year_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('student_id', $request->student_id)
            ->where('payment_state', 'REMOVE')
            ->get();

        $feeHeadNames = $payapplies->map(function ($payapply) {
            return [
                'id' => $payapply->fee_head_id,
                'name' => $payapply->feeHead->name
            ];
        });

        return response()->json([
            'status' => 'success',
            'feeHeads' => $feeHeadNames
        ]);
    }

    public function fee_head_reassign(Request $request)
    {
        $rules = [
            'student_id' => 'required',
            'academic_year_id' => 'required',
            'fee_head_id' => 'required|array|min:1',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        foreach ($request->fee_head_id as $feehead) {
            $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('student_id', $request->student_id)
                ->where('fee_head_id', $feehead)
                ->first();
            if ($payapplies) {
                $payapplies->update(['payment_state' => 'UNPAID']);
            }
        }
        return response()->json([
            'status' => 'success',
        ]);
    }

    public function sub_head_show(Request $request)
    {
        $rules = [
            'student_id' => 'required',
            'fee_head_id' => 'required',
            'academic_year_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('student_id', $request->student_id)
            ->where('fee_head_id', $request->fee_head_id)
            ->where('payment_state', 'REMOVE')
            ->get();

        $subHeadNames = $payapplies->map(function ($payapply) {
            return [
                'id' => $payapply->fee_subhead_id,
                'name' => $payapply->feeSubead->name
            ];
        });
        return response()->json([
            'status' => 'success',
            'subHeads' => $subHeadNames
        ]);
    }

    public function sub_head_reassign(Request $request)
    {
        $rules = [
            'student_id' => 'required',
            'academic_year_id' => 'required',
            'fee_subhead_id' => 'required|array|min:1',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        foreach ($request->fee_subhead_id as $subhead) {
            $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('student_id', $request->student_id)
                ->where('fee_subhead_id', $subhead)
                ->first();
            if ($payapplies) {
                $payapplies->update(['payment_state' => 'UNPAID']);
            }
        }
        return response()->json([
            'status' => 'success',
        ]);
    }
}
