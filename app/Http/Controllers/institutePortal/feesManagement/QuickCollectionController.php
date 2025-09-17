<?php

namespace App\Http\Controllers\institutePortal\feesManagement;

use Carbon\Carbon;
use App\Models\Waiver;
use App\Models\FeeHead;
use App\Models\PayApply;
use App\Models\OpsMapping;
use App\Models\InstituteHr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AcademicDetail;
use App\Models\PaymentInvoice;
use App\Models\InstituteClassMap;
use Illuminate\Support\Facades\DB;
use App\Jobs\QuickCollectionPayJob;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\WaiverResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CoreInstituteResource;
use App\Http\Resources\PaymentInvoiceResource;
use App\Http\Resources\QuickCollectionResource;
use App\Http\Resources\StudentDetailsShowResource;
use App\Http\Resources\InstituteHrForQuickResource;

class QuickCollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $academicYears = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
        ->whereHas('coresubcategories', function ($query) {
            $query->where('core_category_id', 1);
        })
        ->get();
        $academicSession = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
                ->whereHas('coresubcategories', function ($query) {
                    $query->where('core_category_id', 2);
                })
                ->get();
        $instituteClassMap = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)->with('classDetails.shifts', 'classDetails.sections', 'classDetails.groups')->get();

        $waivers = Waiver::where('institute_details_id', Auth::user()->institute_details_id)->get();
        return response()->json([
            'instituteClassMap' => $instituteClassMap,
            'waivers' => WaiverResource::collection($waivers),
            'academicYears' => CoreInstituteResource::collection($academicYears),
            'academicSession' => CoreInstituteResource::collection($academicSession),
        ]);
    }

    public function instantWaiver(Request $request)
    {
        $rules = [
            'payapplies_id' => 'required',
            'waiver_id' => 'required',
            'waiver_amount' => 'required'
        ];
        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        $payApply = PayApply::find($request->payapplies_id);
        if ($payApply) {
            $payApply->update([
                'waiver_id' => $request->waiver_id,
                'waiver_amount' => $request->waiver_amount,
                'total_amount' => $payApply->total_amount - $request->waiver_amount
            ]);
        }
        return response()->json(['status' => 'success', 'message' => 'Waiver added successfully'], Response::HTTP_CREATED); 

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'payapplies_id' => 'required|array|min:1',
            'payment_of' => 'required|array|min:1',
            'date' => 'required',
            'hr_id' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }


        $studentId = PayApply::whereIn('id', $request->payapplies_id)->first();
        $todaydate = $request->date;
        $now = Carbon::now();
        $unique_code = $now->format('ymdHis');
        $unique_invoice = 'QC' . $studentId->student_id . '_' . Auth::user()->institure_detail->institute_id . '_' . $unique_code;
        $unique_invoice = strtoupper($unique_invoice);
        // return $unique_invoice;

        $totalPayment = array_sum($request->input('payment_of'));

        foreach($request->payapplies_id as $key => $payapplies_id)
        {
            $data = PayApply::find($payapplies_id);
            if(empty($data->due_amount) || $data->due_amount == 0)
            {
                $paymentState = $data->total_amount == $request->payment_of[$key] ? '200' : '11';
                $due = $data->total_amount - $request->payment_of[$key];
                $totalAmount = $data->total_amount - $data->due_amount;
            }else{
                $paymentState = $data->due_amount == $request->payment_of[$key] ? '200' : '11';
                $due = $data->due_amount - $request->payment_of[$key];
                $previous_paid = $data->total_amount - $data->due_amount;
                $totalAmount = $data->total_amount - $previous_paid;
            }

            $payUpdate = [
                'institute_details_id' => Auth::user()->institute_details_id,
                'student_id' => $data->student_id,
                'id' => $data->id,
                'invoice' => $unique_invoice,
                'total' => $totalAmount,
                'paid' => (double)$request->payment_of[$key],
                'due' => $due,
                'payment_date' => $todaydate,
                'payment_type' => "qc",
                'payment_state' => $paymentState,
                'academic_year_id' => $data->academic_year_id,
                'totalPayment' => $totalPayment,
                'hr_id' => $request->hr_id
            ];
            dispatch(new QuickCollectionPayJob($payUpdate));

        }

        return response()->json(['status' => 'success', 'message' => 'Record Store Successfully'], Response::HTTP_CREATED); 

    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $rules = [
            'academic_year_id' => 'required',
            'student_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $studentDetail = AcademicDetail::
                        select(
                            'academic_details.*',
                            'class_name',
                            'forgroup.core_subcategory_name as group',
                            'forshift.core_subcategory_name as shift',
                            'forsection.core_subcategory_name as section'
                        )
                        ->where('academic_details.institute_details_id', Auth::user()->institute_details_id)
                        ->join('class_details_institute_class_map', 'class_details_institute_class_map.id', 'academic_details.combinations_pivot_id')
                        ->join('institute_class_maps', 'institute_class_maps.id', 'class_details_institute_class_map.institute_class_map_id')
                        ->join('class_details', 'class_details.id', 'class_details_institute_class_map.class_details_id')
                        ->join('core_subcategories as forgroup', 'forgroup.id', 'class_details.group_id')
                        ->join('core_subcategories as forshift', 'forshift.id', 'class_details.shift_id')
                        ->join('core_subcategories as forsection', 'forsection.id', 'class_details.section_id')
                        ->where('academic_details.student_id', $request->student_id)
                        ->where('academic_details.academic_year', $request->academic_year_id)
                        ->first();
        $user = InstituteHr::where('id', Auth::user()->institute_details_id)->first();
        
        $payApplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                                ->where('student_id', $request->student_id)
                                ->where('academic_year_id', $request->academic_year_id)
                                ->whereIn('payment_state', ['0','11'])
                                ->with('feeHead') 
                                ->get();
        $feeHeadIds = $payApplies->pluck('fee_head_id')->toArray();

        $checkMapping = OpsMapping::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereIn('fee_head_id', $feeHeadIds)
            ->get();

        $existingFeeHeadIds = $checkMapping->pluck('fee_head_id')->toArray();

        $missingFeeHeadIds = array_diff($feeHeadIds, $existingFeeHeadIds);

        if (!empty($missingFeeHeadIds)) {
            $missingFeeHeadNames = FeeHead::whereIn('id', $missingFeeHeadIds)->pluck('name')->toArray();

            // There are missing fee_head_ids in the OpsMapping table
            $errorMessage = 'OPS Mapping missing for fee_head_id: ' . implode(', ', $missingFeeHeadNames);
            return response()->json(['status' => 'error', 'message' => $errorMessage], Response::HTTP_NOT_FOUND);
        }
        $allPay = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                                ->where('student_id', $request->student_id)
                                ->whereIn('payment_state', ['0','11'])
                                ->get();
        return response()->json([
            'studentDetail' => new StudentDetailsShowResource($studentDetail),
            'user' => new InstituteHrForQuickResource($user),
            'payApplies' => QuickCollectionResource::collection($allPay)
        ]);
    }

    public function QuickInvoiceShow(Request $request)
    {
        $rules = [
            'academic_year_id' => 'required',
            'student_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $studentDetail = AcademicDetail::
                        select(
                            'academic_details.*',
                            'class_name',
                            'forgroup.core_subcategory_name as group',
                            'forshift.core_subcategory_name as shift',
                            'forsection.core_subcategory_name as section'
                        )
                        ->where('academic_details.institute_details_id', Auth::user()->institute_details_id)
                        ->join('class_details_institute_class_map', 'class_details_institute_class_map.id', 'academic_details.combinations_pivot_id')
                        ->join('institute_class_maps', 'institute_class_maps.id', 'class_details_institute_class_map.institute_class_map_id')
                        ->join('class_details', 'class_details.id', 'class_details_institute_class_map.class_details_id')
                        ->join('core_subcategories as forgroup', 'forgroup.id', 'class_details.group_id')
                        ->join('core_subcategories as forshift', 'forshift.id', 'class_details.shift_id')
                        ->join('core_subcategories as forsection', 'forsection.id', 'class_details.section_id')
                        ->where('academic_details.student_id', $request->student_id)
                        ->where('academic_details.academic_year', $request->academic_year_id)
                        ->first();

        $paymentInvoices = PaymentInvoice::where('institute_details_id', Auth::user()->institute_details_id)
                                ->where('student_id', $request->student_id)
                                ->where('academic_year_id', $request->academic_year_id)
                                ->whereIn('status', ['200','11'])
                                ->get();
        
        return response()->json([
            'studentDetail' => new StudentDetailsShowResource($studentDetail),
            'paymentInvoices' => PaymentInvoiceResource::collection($paymentInvoices)
            // 'payApplies' => QuickCollectionResource::collection($allPay)
        ]);
    }
    
    public function reportGenerate(Request $request)
    {
        $rules = [
            'academic_year_id' => 'required',
            'student_id' => 'required',
            'invoice' => 'required',
        ];
        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        $hrDetails = PaymentInvoice::where('institute_details_id', Auth::user()->institute_details_id)
                                    ->where('payment_invoice', $request->invoice)->with('hr_detail')->first();
        $instituteDetails = Auth::user()->institure_detail;
        $studentDetail = AcademicDetail::
                        select(
                            'academic_details.*',
                            'class_name',
                            'forgroup.core_subcategory_name as group',
                            'forshift.core_subcategory_name as shift',
                            'forsection.core_subcategory_name as section'
                        )
                        ->where('academic_details.institute_details_id', Auth::user()->institute_details_id)
                        ->join('class_details_institute_class_map', 'class_details_institute_class_map.id', 'academic_details.combinations_pivot_id')
                        ->join('institute_class_maps', 'institute_class_maps.id', 'class_details_institute_class_map.institute_class_map_id')
                        ->join('class_details', 'class_details.id', 'class_details_institute_class_map.class_details_id')
                        ->join('core_subcategories as forgroup', 'forgroup.id', 'class_details.group_id')
                        ->join('core_subcategories as forshift', 'forshift.id', 'class_details.shift_id')
                        ->join('core_subcategories as forsection', 'forsection.id', 'class_details.section_id')
                        ->where('academic_details.student_id', $request->student_id)
                        ->where('academic_details.academic_year', $request->academic_year_id)
                        ->first();
        $payments = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                            ->where('student_id', $request->student_id)
                            ->where('academic_year_id', $request->academic_year_id)
                            ->get();

        $reports = [];
        foreach ($payments as $payment) {

            // Decode the JSON in the pay_details column
            $payDetails = json_decode($payment->pay_details, true);
            $detail = collect($payDetails)->firstWhere('invoice', $request->invoice);

                    // Extract additional details for the report
                if ($detail) {
                    $additionalDetails = [
                        'fee_head' => $payment->feeHead->name,
                        'fee_subhead' => $payment->feeSubead->name,
                        'payable' => $payment->payable,
                        'waiver_amount' => $payment->waiver_amount,
                        'fine' => $payment->fine
                    ];
        
                    // Combine all details into the final report
                    $report = array_merge([
                        'invoice' => $detail['invoice'],
                        'total_amount' => $detail['total'],
                        'paid_amount' => $detail['paid'],
                        'due_amount' => $detail['due'],
                        'payment_date' => $detail['payment_date'],
                        'payment_type' => $detail['payment_type'],
                    ], $additionalDetails);

                    $reports[] = $report;
        
                }
            // }
        
        }

        return response()->json([
            'instituteDetails' => $instituteDetails,
            'studentDetail' => new StudentDetailsShowResource($studentDetail),
            'reports' => $reports,
            'hr_name' => $hrDetails->hr_detail->hr_name ?? null
        ]);

    }
   
}
