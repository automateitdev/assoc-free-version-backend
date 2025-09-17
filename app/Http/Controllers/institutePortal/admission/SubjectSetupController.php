<?php

namespace App\Http\Controllers\institutePortal\admission;

use App\Helpers\ApiResponseHelper;
use App\Models\SubjectList;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Models\AdmissionClassSetup;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\AdmissionSubjectSetup;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\SubjectListResource;
use App\Http\Resources\AdmissionClassSetupResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class SubjectSetupController extends Controller
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
        $subjectList = SubjectList::all();
        $admissionClassSetup = AdmissionClassSetup::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $admissionSubjectSetup = AdmissionSubjectSetup::where('institute_details_id', Auth::user()->institute_details_id)->get();

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
            'admissionSubjectSetup' => $admissionSubjectSetup,
            'subjectList' => SubjectListResource::collection($subjectList),
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
            'group_id' => 'required',
            'compulsory' => 'required|array|min:1',
            'group' => 'required|array|min:1',
            'class_name' => 'required',
            'group_name' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        DB::beginTransaction();

        try {
            $check = AdmissionSubjectSetup::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('class_id', $request->class_id)
                ->where('group_id', $request->group_id)
                ->first();
            if ($check) {
                return response()->json([$check, 'status' => 'success', 'message' => 'Record Already Exists.'], Response::HTTP_CREATED);
            }
            $input = new AdmissionSubjectSetup();
            $input->institute_details_id = Auth::user()->institute_details_id;
            $input->class_id = $request->class_id;
            $input->group_id = $request->group_id;
            $input->class_name = $request->class_name;
            $input->group_name = $request->group_name;
            $input->compulsory = json_encode($request->compulsory);
            $input->group = json_encode($request->group);
            $input->save();

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
        } catch (QueryException $e) {
            DB::rollBack();

            Log::error("Failed to assign subject: $e");
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Duplicate Entry']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], Response::HTTP_CONFLICT);
            }

            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['An unexpected error occured!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['An unexpected error occured!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($class_id)
    {
        $admissionClassSetups = AdmissionClassSetup::where('class_id', $class_id)->get();

        $result = $admissionClassSetups->groupBy('class_id')->map(function ($items, $key) {
            return [
                'class_id' => $key,
                'class_name' => $items->first()->class_name,
                'groups' => $items->map(function ($item) {
                    return [
                        'group_id' => $item->group_id,
                        'group_name' => $item->group_name
                    ];
                })->values()
            ];
        })->values();

        return response()->json(['status' => 'success', 'class_data' => $result], Response::HTTP_OK);
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
    public function update(Request $request, $id)
    {
        $rules = [
            'class_id' => 'required',
            'group_id' => 'required',
            'compulsory' => 'required|array|min:1',
            'group' => 'required|array|min:1',
            // 'optional' => 'required|array|min:1',
            'class_name' => 'required',
            'group_name' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        DB::beginTransaction();

        try {
            $admissionSubjectSetup = AdmissionSubjectSetup::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('id', $id)
                ->firstOrFail();

            $admissionSubjectSetup->class_id = $request->class_id;
            $admissionSubjectSetup->group_id = $request->group_id;
            $admissionSubjectSetup->class_name = $request->class_name;
            $admissionSubjectSetup->group_name = $request->group_name;
            $admissionSubjectSetup->compulsory = json_encode($request->compulsory);
            $admissionSubjectSetup->group = json_encode($request->group);
            $admissionSubjectSetup->save();

            DB::commit();

            return response()->json([$admissionSubjectSetup, 'status' => 'success', 'message' => 'Record updated successfully'], Response::HTTP_OK);
        } catch (QueryException $e) {
            DB::rollBack();

            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Duplicate entry']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 409);
            }

            return response()->json(['status' => 'error', 'message' => 'Database error'], Response::HTTP_INTERNAL_SERVER_ERROR);

            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Internal Server Error!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Subject-setup not found']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function classInfo($class_id)
    {
        $admissionClassSetups = AdmissionClassSetup::where('class_id', $class_id)->get();

        // $result = $admissionClassSetups->groupBy('class_id')->map(function ($items, $key) {
        //     return [
        //         'class_id' => $key,
        //         'class_name' => $items->first()->class_name,
        //         'groups' => $items->map(function ($item) {
        //             return [
        //                 'group_id' => $item->group_id,
        //                 'group_name' => $item->group_name
        //             ];
        //         })->values()
        //     ];
        // })->values();

        return response()->json(['status' => 'success', 'class_info' => $admissionClassSetups], Response::HTTP_OK);
    }
}
