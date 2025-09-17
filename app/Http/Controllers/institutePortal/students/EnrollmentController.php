<?php

namespace App\Http\Controllers\institutePortal\students;

use Illuminate\Http\Request;
use App\Jobs\ExcelEnrollment;
use Illuminate\Http\Response;
use App\Jobs\StudentEnrolment;
use App\Models\AcademicDetail;
use App\Models\InstituteClassMap;
use App\Helpers\ApiResponseHelper;
use App\Models\CoreInstituteConfig;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Http\Resources\CoreInstituteResource;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;


class EnrollmentController extends Controller
{

    /** @OA\Get(
     *     path="/api/student-enrollment",
     *     summary="Get a listing of student enrollments",
     *     tags={"Students"},
     *     @OA\Response(response=200, description="Successful operation"),
     * )
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


    /**
     * Store a newly created resource in storage.
     
     * @OA\Post(
     *     path="/api/student-enrollment/store",
     *     summary="Store a new student enrollment",
     *     tags={"Students"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="student_name", type="string"),
     *             @OA\Property(property="student_gender", type="string"),
     *             @OA\Property(property="student_religion", type="string"),
     *             @OA\Property(property="class_roll", type="integer", format="array"),
     *             @OA\Property(property="category", type="integer"),
     *             @OA\Property(property="academic_year", type="integer"),
     *             @OA\Property(property="academic_session", type="integer"),
     *             @OA\Property(property="assign_id", type="integer"),
     *             @OA\Property(property="father_name", type="string"),
     *             @OA\Property(property="mother_name", type="string"),
     *             @OA\Property(property="father_mobile", type="integer"),
     *         )
     *     ),
     *     @OA\Response(response=201, description="Record saved successfully"),
     *     @OA\Response(response=400, description="Bad request, validation failed"),
     * )
     */
    public function store(Request $request)
    {
        $rules = [
            'student_name' => 'required|array|min:1',
            'student_gender' => 'required|array|min:1',
            'student_religion' => 'required|array|min:1',
            'custom_student_id' => 'required|array|min:1',
            'class_roll' => 'required|array|min:1',
            'category' => 'required',
            'academic_year' => 'required',
            'academic_session' => 'required',
            'assign_id' => 'required',
            'father_name' => 'required|array|min:1',
            'mother_name' => 'required|array|min:1',
            'father_mobile' => 'required|array|min:1',
            'class_id' => 'required',
            'group_id' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validation passed, proceed to check for duplicates
        $student_name = $request->input('student_name');
        $student_gender = $request->input('student_gender');
        $student_religion = $request->input('student_religion');
        $custom_student_id = $request->input('custom_student_id');
        $class_roll = $request->input('class_roll');
        $category = $request->input('category');
        $academic_year = $request->input('academic_year');
        $academic_session = $request->input('academic_session');
        $father_name = $request->input('father_name');
        $mother_name = $request->input('mother_name');
        $father_mobile = $request->input('father_mobile');

        foreach ($custom_student_id as $key => $data) {

            $checkCustomId = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year', $academic_year)
                ->where('custom_student_id', $data)
                ->first();

            if ($checkCustomId) {
                Log::warning("Custom student ID '{$data}' already exists for institute " . Auth::user()->institute_detail->institute_id);

                continue; // Skip updating the current student and move to the next iteration
            }

            $studentData = [
                'institute_details_id' => Auth::user()->institute_details_id,
                'assign_id' => $request->assign_id,
                'class_id' => $request->class_id,
                'group_id' => $request->group_id,
                'academic_year' => $academic_year,
                'academic_session' => $academic_session,
                'category' => $category,
                'student_name' => $student_name[$key],
                'student_gender' => $student_gender[$key],
                'student_religion' => $student_religion[$key],
                'custom_student_id' => $data,
                'class_roll' => $class_roll[$key],
                'father_name' => $father_name[$key],
                'mother_name' => $mother_name[$key],
                'father_mobile' => $father_mobile[$key]
            ];
            dispatch(new StudentEnrolment($studentData));
        }
        return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
    }
    /**
     * Store a newly created resource in storage.
     
     * @OA\Post(
     *     path="/api/student-enrollment/excel-store",
     *     summary="Store a new student enrollment using Excel file",
     *     tags={"Students"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="category", type="integer", example="1"),
     *             @OA\Property(property="academic_year", type="integer", example="2"),
     *             @OA\Property(property="academic_session", type="integer", example="5"),
     *             @OA\Property(property="assign_id", type="integer", example="12"),
     *             @OA\Property(property="file", type="file", format="xlsx", example="student.xlsx"),
     *         )
     *     ),
     *     @OA\Response(response=201, description="Record saved successfully"),
     *     @OA\Response(response=400, description="Bad request, validation failed"),
     * )
     */
    public function excelStore(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'academic_year' => 'required',
            'academic_session' => 'required',
            'category' => 'required',
            'assign_id' => 'required',
            'file' => ['required', 'file', 'mimes:xlsx'],
            'class_id' => 'required',
            'group_id' => 'required'
        ]);


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

