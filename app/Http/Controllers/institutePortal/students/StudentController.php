<?php

namespace App\Http\Controllers\institutePortal\students;

use App\Models\Student;
use App\Models\PayApply;
use App\Models\ClassDetails;
use Illuminate\Http\Request;
use App\Models\StudentDetail;
use Illuminate\Http\Response;
use App\Models\AcademicDetail;
use App\Models\GuardianDetail;
use App\Traits\AmountSetTraits;
use App\Jobs\BasciInfoUpdateJob;
use App\Models\InstituteClassMap;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Http\Resources\StudentListResource;
use App\Http\Resources\CoreInstituteResource;
use Savannabits\PrimevueDatatables\PrimevueDatatables;

class StudentController extends Controller
{
    use AmountSetTraits;
    /**
     * Display a listing of the resource.
     */
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
            'academicSession' => CoreInstituteResource::collection($academicSession),
            'category' => CoreInstituteResource::collection($category),
        ]);
    }




    public function studentList(Request $request)
    {
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
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        $classDetails = ClassDetails::where('shift_id', $request->shift_id)
            ->where('section_id', $request->section_id)->pluck('id')->toArray();
        $pivotIds = DB::table('class_details_institute_class_map')
            ->where('institute_class_map_id', $request->institute_class_map_id)
            ->whereIn('class_details_id', $classDetails)
            ->pluck('id')->toArray(); // Use pluck to get a single value;

        $list = PrimevueDatatables::of(
            AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                ->whereIn('combinations_pivot_id', $pivotIds)
                ->where('academic_year', $request->academic_year_id)
                ->whereHas('student', function ($query) {
                    $query->where('status', 'active');
                })
                ->with('combination', 'student', 'studentDetails', 'guardianDetails', 'categories.coresubcategories', 'academicsession.coresubcategories', 'academicyear.coresubcategories')
        )->make();

        return response()->json([
            'success' => true,
            'students' => $list,
        ]);
    }


    // student active/inactive
    public function toggleStatus(Request $request)
    {
        $rules = [
            'student_id' => 'required|array|min:1'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        foreach ($request->student_id as $studentId) {
            $student = Student::find($studentId);

            // Toggle the status
            $student->status = ($student->status == 'active') ? 'inactive' : 'active';
            $student->save();
        }

        return response()->json(['status' => 'success', 'message' => 'Student status change successfully'], Response::HTTP_CREATED);
    }

    //student list report
    public function studentInactive(Request $request)
    {
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
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        $classDetails = ClassDetails::where('shift_id', $request->shift_id)
            ->where('section_id', $request->section_id)->pluck('id')->toArray();

        $pivotIds = DB::table('class_details_institute_class_map')
            ->where('institute_class_map_id', $request->institute_class_map_id)
            ->whereIn('class_details_id', $classDetails)
            ->pluck('id')->toArray(); // Use pluck to get a single value;

        $list = PrimevueDatatables::of(
            AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                ->whereIn('combinations_pivot_id', $pivotIds)
                ->where('academic_year', $request->academic_year_id)
                ->whereHas('student', function ($query) {
                    $query->where('status', 'inactive');
                })
                ->with('student', 'studentDetails', 'guardianDetails', 'categories.coresubcategories', 'academicsession.coresubcategories', 'academicyear.coresubcategories')
        )->make();

        return response()->json([
            'success' => true,
            'students' => $list,
        ]);
    }

    //Basic info
    public function basicInfoSearch(Request $request)
    {
        $rules = [
            'institute_class_map_id' => 'required',
            'section_id' => 'required',
            'shift_id' => 'required',
            'academic_year_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        $classDetails = ClassDetails::where('shift_id', $request->shift_id)
            ->where('section_id', $request->section_id)->pluck('id')->toArray();

        $pivotIds = DB::table('class_details_institute_class_map')
            ->where('institute_class_map_id', $request->institute_class_map_id)
            ->whereIn('class_details_id', $classDetails)
            ->pluck('id')->toArray(); // Use pluck to get a single value;

        $classForGroup = DB::table('class_details_institute_class_map')
            ->where('institute_class_map_id', $request->institute_class_map_id)
            ->whereIn('class_details_id', $classDetails)
            ->pluck('class_details_id')->toArray();
        $groupId = ClassDetails::whereIn('id', $classForGroup)->get();

        $group_array = [];
        foreach ($groupId as $group) {
            $group_array[] = [
                'id' => $group->id,
                'group_id' => $group->group_id,
                'group_name' => $group->groups->core_subcategory_name,
            ];
        }
        $list = PrimevueDatatables::of(
            AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                ->whereIn('combinations_pivot_id', $pivotIds)
                ->where('academic_year', $request->academic_year_id)
                ->whereHas('student', function ($query) {
                    $query->where('status', 'active');
                })
                ->with('studentDetails', 'guardianDetails')
        )->make();

        return response()->json([
            'success' => true,
            'groups' =>  $group_array,
            'students' => StudentListResource::collection($list),
        ]);
    }

    public function basicInfoStore(Request $request)
    {
        if (empty($request->file)) {
            $rules = [
                'student_id' => 'required',
                'academic_year' => 'required',
                'file' => 'nullable'
            ];

            // Validate the request data
            $validator = Validator::make($request->all(), $rules);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }
            $logMessages = [];
            // foreach ($request->student_id as $key => $studentId) {
            try {
                // $basicUpdate = [
                //     'institute_details_id' => Auth::user()->institute_details_id,
                //     'academic_year' => $request->academic_year,
                //     'student_id' => $studentId,
                //     'admission_date' => $request->admission_date[$key],
                //     'class_roll' => $request->class_roll[$key],
                //     'custom_student_id' => $request->custom_student_id[$key],

                //     'student_name' => $request->student_name[$key],
                //     'student_gender' => $request->student_gender[$key],
                //     'student_religion' => $request->student_religion[$key],
                //     'student_dob' => $request->student_dob[$key] ?? null,
                //     'blood_group' => $request->blood_group[$key] ?? null,

                //     'father_name' => $request->father_name[$key],
                //     'mother_name' => $request->mother_name[$key],
                //     'father_mobile' => $request->father_mobile[$key],
                // ];
                $basicUpdate = [
                    'institute_details_id' => Auth::user()->institute_details_id,
                    'academic_year' => $request->academic_year,
                    'student_id' => $request->student_id,
                    'admission_date' => $request->admission_date,
                    'class_roll' => $request->class_roll,
                    'custom_student_id' => $request->custom_student_id,

                    'student_name' => $request->student_name,
                    'student_gender' => $request->student_gender,
                    'student_religion' => $request->student_religion,
                    'student_dob' => $request->student_dob ?? null,
                    'blood_group' => $request->blood_group ?? null,

                    'father_name' => $request->father_name,
                    'mother_name' => $request->mother_name,
                    'father_mobile' => $request->father_mobile,
                ];

                dispatch(new BasciInfoUpdateJob($basicUpdate));
                $logMessages[] = "Student with ID {$request->student_id} updated successfully.";
            } catch (\Exception $e) {
                // Log the error
                $logMessages[] = "Failed to update student with ID {$request->student_id}. Error: {$e->getMessage()}";
            }
            // }
            // Include log messages in the response
            $response = [
                'status' => 'success', // Consider updating based on the actual logic
                'message' => 'Record update completed',
                'log_messages' => $logMessages,
            ];
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully update.',
            ]);
        } else {
            $rules = [
                'academic_year' => 'required',
                'file' => 'required'
            ];

            // Validate the request data
            $validator = Validator::make($request->all(), $rules);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }

            $data = Excel::toArray([], $request->file);
            foreach ($data[0] as $key => $row) {
                // Skip the header row
                if ($key === 0) {
                    continue;
                }
                $institue_id = $row[0];
                $instituteId = Auth::user()->institute_detail->institute_id;
                if ($institue_id != $instituteId) {
                    $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Wrong Institute File.']);
                    return response()->json([
                        'errors' => $formattedErrors,
                        'payload' => null,
                    ], 500);
                }

                $studentId = $row[4];

                // Find the column index for each field dynamically based on the header row
                $headerRow = $data[0][0]; // Assuming the header row is the first row
                $admissionDateIndex = array_search('Admission Date', $headerRow);
                $classRollIndex = array_search('Roll', $headerRow);
                $studentNameIndex = array_search('Student Name', $headerRow);
                $genderIndex = array_search('Gender', $headerRow);
                $religionIndex = array_search('Religion', $headerRow);
                $dobIndex = array_search('Date of Birth', $headerRow);
                $bloodGroupIndex = array_search('Blood Group', $headerRow);
                $fatherNameIndex = array_search('Father Name', $headerRow);
                $motherNameIndex = array_search('Mother Name', $headerRow);
                $fatherMobileIndex = array_search('Guardian Mobile', $headerRow);

                // Get the values from the current row based on the column indexes
                $admissionDate = isset($admissionDateIndex) && $admissionDateIndex !== false ? $row[$admissionDateIndex] : null;
                $classRoll = isset($classRollIndex) && $classRollIndex !== false ? $row[$classRollIndex] : null;
                $studentName = isset($studentNameIndex) && $studentNameIndex !== false ? $row[$studentNameIndex] : null;
                $gender = isset($genderIndex) && $genderIndex !== false ? $row[$genderIndex] : null;
                $religion = isset($religionIndex) && $religionIndex !== false ? $row[$religionIndex] : null;
                $dob = isset($dobIndex) && $dobIndex !== false ? $row[$dobIndex] : null;
                $bloodGroup = isset($bloodGroupIndex) && $bloodGroupIndex !== false ? $row[$bloodGroupIndex] : null;
                $fatherName = isset($fatherNameIndex) && $fatherNameIndex !== false ? $row[$fatherNameIndex] : null;
                $motherName = isset($motherNameIndex) && $motherNameIndex !== false ? $row[$motherNameIndex] : null;
                $fatherMobile = isset($fatherMobileIndex) && $fatherMobileIndex !== false ? $row[$fatherMobileIndex] : null;

                // Update the database based on the Student ID
                $academicDetail = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('custom_student_id', $studentId)
                    ->where('academic_year', $request->academic_year)
                    ->first();
                if ($academicDetail) {
                    if (!empty($admissionDate)) {
                        $academicDetail->admission_date = $admissionDate;
                    }
                    if (!empty($classRoll)) {
                        $academicDetail->class_roll = $classRoll;
                    }
                    $academicDetail->save();
                }

                $guardianDetail = GuardianDetail::where('student_id', $academicDetail->student_id)->first();
                if ($guardianDetail) {
                    if (!empty($fatherName)) {
                        $guardianDetail->father_name = $fatherName;
                    }
                    if (!empty($motherName)) {
                        $guardianDetail->mother_name = $motherName;
                    }
                    if (!empty($fatherMobile)) {
                        $guardianDetail->father_mobile = $fatherMobile;
                    }
                    $guardianDetail->save();
                }
                $student = StudentDetail::where('student_id', $academicDetail->student_id)->first();
                if ($student) {
                    if (!empty($studentName)) {
                        $student->student_name = $studentName;
                    }
                    if (!empty($gender)) {
                        $student->student_gender = $gender;
                    }
                    if (!empty($religion)) {
                        $student->student_religion = $religion;
                    }
                    if (!empty($dob)) {
                        $student->student_dob = $dob;
                    }
                    if (!empty($bloodGroup)) {
                        $student->blood_group = $bloodGroup;
                    }
                    $student->save();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully update.',
            ]);
        }
    }


    public function excelGenerate(Request $request)
    {
        $rules = [
            'student_id' => 'required|array|min:1',
            'academic_year_id' => 'required',
            'field_name' => 'required|array|min:1',
        ];

        // Column name mappings
        $columnMappings = [
            'admission_date' => 'Admission Date',
            'class_roll' => 'Roll',
            'student_name' => 'Student Name',
            'student_gender' => 'Gender',
            'student_religion' => 'Religion',
            'student_dob' => 'Date of Birth',
            'blood_group' => 'Blood Group',
            'father_name' => 'Father Name',
            'mother_name' => 'Mother Name',
            'father_mobile' => 'Guardian Mobile',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Initialize PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add Institute ID as the first column
        $instituteId = Auth::user()->institute_detail->institute_id;

        // Add column names as header row
        $columnNames = ['Institute ID', 'Class-Shift-Section', 'Group', 'Academic Year', 'Student ID'];
        foreach ($request->field_name as $fieldName) {
            $columnNames[] = $columnMappings[$fieldName];
        }
        $sheet->fromArray([$columnNames], null, 'A1');

        // Populate spreadsheet with data
        $rowData = [];
        foreach ($request->student_id as $studentId) {
            $academicDetails = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('student_id', $studentId)->first();

            $com = DB::table('class_details_institute_class_map')->where('id', $academicDetails->combinations_pivot_id)->first();
            $class_details = ClassDetails::find($com->class_details_id);

            $institute_class_map = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('id', $com->institute_class_map_id)->first();

            $class_name = ucfirst($institute_class_map->class_name);
            $group_name = ucfirst($class_details->groups->core_subcategory_name);
            $shift_name = ucfirst($class_details->shifts->core_subcategory_name);
            $section_name = ucfirst($class_details->sections->core_subcategory_name);
            $academic_year = $academicDetails->academicyear->coresubcategories->core_subcategory_name;
            $cls = $class_name . '-' . $shift_name . '-' . $section_name;
            $cus_std_id = $academicDetails->custom_student_id;
            $studentData = [$instituteId, $cls, $group_name, $academic_year, $cus_std_id]; // Set Institute ID as the first column data
            foreach ($request->field_name as $fieldName) {
                switch ($fieldName) {
                    case 'student_gender':
                        // For student gender, create a dropdown with selected value first
                        $genderOptions = ['Female', 'Male', 'Other']; // Assuming Female should be the default option
                        $studentDetails = StudentDetail::where('student_id', $studentId)->first();
                        $selectedGender = $studentDetails->student_gender;
                        $dropdownOptions = array_diff($genderOptions, [$selectedGender]);
                        array_unshift($dropdownOptions, $selectedGender);
                        $sheet->setCellValueByColumnAndRow(count($studentData) + 1, count($rowData) + 2, $selectedGender);
                        $validation = $sheet->getCellByColumnAndRow(count($studentData) + 1, count($rowData) + 2)->getDataValidation();
                        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                        $validation->setShowInputMessage(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setShowDropDown(true);
                        $validation->setErrorTitle('Input error');
                        $validation->setError('Value is not in list');
                        $validation->setPromptTitle('Pick from list');
                        $validation->setPrompt('Please pick a value from the drop-down list');
                        $validation->setFormula1('"' . implode(',', $dropdownOptions) . '"');
                        $studentData[] = $selectedGender;
                        break;
                    case 'student_religion':
                        $religionOptions = ['Islam', 'Hinduism', 'Buddhism', 'Christianity', 'Other'];
                        $studentDetails = StudentDetail::where('student_id', $studentId)->first();
                        $selectedReligion = $studentDetails->student_religion;
                        $redropdownOptions = array_diff($religionOptions, [$selectedReligion]);
                        array_unshift($redropdownOptions, $selectedReligion);
                        $sheet->setCellValueByColumnAndRow(count($studentData) + 1, count($rowData) + 2, $selectedReligion);
                        $validation = $sheet->getCellByColumnAndRow(count($studentData) + 1, count($rowData) + 2)->getDataValidation();
                        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                        $validation->setShowInputMessage(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setShowDropDown(true);
                        $validation->setErrorTitle('Input error');
                        $validation->setError('Value is not in list');
                        $validation->setPromptTitle('Pick from list');
                        $validation->setPrompt('Please pick a value from the drop-down list');
                        $validation->setFormula1('"' . implode(',', $redropdownOptions) . '"');
                        $studentData[] = $selectedReligion;
                        break;
                    case 'student_name':
                    case 'student_dob':
                    case 'blood_group':
                        $studentDetails = StudentDetail::where('student_id', $studentId)->first();
                        $studentData[] = $studentDetails->$fieldName;
                        break;
                    case 'admission_date':
                    case 'class_roll':
                        $academicDetails = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                            ->where('student_id', $studentId)->first();
                        $studentData[] = $academicDetails->$fieldName;
                        break;
                    case 'father_name':
                    case 'mother_name':
                    case 'father_mobile':
                        $guardianDetails = GuardianDetail::where('student_id', $studentId)->first();
                        $studentData[] = $guardianDetails->$fieldName;
                        break;
                }
            }
            $rowData[] = $studentData;
        }

        // Add data to the spreadsheet
        $sheet->fromArray($rowData, null, 'A2');

        // Generate Excel file
        $fileName = $instituteId . '_' . $cls . '_Student_update' . '.xlsx';
        $filePath = $instituteId . '/exports/' . $fileName;
        if (!Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->makeDirectory(dirname($filePath), 0777, true, true);
        }

        // Create a PHPExcel Writer instance
        $writer = new Xlsx($spreadsheet);

        // Store the Excel file in storage
        $writer->save(storage_path('app/public/' . $filePath));
        $url = Storage::url($filePath);

        return response()->json([
            'status' => 'success',
            'url' => $url,
        ]);
    }

    public function classInfoStore(Request $request)
    {
        $rules = [
            'student_id' => 'required|array|min:1',
            'academic_year_id' => 'required',
            'present_combination_id' => 'required',
            'student_category_id' => 'nullable',
            'group_id' => 'nullable',
            'combination_id' => 'nullable',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($request->student_category_id)) {
            foreach ($request->student_id as $student_id) {
                $academic_detail = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('combinations_pivot_id', $request->present_combination_id)
                    ->where('academic_year', $request->academic_year_id)
                    ->where('student_id', $student_id)
                    ->first();
                if ($academic_detail) {
                    $academic_detail->update([
                        'category' => $request->student_category_id
                    ]);
                }
                $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('combinations_pivot_id', $request->present_combination_id)
                    ->where('academic_year_id', $request->academic_year_id)
                    ->where('student_id', $student_id)
                    ->where('payment_state', 'UNPAID')
                    ->get();
                foreach ($payapplies as $payapply) {
                    $payapply->update([
                        'payment_state' => 'OMITTED'
                    ]);
                }
                $this->updateSingleStudentPayApplies($request, Auth::user()->institute_details_id, $request->present_combination_id, $student_id);
        
            }
        }
        if(!empty($request->group_id))
        {
            $present_pivot_id = DB::table('class_details_institute_class_map')->where('id', $request->present_combination_id)->first();

            $new_pivot_id = DB::table('class_details_institute_class_map')
                        ->where('institute_class_map_id', $present_pivot_id->institute_class_map_id)
                        ->where('class_details_id', $request->group_id)->first();

            foreach ($request->student_id as $student_id) {

                $academic_detail = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('combinations_pivot_id', $request->present_combination_id)
                    ->where('academic_year', $request->academic_year_id)
                    ->where('student_id', $student_id)
                    ->first();
                if ($academic_detail) {
                    $academic_detail->update([
                        'combinations_pivot_id' => $new_pivot_id
                    ]);
                }
                $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('combinations_pivot_id', $request->present_combination_id)
                    ->where('academic_year_id', $request->academic_year_id)
                    ->where('student_id', $student_id)
                    ->where('payment_state', 'UNPAID')
                    ->get();
                foreach ($payapplies as $payapply) {
                    $payapply->update([
                        'payment_state' => 'OMITTED'
                    ]);
                }

                $this->updateSingleStudentPayApplies($request, Auth::user()->institute_details_id, $new_pivot_id, $student_id);
        
            }
        }

        if(!empty($request->combination_id))
        {
            foreach ($request->student_id as $student_id) {

                $academic_detail = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('combinations_pivot_id', $request->present_combination_id)
                    ->where('academic_year', $request->academic_year_id)
                    ->where('student_id', $student_id)
                    ->first();
                if ($academic_detail) {
                    $academic_detail->update([
                        'combinations_pivot_id' => $request->combination_id
                    ]);
                }
                $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('combinations_pivot_id', $request->present_combination_id)
                    ->where('academic_year_id', $request->academic_year_id)
                    ->where('student_id', $student_id)
                    ->where('payment_state', 'UNPAID')
                    ->get();
                foreach ($payapplies as $payapply) {
                    $payapply->update([
                        'payment_state' => 'OMITTED'
                    ]);
                }

                $this->updateSingleStudentPayApplies($request, Auth::user()->institute_details_id, $request->combination_id, $student_id);
        
            }
        }
    }
}

        //academic_details table
        // admission_date = Admission Date
        // class_roll = Roll
    
        //student_details table
        // student_name = Student Name
        // student_gender = Gender
        // student_religion = Religion
        // student_dob = Date of Birth
        // blood_group = Blood Group
    
        //guardian_details table
        // father_name = Father Name
        // mother_name = Mother Name
        // father_mobile = Gurdian Mobile
