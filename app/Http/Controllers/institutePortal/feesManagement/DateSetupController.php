<?php

namespace App\Http\Controllers\institutePortal\feesManagement;

use App\Models\FeeHead;
use App\Models\PayApply;
use App\Models\DateSetup;
use App\Jobs\DateSetupJob;
use App\Models\FeeMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Jobs\DateSetupUpdateJob;
use App\Models\InstituteClassMap;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\FeeHeadResource;
use App\Http\Resources\DateSetupResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\FeeMappingResource;
use App\Http\Resources\FeeSubheadResource;
use App\Http\Resources\CoreInstituteResource;
use App\Http\Resources\FeeHeadToSubhedShowResource;
use App\Models\FeeSubhead;

class DateSetupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $feeHead = FeeHead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $feeSubhead = FeeSubhead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $academicYears = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 1);
            })
            ->with('coresubcategories.corecategory')
            ->get();

        $instituteClassMap = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)->get();
        return response()->json([
            'status' => 'success',
            'instituteClassMap' => $instituteClassMap,
            'academicYears' => CoreInstituteResource::collection($academicYears),
            'feeHead' => FeeHeadResource::collection($feeHead),
            'feeSubhead' => FeeSubheadResource::collection($feeSubhead),
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
            'class_id' => 'required|array|min:1',
            'academic_year_id' => 'required',
            'fee_head_id' => 'required',
            'fee_subhead_id' => 'required|array|min:1',
            'fee_payable_date' => 'required|array|min:1',
            'fine_active_date' => 'nullable|array|min:1',
            'fee_expire_date' => 'nullable|array|min:1'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $pivotId = [];

        $instituteClassMaps = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereIn('class_id', $request->class_id)
            ->with('classDetails')
            ->get();
        foreach ($instituteClassMaps as $instituteClassMap) {
            foreach ($instituteClassMap->classDetails as $classDetail) {
                $pivotId[] = $classDetail->pivot->id;
            }
        }

        $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->whereIn('combinations_pivot_id', $pivotId)
            ->where('payment_state', 'UNPAID')
            ->get();
        foreach ($payapplies as $student) {
            foreach ($request->fee_subhead_id as $key => $fee_subhead_id) {
                if ($student->fee_head_id == $request->fee_head_id && $student->fee_subhead_id == $fee_subhead_id) {
                    $payUpdate = [
                        'id' => $student->id,
                        'fee_payable_date' => $request->fee_payable_date[$key],
                        'fine_active_date' => $request->fine_active_date[$key] ?? null,
                        'fee_expire_date' => $request->fee_expire_date[$key] ?? null
                    ];
                    dispatch(new DateSetupJob($payUpdate));
                }

                foreach ($request->class_id as $class_id) {
                    $check = DateSetup::where('institute_details_id', Auth::user()->institute_details_id)
                        ->where('academic_year_id', $request->academic_year_id)
                        ->where('class_id', $class_id)
                        ->where('fee_head_id', $request->fee_head_id)
                        ->where('fee_subhead_id', $fee_subhead_id)
                        ->first();
                    if ($check) {
                        continue;
                    }
                    $datesetup = new DateSetup();
                    $datesetup->institute_details_id = Auth::user()->institute_details_id;
                    $datesetup->class_id = $class_id;
                    $datesetup->academic_year_id = $request->academic_year_id;
                    $datesetup->fee_head_id = $request->fee_head_id;
                    $datesetup->fee_subhead_id = $fee_subhead_id;
                    $datesetup->fee_payable_date = $request->fee_payable_date[$key];
                    $datesetup->fine_active_date = $request->fine_active_date[$key] ?? null;
                    $datesetup->fee_expire_date = $request->fee_expire_date[$key] ?? null;
                    $datesetup->save();
                }
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        // $datesetup->fee_head_id = $request->fee_head_id;
        // $datesetup->fee_subhead_id = $fee_subhead_id;

        $existingDatesetups  = DateSetup::where('fee_head_id', $request->fee_head_id)->whereNotNull('fee_payable_date')->get();

        $alreadySet = $existingDatesetups->pluck('fee_subhead_id')->toArray();

        $fee_subhead = FeeMapping::where('institute_details_id', Auth::user()->institute_details_id)
        ->where('fee_head_id', $request->fee_head_id)->whereNotIn('fee_subhead_id', $alreadySet)->with('feeSubhead')->get();


        return response()->json([
            'status' => 'success',
            'feeSubhead' => FeeMappingResource::collection($fee_subhead)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function search(Request $request)
    {
        $rules = [
            'class_id' => 'required',
            'academic_year_id' => 'required',
            'fee_head_id' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }


        $matches = DateSetup::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('class_id', $request->class_id)
            ->where('fee_head_id', $request->fee_head_id)
            ->with('feeSubhead')
            ->get();
        return response()->json([
            'status' => 'success',
            'DateSetup' => DateSetupResource::collection($matches)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $rules = [
            'date_setup_id' => 'required|array|min:1',
            'fee_payable_date' => 'required|array|min:1',
            'fine_active_date' => 'nullable|array|min:1',
            'fee_expire_date' => 'nullable|array|min:1'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        foreach ($request->date_setup_id as $key => $date_setup_id) {
            $datesetup = DateSetup::find($date_setup_id);

            $pivotId = [];

            $instituteClassMaps = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('class_id', $datesetup->class_id)
                ->with('classDetails')
                ->first();

            foreach ($instituteClassMaps->classDetails as $classDetail) {
                $pivotId[] = $classDetail->pivot->id;
            }

            $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year_id', $datesetup->academic_year_id)
                ->whereIn('combinations_pivot_id', $pivotId)
                ->where('payment_state', 'UNPAID')
                ->get();
            foreach ($payapplies as $student) {
                if ($student->fee_head_id == $datesetup->fee_head_id && $student->fee_subhead_id == $datesetup->fee_subhead_id) {
                    $payUpdate = [
                        'pay_id' => $student->id,
                        'date_setup_id' => $date_setup_id,
                        'fee_payable_date' => $request->fee_payable_date[$key],
                        'fine_active_date' => $request->fine_active_date[$key] ?? null,
                        'fee_expire_date' => $request->fee_expire_date[$key] ?? null
                    ];
                    dispatch(new DateSetupUpdateJob($payUpdate));
                }
            }
        }
        return response()->json(['status' => 'success', 'message' => 'Record Update successfully'], Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
