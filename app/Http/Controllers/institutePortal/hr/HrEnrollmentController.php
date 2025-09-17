<?php

namespace App\Http\Controllers\institutePortal\hr;

use App\Models\HrInfo;
use App\Models\HrBasicInfo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\HrClassification;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HrEnrollmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
            'hr_list' => 'required|array',
            'hr_list.*.hr_id' => 'required|integer',
            'hr_list.*.hr_name' => 'required|string',
            'hr_list.*.hr_gender' => 'required|in:Male,Female,Common',
            'hr_list.*.hr_religion' => 'required|in:Islam,Hinduism,Buddhism,Christianity',
            'hr_list.*.mobile' => 'required|string',
            'hr_list.*.description' => 'nullable|string',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json(
                [
                    'errors' => $formattedErrors,
                    'payload' => null,
                ],
                422
            );
        }

        foreach ($request['hr_list'] as $hrData) {
            // Step 1: Store in HrInfo
            $hrInfo = HrInfo::create([
                'hr_name' => $hrData['hr_name'],
                'department' => collect($hrData['type'])->firstWhere('label', 'department')['name'] ?? null,
                'designation' => collect($hrData['type'])->firstWhere('label', 'designation')['name'] ?? null,
                'category' => collect($hrData['type'])->firstWhere('label', 'category')['name'] ?? null,
                'job_type' => collect($hrData['type'])->firstWhere('label', 'job_type')['name'] ?? null,
                'duty_shift' => collect($hrData['type'])->firstWhere('label', 'duty_shift')['name'] ?? null,
            ]);

            // Step 2: Store in HrBasicInfo
            HrBasicInfo::create([
                'hr_info_id' => $hrInfo->id,
                'institute_details_id' => Auth::user()->institute_details_id,
                'custom_hr_id' => $hrData['hr_id'],
                'name' => $hrData['hr_name'],
                'gender' => $hrData['hr_gender'],
                'religion' => $hrData['hr_religion'],
                'mobile_no' => $hrData['mobile'],
                'description' => $hrData['description'],
            ]);

            // Step 3: Store in HrClassification
            foreach ($hrData['type'] as $type) {
                HrClassification::create([
                    'institute_details_id' => Auth::user()->institute_details_id,
                    'name' => $type['name'],
                    'type' => $type['label'],
                    'description' => $hrData['description'],
                ]);
            }
        }
        return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
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