        try {
            $filePath = $request->file('file')->store('excel');

            $studentData = [
                'institute_details_id' => Auth::user()->institute_details_id,
                'academic_year' => $request->academic_year,
                'academic_session' => $request->academic_session,
                'category' => $request->category,
                'assign_id' => $request->assign_id,
                'file' => $filePath,
                'class_id' => $request->class_id,
                'group_id' => $request->group_id
            ];
            dispatch(new ExcelEnrollment($studentData));
        } catch (\Exception $e) {
            Log::error("Excel Enrollment failed: $e");
            // return response()->json(['errors' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['errors' => $e->getMessage()]);
            return response()->json(
                [
                    'errors' => $formattedErrors,
                    'payload' => null,
                ],
                400
            );
        }

        return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
    }

    public function excelDwonload(Request $request)
    {
        // $rules = [
        //     'rows' => 'required',
        // ];

        // Validate the request data
        // $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        // if ($validator->fails()) {
        //     $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
        //     return response()->json(
        //         [
        //             'errors' => $formattedErrors,
        //             'payload' => null,
        //         ],
        //         422
        //     );
        // }
        try {

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Define your data here
            $instituteId = Auth::user()->institute_detail->institute_id;

            // Add dropdown options for Gender and Religion
            $sheet->setCellValue('E2', 'Gender');
            $sheet->setCellValue('F2', 'Religion');
            $genderOptions = ['Male', 'Female', 'Other'];
            $religionOptions = ['Islam', 'Hinduism', 'Buddhism', 'Christianity', 'Other'];
            $students = [
                [
                    'Institute ID', // Institute ID from Auth
                    'Student ID',
                    'Roll',
                    'Name',
                    'Gender',
                    'Religion',
                    'Father Name',
                    'Mother Name',
                    'Mobile No.'
                ],
                [
                    $instituteId,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ],
                // Add more student data as needed
            ];

            // Set data validation for Gender dropdown
            $genderValidation = $sheet->getCell('E3')->getDataValidation();
            $genderValidation->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(false)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setErrorTitle('Input error')
                ->setError('Value is not in list.')
                ->setPromptTitle('Pick from list')
                ->setPrompt('Please pick a value from the dropdown list')
                ->setFormula1('"' . implode(',', $genderOptions) . '"');

            // Set data validation for Religion dropdown
            $religionValidation = $sheet->getCell('F3')->getDataValidation();
            $religionValidation->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(false)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setErrorTitle('Input error')
                ->setError('Value is not in list.')
                ->setPromptTitle('Pick from list')
                ->setPrompt('Please pick a value from the dropdown list')
                ->setFormula1('"' . implode(',', $religionOptions) . '"');

            // Get the highest row index with data
            // Apply data validation to all rows for Gender and Religion columns
            // $highestRow = $request->rows;
            // for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
            //     $sheet->getCellByColumnAndRow(5, $rowIndex)->setDataValidation(clone $genderValidation); // Gender
            //     $sheet->getCellByColumnAndRow(6, $rowIndex)->setDataValidation(clone $religionValidation); // Religion
            // }


            // Apply data validation to all rows for Gender and Religion columns
            $highestRow = count($students) + 1; // +1 to account for the header row
            for ($rowIndex = 3; $rowIndex <= $highestRow; $rowIndex++) { // Start from row 3 to skip headers
                $sheet->getCellByColumnAndRow(
                    5,
                    $rowIndex
                )->setDataValidation(clone $genderValidation); // Gender
                $sheet->getCellByColumnAndRow(6, $rowIndex)->setDataValidation(clone $religionValidation); // Religion
            }

            // Add data to the spreadsheet
            foreach ($students as $rowIndex => $rowData) {
                foreach ($rowData as $columnIndex => $value) {
                    $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
                }
            }

            // Save the Excel file
            $fileName = $instituteId . '_students.xlsx';
            $filePath = $instituteId . '/exports/' . $fileName;
            if (!Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->makeDirectory(dirname($filePath), 0777, true, true);
            }
            $writer = new Xlsx($spreadsheet);
            $writer->save(storage_path('app/public/' . $filePath));

            // Get the download URL
            $url = Storage::url($filePath);

            return response()->json([
                'status' => 'success',
                'message' => 'Students exported successfully.',
                'download_url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error("Excel Enrollment failed: $e");
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['errors' => $e->getMessage()]);
            return response()->json(
                [
                    'errors' => $formattedErrors,
                    'payload' => null,
                ],
                400
            );
        }
    }
}
