<?php

namespace App\Http\Controllers\institutePortal\students;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AcademicDetail;
use App\Models\InstituteClassMap;
use App\Jobs\MigrationPushbackJob;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Jobs\WithoutMeritPromotionJob;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CoreInstituteResource;

class PromotionController extends Controller
{
    public function index()
    {
        $academicYears = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 1);
            })
            ->with('coresubcategories.corecategory')
            ->get();
        $academicSession = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 2);
            })
            ->with('coresubcategories.corecategory')
            ->get();
        $instituteClassMap = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)->with('classDetails.shifts', 'classDetails.sections', 'classDetails.groups')->get();

        return response()->json([
            'instituteClassMap' => $instituteClassMap,
            'academicYears' => CoreInstituteResource::collection($academicYears),
            'academicSession' => CoreInstituteResource::collection($academicSession),
        ]);
    }

    public function withoutMeritSearch(Request $request)
    {
        $rules = [
            'institute_class_map_id' => 'required',
            'academic_year_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $pivotId = [];
        $instituteClassMaps = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('id', $request->institute_class_map_id)
            ->with('classDetails')
            ->first();

        foreach ($instituteClassMaps->classDetails as $classDetail) {
            $pivotId[] = $classDetail->pivot->id;
        }

        $students = AcademicDetail::select(
            'academic_details.*',
            'class_details.group_id',
            'core_subcategories.core_subcategory_name as group'
        )
            ->join('class_details_institute_class_map', 'academic_details.combinations_pivot_id', '=', 'class_details_institute_class_map.id')
            ->join('class_details', 'class_details_institute_class_map.class_details_id', '=', 'class_details.id')
            ->join('core_subcategories', 'class_details.group_id', '=', 'core_subcategories.id')
            ->where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year', $request->academic_year_id)
            ->whereIn('combinations_pivot_id', $pivotId)
            ->with('studentDetails')
            ->get();


        return $students;
    }

    public function withoutMeritStore(Request $request)
    {
        $rules = [
            'present_combinations_pivot_id' => 'required',
            'present_academic_year_id' => 'required',
            'combinations_pivot_id' => 'required',
            'academic_year_id' => 'required',
            'academic_session_id' => 'required',
            'student_id' => 'required|array|min:1',
            'new_roll' => 'required|array|min:1',
            'class_id' => 'required',
            'group_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        foreach ($request->student_id as $key => $student_id) {
            $academicDetails = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('combinations_pivot_id', $request->present_combinations_pivot_id)
                ->where('academic_year', $request->present_academic_year_id)
                ->where('student_id', $student_id)
                ->first();
            $studentPromotion = [
                'institute_details_id' => Auth::user()->institute_details_id,
                'academic_year_id' => $request->academic_year_id,
                'academic_session_id' => $request->academic_session_id,
                'combinations_pivot_id' => $request->combinations_pivot_id,
                'new_roll' => $request->new_roll[$key],
                'student_id' => $academicDetails->student_id,
                'admission_date' => $academicDetails->admission_date,
                'category' => $academicDetails->category,
                'custom_student_id' => $academicDetails->custom_student_id,
                'class_id' => $request->class_id,
                'group_id' => $request->group_id
            ];

            dispatch(new WithoutMeritPromotionJob($studentPromotion));
        }
        return response()->json(['status' => 'success', 'message' => 'Students Promoted Successfully'], Response::HTTP_CREATED);
    }


    //pushback
    public function puchbackStore(Request $request)
    {
        $rules = [
            'student_id' => 'required|array|min:1',
            'academic_year_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        foreach ($request->student_id as $key => $student_id) {
            $academicDetails = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year', $request->present_academic_year_id)
                ->where('student_id', $student_id)
                ->first();

            if (empty($academicDetails)) {
                return response()->json(['status' => 'error', 'message' => 'Data Not Found.'], Response::HTTP_CREATED);
            }
            $studentPushback = [
                'institute_details_id' => Auth::user()->institute_details_id,
                'academic_year_id' => $request->academic_year_id,
                'delete_academic_year_id' => $academicDetails->academic_year_id,
                'delete_id' => $academicDetails->id,
                'student_id' => $academicDetails->student_id,
            ];

            dispatch(new MigrationPushbackJob($studentPushback));
        }
        return response()->json(['status' => 'success', 'message' => 'Migration Pushback Successfully'], Response::HTTP_CREATED);
    }
}
