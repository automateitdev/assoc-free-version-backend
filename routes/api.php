<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\auth\MerchantAuthController;
use App\Http\Controllers\backOffice\SslInfoController;
use App\Http\Controllers\backOffice\SubjectController;
use App\Http\Controllers\auth\BackOfficeAuthController;
use App\Http\Controllers\backOffice\BankInfoController;
use App\Http\Controllers\backOffice\AdmissionController;
use App\Http\Controllers\backOffice\InstituteController;
use App\Http\Controllers\operationPortal\wallet\WalletController;
use App\Http\Controllers\institutePortal\hr\HrEnrollmentController;
use App\Http\Controllers\institutePortal\openPortal\OpenController;
use App\Http\Controllers\institutePortal\admission\AdmissionLottery;
use App\Http\Controllers\institutePortal\admission\ReportController;
use App\Http\Controllers\institutePortal\students\StudentController;
use App\Http\Controllers\institutePortal\master\DepartmentController;
use App\Http\Controllers\operationPortal\merchant\MerchantController;
use App\Http\Controllers\institutePortal\students\PromotionController;
use App\Http\Controllers\institutePortal\students\EnrollmentController;
use App\Http\Controllers\operationPortal\global\CoreSettingsController;
use App\Http\Controllers\institutePortal\admission\ClassSetupController;
use App\Http\Controllers\institutePortal\master\CoreInstituteController;
use App\Http\Controllers\operationPortal\rolePermission\RolesController;
use App\Http\Controllers\institutePortal\feesManagement\RemoveController;
use App\Http\Controllers\institutePortal\feesManagement\WaiverController;
use App\Http\Controllers\institutePortal\admission\SubjectSetupController;
use App\Http\Controllers\institutePortal\admission\AdmissionSetupController;
use App\Http\Controllers\institutePortal\feesManagement\DateSetupController;
use App\Http\Controllers\institutePortal\master\InstituteClassMapController;
use App\Http\Controllers\institutePortal\admission\AdmissionConfigController;
use App\Http\Controllers\institutePortal\feesManagement\FeeMappingController;
use App\Http\Controllers\institutePortal\feesManagement\FeeStartupController;
use App\Http\Controllers\operationPortal\rolePermission\AdminUpdateController;
use App\Http\Controllers\operationPortal\rolePermission\PermissionsController;
use App\Http\Controllers\institutePortal\master\InstituteInformationController;
use App\Http\Controllers\institutePortal\feesManagement\AmountSettingController;
use App\Http\Controllers\institutePortal\GlobalConstant\GlobaConstantController;
use App\Http\Controllers\institutePortal\feesManagement\QuickCollectionController;
use App\Http\Controllers\institutePortal\feesManagement\StudentWiseFeesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


//Global Conastant api
Route::controller(GlobaConstantController::class)->group(function () {
    Route::get('boards', 'bardList');
    Route::get('payment-types', 'paymentTypeList');
    Route::get('subscription-status', 'suscriptionStatusList');
    //attendance
    Route::get('all-days', 'allDays');
    Route::get('all-sms-status', 'allSmsStatus');
    Route::prefix('institute')->group(function () {
        Route::get('service-types', 'instituteServiceTypes');
        Route::get('config-status', 'instituteConfigStatus');
        Route::get('config-billing-types', 'instituteConfigBillingTypes');
        Route::get('config-sms-status', 'instituteConfigSmsStatus');
        //institure hr
        Route::get('hr-status', 'instituteHrStatus');
    });
});


//*********************************BACK OFFICE START******************************/

Route::controller(BackOfficeAuthController::class)->prefix('operation-portal')->group(function () {
    Route::post('login', 'login');
    // Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::get('auth-user', 'authUser');
});

