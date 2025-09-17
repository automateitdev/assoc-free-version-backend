<?php

namespace App\Http\Controllers\institutePortal\admission;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\SslInfo;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Illuminate\Http\Response;
use App\Models\AdmissionConfig;
use App\Models\AdmissionSpgPay;
use App\Models\InstituteDetail;
use App\Models\AdmissionApplied;
use App\Models\AdmissionPayment;
use App\Helpers\ApiResponseHelper;
use App\Jobs\GenerateAdmissionPDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Http\Resources\AdmissionOpsResource;
use Savannabits\PrimevueDatatables\PrimevueDatatables;

class ReportController extends Controller
{
    public function appliedList(Request $request)
    {
        $rules = [
            'academic_year' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        // $admission_applied = AdmissionApplied::where('institute_details_id', Auth::user()->institute_details_id)->where('academic_year', $request->academic_year)->get();

        $list = PrimevueDatatables::of(
            AdmissionApplied::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year', $request->academic_year)
        )->make();

        return response()->json([
            'status' => 'success',
            'message' => 'Admission Applied Data',
            'admission_applied' => $list,
        ], Response::HTTP_OK);
    }
    public function appliedSuccessList(Request $request)
    {
        $rules = [
            'academic_year' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $admission_applied = AdmissionApplied::where('institute_details_id', Auth::user()->institute_details_id)->where('academic_year', $request->academic_year)->where('approval_status', 'Success')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Admission Applicant Success Data',
            'admission_applied' => $admission_applied,
        ], Response::HTTP_OK);
    }
    public function appliedPendingList(Request $request)
    {
        $rules = [
            'academic_year' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $admission_applied = AdmissionApplied::where('institute_details_id', Auth::user()->institute_details_id)->where('academic_year', $request->academic_year)->where('approval_status', 'pending')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Admission Applicant Pending Data',
            'admission_applied' => $admission_applied,
        ], Response::HTTP_OK);
    }

    public function subjectReport(Request $request)
    {
        $rules = [
            'academic_year' => 'required',
            'class' => 'required',
            'group' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $instituteId = Auth::user()->institute_detail->institute_id;

        // Fetch the necessary data
        $infos = AdmissionApplied::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year', $request->academic_year)
            ->where('class', trim($request->class))
            ->where('group', trim($request->group))
            ->where('approval_status', 'Success')
            ->get();

        // Create a new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $row = 1;

        // Prepare the data for export
        $groupedStudents = [];
        foreach ($infos as $info) {
            $subjectList = json_decode($info->subject, true); // Decode JSON as associative array
            if ($subjectList) {
                foreach ($subjectList as $subjects) {
                    foreach ($subjects as $subject) {
                        if (!isset($groupedStudents[$subject])) {
                            $groupedStudents[$subject] = [];
                        }
                        $groupedStudents[$subject][] = [
                            'unique_number_id' => $info->unique_number,
                            'name_english' => $info->student_name_english,
                            'group' => $info->group,
                            'class' => $info->class,
                            'roll' => $info->assigned_roll,
                        ];
                    }
                }
            }
        }

        // Iterate through the grouped students and create the Excel sheet
        foreach ($groupedStudents as $subject => $students) {
            // Only add the subject section if there are students
            if (count($students) > 0) {
                // Set subject name as a heading and merge the cells
                $subjectNameCell = 'A' . $row;
                $worksheet->mergeCells($subjectNameCell . ':' . 'E' . $row);
                $worksheet->setCellValue($subjectNameCell, $subject);
                $worksheet->getStyle($subjectNameCell)->getAlignment()->setHorizontal('center');
                $row++;

                // Add headers for the selected columns
                $worksheet->setCellValue('A' . $row, 'Unique Number ID');
                $worksheet->setCellValue('B' . $row, 'Name');
                $worksheet->setCellValue('C' . $row, 'Assigned Roll');
                $worksheet->setCellValue('D' . $row, 'Class');
                $worksheet->setCellValue('E' . $row, 'Group');
                $row++;

                // Add the student information
                foreach ($students as $student) {
                    $worksheet->setCellValue('A' . $row, $student['unique_number_id']);
                    $worksheet->setCellValue('B' . $row, $student['name_english']);
                    $worksheet->setCellValue('C' . $row, $student['roll']);
                    $worksheet->setCellValue('D' . $row, $student['class']);
                    $worksheet->setCellValue('E' . $row, $student['group']);
                    $row++;
                }

                // Add an empty row for separation between subjects
                $row++;
            }
        }

        // Create a new Excel writer
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        // Define the file name and path
        $fileName = $request->class . '_' . $request->group . '_subject_report.xlsx';
        $filePath = $instituteId . '/admission/' . $fileName;

        // Ensure the directory exists
        Storage::disk('public')->makeDirectory(dirname($filePath), 0777, true, true);

        // Store the Excel file in the storage directory
        $writer->save(storage_path('app/public/' . $filePath));

        // Generate the URL to access the stored file
        $fileUrl = Storage::url($filePath);

        // Return the URL in the response
        return response()->json([
            'status' => 'success',
            'message' => 'Subject report generated successfully.',
            'file_url' => $fileUrl,
        ], Response::HTTP_OK);
    }

    public function detailsReport(Request $request)
    {
        $rules = [
            'academic_year' => 'required',
            'class' => 'required',
            'group' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $applieds = AdmissionApplied::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year', $request->academic_year)
            ->where('class', trim($request->class))
            ->where('group', trim($request->group))
            ->where('approval_status', 'Success')
            ->orderBy('unique_number', 'asc')
            ->get();

        $admissionPaymentIds = AdmissionPayment::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year', $request->academic_year)
            ->where('class', trim($request->class))
            ->where('group', trim($request->group))
            ->pluck('id');

        $appliedUniqueNumbers = $applieds->pluck('unique_number');

        $configs = AdmissionConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereIn('admission_payment_id', $admissionPaymentIds)
            ->whereIn('unique_id', $appliedUniqueNumbers)
            ->orderBy('unique_id', 'asc')
            ->get();

        // Merge the data of applieds and configs by unique_number and unique_id
        $mergedData = $applieds->map(function ($applied) use ($configs) {
            $config = $configs->firstWhere('unique_id', $applied->unique_number);
            return (object) array_merge(
                $applied->toArray(),
                $config ? $config->toArray() : []
            );
        });

        try {
            $institute = Auth::user()->institute_detail;
            $instituteId = $institute->institute_id;
            $year = $request->academic_year;
            $class = $request->class;
            $group = $request->group;

            $fileName = "{$year}_{$class}_{$group}_admission_esif_report.pdf";
            $filePath = "{$instituteId}/admission/{$fileName}";
            $cacheName = "{$instituteId}_{$fileName}";

            $reportState = Cache::get($cacheName);
            if (empty($reportState)) {
                Cache::put($cacheName, 'pending', now()->addMinutes(10));
                GenerateAdmissionPDF::dispatch($mergedData, $institute->institute_name,  $institute->institute_address, $instituteId, $filePath, $cacheName);

                return response()->json([
                    'status' => 'success',
                    'data' => $mergedData,
                    'message' => 'Admission Applicant Details Report is being queued. Download after 2 minutes.',
                    'report_id' => $fileName,
                ]);
            } else {
                $currentReportState = $reportState === 'completed' ? Cache::get($cacheName) : null;

                if ($currentReportState === 'completed') {
                    Cache::forget($cacheName);
                    Log::info("{$cacheName} forgotten");
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Admission Applicant Details Report',
                        'cache' => $cacheName,
                        'pdf' => "/storage/{$filePath}",
                    ]);
                } else {
                    return response()->json([
                        'status' => 'success',
                        'event' => $reportState,
                        'cache' => $cacheName,
                        'message' => 'Admission Applicant Details Report is being generated. Try again in a minute.',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while generating the report.',
            ], 500);
        }
    }


    public function esifDetailsReport(Request $request)
    {
        $rules = [
            'academic_year' => 'required',
            'class' => 'required',
            'group' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $list = PrimevueDatatables::of(
            AdmissionApplied::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year', $request->academic_year)
                ->where('class', trim($request->class))
                ->where('group', trim($request->group))
                ->where('approval_status', 'Success')
        )->make();


        // Return file paths or download files as needed
        return response()->json([
            'status' => 'success',
            'message' => 'Admission Applicant Details Report',
            'esifList' => $list,
        ]);
    }

    public function generateExcel($configs, $applieds, $year, $class)
    {
        $instituteId = Auth::user()->institute_detail->institute_id;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set the column names
        $columns = [
            'SSC Board',
            'SSC Passing Year',
            'SSC Roll',
            "Applicant's Name, Father's Name, Mother's Name",
            'Class Roll, Guardian Name',
            "Student's Picture",
            'Address',
            'Student Mobile',
            'Date of Birth',
            'Religion, Gender',
            'Subject Code'
        ];

        $sheet->fromArray($columns, NULL, 'A1');

        // Set the data
        $row = 2;
        foreach ($configs as $index => $config) {
            $applied = $applieds[$index];
            $data = [
                $config->board,
                $config->passing_year,
                $config->roll,
                $applied->student_name_english . ', ' . $applied->father_name_english . ', ' . $applied->mother_name_english,
                $applied->assigned_roll,
                $applied->guardian_name,
                $applied->student_pic,
                $applied->permanent_address . ', ' . $applied->permanent_division . ', ' . $applied->permanent_district . ', ' . $applied->permanent_upozilla . ', ' . $applied->permanent_post_office . ', ' . $applied->permanent_post_code,
                $applied->student_mobile,
                $applied->date_of_birth,
                $applied->religion . ', ' . $applied->gender,
                $applied->subject
            ];
            $sheet->fromArray($data, NULL, 'A' . $row);
            $row++;
        }

        $fileName =  $year . '_' . $class . '_admission_report.xlsx';
        $directoryPath = $instituteId . '/admission/';
        $filePath = $directoryPath . '/' . $fileName;

        // Ensure the directory exists
        if (!Storage::disk('public')->exists($directoryPath)) {
            Storage::disk('public')->makeDirectory($directoryPath);
        }

        $writer = new Xlsx($spreadsheet);
        $savePath = storage_path('app/public/' . $filePath);
        $writer->save($savePath);

        return Storage::url($filePath);;
    }

    public function generatePDF($configs, $applieds, $year)
    {
        $instituteId = Auth::user()->institute_detail->institute_id;
        $institute_name = Auth::user()->institute_detail->institute_name;
        $institute_address = Auth::user()->institute_detail->institute_address;
        $directory = $instituteId . '/admission';

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $html = View::make('pdf.admission', compact('configs', 'applieds', 'institute_name', 'institute_address'))->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $fileName =  $year . '_admission_report.pdf';
        $filePath = $instituteId . '/admission/' . $fileName;
        // Ensure the directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0775, true);
        }

        file_put_contents(storage_path('app/public/' . $filePath), $dompdf->output());

        return $filePath;
    }

    public function admissionOps(Request $request)
    {
        $rules = [
            'start_date' => 'date|required',
            'end_date' => 'date|required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $instituteDetals = InstituteDetail::where('id', Auth::user()->institute_details_id)->first();

        if ($instituteDetals->gateway == "SPG") {
            $unique_numbers = AdmissionApplied::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('approval_status', 'Success')
                ->distinct()
                ->pluck('unique_number')
                ->toArray();

            $list = PrimevueDatatables::of(
                AdmissionSpgPay::select(
                    'unique_number',
                    'applicant_name',
                    'invoice_no',
                    'transaction_id',
                    'transaction_date',
                    'total_amount',
                    'status',
                    'msg'
                )->with(['admissionApplied' => function ($query) {
                    $query->select('unique_number', 'assigned_roll'); // include foreign key!
                }])->where('institute_details_id', Auth::user()->institute_details_id)
                    ->whereIn('unique_number', $unique_numbers)
                    ->where('transaction_date', '>=', $request->start_date)
                    ->where('transaction_date', '<=', $request->end_date)
                    ->where('status', 200)
                    ->where('msg', 'Success')
                    ->groupBy('unique_number')
            )->make();
        }

        if ($instituteDetals->gateway == "SSL") {

            $query = AdmissionApplied::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('approval_status', 'Success')
                ->where('date', '>=', $request->start_date)
                ->where('date', '<=', $request->end_date)
                ->select([
                    'unique_number as unique_number',
                    'student_name_english as applicant_name',
                    'date as transaction_date',
                    'unique_number as transaction_id',
                    'unique_number as invoice_no',
                'assigned_roll as assigned_roll',
                    'amount as total_amount',
                ]);

            // Pass query directly to PrimevueDatatables
            $list = PrimevueDatatables::of($query)->make();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Admission Applicant OPS Report',
            'admission_ops' => $list,
            'gateway' => $instituteDetals->gateway,
        ], Response::HTTP_OK);
    }
}