Route::get('online-admission-instruction', [AdmissionController::class, 'instruction']);
Route::group(['middleware' => ['auth:admin-api']], function () {
    Route::controller(BackOfficeAuthController::class)->prefix('operation-portal')->group(function () {
        Route::post('register', 'register');
    });


    Route::prefix('operation-portal')->group(function () {
        Route::resource('roles', RolesController::class);
        Route::resource('admin', AdminUpdateController::class);
        Route::resource('permissions', PermissionsController::class);

        Route::resource('merchant', MerchantController::class);
        Route::resource('wallet', WalletController::class);
        Route::post('wallet-mapping', [WalletController::class, 'walletMapping'])->name('wallet.mapping');
        Route::post('add-wallet', [WalletController::class, 'addNewWallet'])->name('wallet.add');
        Route::post('/wallet/status-update', [WalletController::class, 'walletStatusToggle'])->name('wallet.status.toggle');
        Route::post('/wallet-map/status-update', [WalletController::class, 'walletMapStatusToggle'])->name('wallet-map.status.update');

        //global data
        Route::get('core-category', [CoreSettingsController::class, 'index']);
        Route::get('core-subcategories/{id}', [CoreSettingsController::class, 'findsubcategory']);
        Route::post('core-subcategories/store', [CoreSettingsController::class, 'store']);

        Route::resource('institute', InstituteController::class);
        Route::resource('ssl-info', SslInfoController::class);
        Route::resource('online-admission', AdmissionController::class);
        Route::resource('global-subject', SubjectController::class);
        Route::post('online-admission-instruction-store', [AdmissionController::class, 'pay_instruction']);
        // Route::get('institute-information', [InstituteController::class, 'index']);
        Route::get('institute-bank', [BankInfoController::class, 'index']);
        Route::post('institute-bank/store', [BankInfoController::class, 'store']);
    });
});
//*********************************BACK OFFICE END******************************/

Route::controller(MerchantAuthController::class)->prefix('merchant')->group(function () {
    Route::post('login', 'login');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::get('auth-user', 'authUser');
});


//master settings
//guard(institute)

Route::group(['middleware' => ['auth:api']], function () {

    ////role and permissions
    Route::resource('roles', RolesController::class);
    Route::resource('permissions', PermissionsController::class);

    //*******************master settings**********************
    Route::get('core-category', [CoreSettingsController::class, 'index']);

    Route::get('core-subcategories/{id}', [CoreSettingsController::class, 'findsubcategory']);
    //core institute
    Route::get('core-institute', [CoreInstituteController::class, 'index']);
    Route::get('core-institute/show/{corecategory}', [CoreInstituteController::class, 'show']);
    Route::post('core-institute/store', [CoreInstituteController::class, 'store']);

    //institute class details map
    Route::get('class-details-map', [InstituteClassMapController::class, 'index']);
    Route::post('class-details-map/store', [InstituteClassMapController::class, 'store']);
    Route::get('class-details-map/show/{id}', [InstituteClassMapController::class, 'show']);
    Route::delete('class-details-map/delete/{id}', [InstituteClassMapController::class, 'destroy']);

    // class with shift-section-group
    Route::post('class-details-map/shift', [InstituteClassMapController::class, 'shift']);
    Route::post('class-details-map/section', [InstituteClassMapController::class, 'section']);
    Route::post('class-details-map/group', [InstituteClassMapController::class, 'group']);

    //institute info
    Route::get('institute-information/index', [InstituteInformationController::class, 'index']);
    Route::post('institute-information/update/{id}', [InstituteInformationController::class, 'update']);

    //department
    Route::resource('department', DepartmentController::class);

    //*******************student***********************
    Route::resource('students', StudentController::class);
    Route::post('student-list', [StudentController::class, 'studentList'])->name('student.list');
    //active / inactive
    Route::post('students/change-status', [StudentController::class, 'toggleStatus']);
    Route::post('students/status-inactive', [StudentController::class, 'studentInactive']);
    //student update
    Route::get('student-update/basic-info/index', [StudentController::class, 'index']);
    Route::post('student-update/basic-info-search', [StudentController::class, 'basicInfoSearch']);
    Route::post('student-update/basic-info-store', [StudentController::class, 'basicInfoStore']);
    Route::post('student-update/basic-excel-generate', [StudentController::class, 'excelGenerate']);
    //class update
    Route::post('student-update/class-info-update', [StudentController::class, 'classInfoStore']);

    //enrollemnt
    Route::get('student-enrollment', [EnrollmentController::class, 'index']);
    Route::post('student-enrollment/store', [EnrollmentController::class, 'store']);
    Route::post('student-enrollment/excel-store', [EnrollmentController::class, 'excelStore']);
    Route::get('student-enrollment/excel-file/download', [EnrollmentController::class, 'excelDwonload']);

    //promotion
    Route::get('without-merit-promotion/index', [PromotionController::class, 'index']);
    Route::post('without-merit-promotion/search', [PromotionController::class, 'withoutMeritSearch']);
    Route::post('without-merit-promotion/store', [PromotionController::class, 'withoutMeritStore']);
    //pushback
    Route::post('migration-pushback/store', [PromotionController::class, 'puchbackStore']);

    //hr
    Route::resource('hr-enrollment', HrEnrollmentController::class);
    //******************************Fees Management*************************
    //startup
    Route::get('fees-management/startup', [FeeStartupController::class, 'index']);
    Route::post('fees-management/startup/feehead/store', [FeeStartupController::class, 'feeHeadStore']);
    Route::delete('fees-management/startup/feehead/delete/{id}', [FeeStartupController::class, 'feeHeadDestroy']);
    Route::post('fees-management/startup/feesubhead/store', [FeeStartupController::class, 'feeSubheadStore']);
    Route::delete('fees-management/startup/feesubhead/delete/{id}', [FeeStartupController::class, 'feeSubheadDestroy']);
    Route::post('fees-management/startup/waiver/store', [FeeStartupController::class, 'waiverStore']);
    Route::post('fees-management/startup/fund/store', [FeeStartupController::class, 'fundStore']);
    Route::delete('fees-management/startup/fund/delete/{id}', [FeeStartupController::class, 'fundDestroy']);
    Route::post('fees-management/startup/ledger/store', [FeeStartupController::class, 'ledgerStore']);
    Route::delete('fees-management/startup/ledger/delete/{id}', [FeeStartupController::class, 'ledgerDestroy']);

    //mapping
    Route::get('fees-management/mapping', [FeeMappingController::class, 'index']);
    Route::post('fees-management/mapping/fee/store', [FeeMappingController::class, 'feeMappingStore']);
    Route::post('fees-management/mapping/fine/store', [FeeMappingController::class, 'fineMappingStore']);
    Route::post('fees-management/mapping/ops/store', [FeeMappingController::class, 'opsMappingStore']);
    Route::get('fees-management/mapping/bank-info', [FeeMappingController::class, 'bankInfo']);

    //Amount set
    Route::get('fees-management/amount-setting', [AmountSettingController::class, 'index']);
    Route::get('fees-management/amount-setting/fund-show/{id}', [AmountSettingController::class, 'fundShow']);
    Route::post('fees-management/amount-setting/class-wise-feeamount/store', [AmountSettingController::class, 'feeAmountStore']);
    Route::post('fees-management/amount-setting/class-wise-feeamount/view', [AmountSettingController::class, 'feeAmountShow']);
    Route::get('fees-management/amount-setting/class-wise-feeamount/edit/{id}', [AmountSettingController::class, 'feeAmountEdit']);
    Route::post('fees-management/amount-setting/class-wise-feeamount/update', [AmountSettingController::class, 'feeAmountUpdate']);

    //student wise amount set
    Route::post('fees-management/amount-setting/student-wise/search', [StudentWiseFeesController::class, 'search']);
    Route::post('fees-management/amount-setting/student-wise/store', [StudentWiseFeesController::class, 'store']);
    Route::post('fees-management/amount-setting/student-wise/excel-generate', [StudentWiseFeesController::class, 'excelGenerate']);

    //date setup
    Route::get('fees-management/date-setup', [DateSetupController::class, 'index']);
    Route::post('fees-management/date-setup/show', [DateSetupController::class, 'show']);
    Route::post('fees-management/date-setup/store', [DateSetupController::class, 'store']);
    Route::post('fees-management/date-setup/search', [DateSetupController::class, 'search']);
    Route::post('fees-management/date-setup/update', [DateSetupController::class, 'update']);

    //waiver setup
    Route::resource('waiver-setup', WaiverController::class);
    Route::post('waiver-setup/search', [WaiverController::class, 'search']);
    Route::post('waiver-setup/getfeeheadWiseAmount', [WaiverController::class, 'getfeeheadWiseAmount']);
    Route::post('waiver-setup/assign-list', [WaiverController::class, 'assignList']);

    //remove feehead/subhead
    Route::get('remove-setup', [RemoveController::class, 'index']);
    Route::post('remove/search', [RemoveController::class, 'search']);
    Route::post('remove/feeHead', [RemoveController::class, 'fee_head_remove']);
    Route::post('remove/feeSubHead', [RemoveController::class, 'fee_subhead_remove']);

    Route::post('remove/feeHead/show', [RemoveController::class, 'fee_head_show']);
    Route::post('remove/feeHead/reassign', [RemoveController::class, 'fee_head_reassign']);

    Route::post('remove/feeSubHead/show', [RemoveController::class, 'sub_head_show']);
    Route::post('remove/feeSubHead/reassign', [RemoveController::class, 'sub_head_reassign']);

    //open portal
    Route::get('open-portal-index', [OpenController::class, 'index']);
    Route::post('open-portal/store', [OpenController::class, 'store']);
    Route::get('open-portal/excel', [OpenController::class, 'excelDwonload']);
    Route::get('open-portal/show', [OpenController::class, 'show']);
    Route::post('open-portal/update', [OpenController::class, 'update']);

    //admission
    Route::resource('admission-setup', AdmissionSetupController::class);
    Route::resource('admission-class-setup', ClassSetupController::class);
    Route::resource('admission-subject-setup', SubjectSetupController::class);
    Route::resource('admission-configuration', AdmissionConfigController::class);
    Route::post('admission-configuration/excelDownload', [AdmissionConfigController::class, 'excelDownload']);
    Route::post('admission-configuration/enlistment-list', [AdmissionConfigController::class, 'enlistmentList']);

    Route::post('admission-startup', [ClassSetupController::class, 'admissionStartup']);

    Route::get('admission-class-info/{class_id}', [SubjectSetupController::class, 'classInfo']);

    Route::post('admission-applied-list', [ReportController::class, 'appliedList']);
    Route::post('admission-applied-payment-success-list', [ReportController::class, 'appliedSuccessList']);
    Route::post('admission-applied-payment-pending-list', [ReportController::class, 'appliedPendingList']);
    Route::post('admission-applied-subject-wise-report', [ReportController::class, 'subjectReport']);
    Route::post('admission-applied-esif-details-report', [ReportController::class, 'esifDetailsReport']);
    Route::post('admission-applied-details-report', [ReportController::class, 'detailsReport']);
    Route::post('admission-ops-report', [ReportController::class, 'admissionOps']);

    Route::post('admission-lottery-generate', [AdmissionLottery::class, 'lotteryGenerate']);
    Route::post('admission-lottery-list', [AdmissionLottery::class, 'lotteryList']);

    // ADMISSION EXAM
    Route::get('admission/exam/essentials', [AdmissionController::class, 'examEssentials']);
    Route::get('admission/exams', [AdmissionController::class, 'getAdmissionExamList']);
    Route::post('/admission/examinee-list', [AdmissionController::class, 'getAdmissionExamineeList']);

    Route::post('admission/exam-save', [AdmissionController::class, 'admissionExamSave']);
    Route::delete('admission/remove/center/{center_id}', [AdmissionController::class, 'removeExamCenter']);
    Route::delete('admission/remove/exam/{exam_id}', [AdmissionController::class, 'removeExam']);

    Route::post('seat-card/export', [AdmissionController::class, 'startSeatCardExport']);
    Route::get('seat-card/export-progress', [AdmissionController::class, 'exportProgress']);
});

//admission api
Route::get('admission-data/{institute_id}', [ApiController::class, 'admission']);
Route::post('admission-year-search/{year}', [ApiController::class, 'yearWiseSearch']);
Route::post('student-roll-search', [ApiController::class, 'rollSearch']);
Route::post('student-form-store', [ApiController::class, 'studentStore']);
Route::post('student-form-update/{unique_number}', [ApiController::class, 'studentDataupdate']);
Route::get('student-form-preview/{unique_number}', [ApiController::class, 'preview']);
Route::get('student-admission-invoice/{unique_number}', [ApiController::class, 'admission_invoice']);
Route::get('student-admission-admit/{unique_number}', [ApiController::class, 'admission_admit']);

Route::get('divisions-data', [ApiController::class, 'division']);
Route::get('district-data/{division_id}', [ApiController::class, 'district']);
Route::get('upozila-data/{district_id}', [ApiController::class, 'upozila']);

//SPG
// Route::post('payment-spg', [ApiController::class, 'paymentSPG']);
//spg IPN
// Route::post('dataupdate', [ApiController::class, 'dataupdate']);

//ssl payment
Route::post('/pay', [ApiController::class, 'sslPayment']);
Route::prefix('sslcz')->group(function () {
    Route::post('/success', [ApiController::class, 'success']);
    Route::post('/cancel', [ApiController::class, 'cancel']);
    Route::post('/fail', [ApiController::class, 'fail']);
    Route::post('/ipn', [ApiController::class, 'ipn']);
});



// Fixation:
Route::get('/fix/academic-year-id', [ApiController::class, 'populateAcademicYearId']);