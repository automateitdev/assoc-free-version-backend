<?php

namespace App\Http\Controllers\institutePortal\GlobalConstant;

use App\Http\Controllers\Controller;
use App\Utils\GlobalConstant;
use Illuminate\Http\Request;

class GlobaConstantController extends Controller
{
    public function bardList(){
         return response()->json([
            'data' => GlobalConstant::SUBSCRIPTION_EDUCATION_BOARD_LIST
        ]);
    }

    public function paymentTypeList(){
         return response()->json([
            'data' => GlobalConstant::SUBSCRIPTION_PAYMENT_TYPE
        ]);
    }
    public function suscriptionStatusList(){
         return response()->json([
            'data' => GlobalConstant::SUBSCRIPTION_STATUS
        ]);
    }
    public function instituteServiceTypes(){
         return response()->json([
            'data' => GlobalConstant::INSTITUTE_SERVICE_TYPE
        ]);
    }
    public function instituteConfigStatus(){
         return response()->json([
            'data' => GlobalConstant::INSTITUTE_CONFIG_STATUS
        ]);
    }
    public function instituteConfigBillingTypes(){
         return response()->json([
            'data' => GlobalConstant::INSTITUTE_BILLING_TYPE
        ]);
    }
    
    public function instituteConfigSmsStatus(){
         return response()->json([
            'data' => GlobalConstant::INSTITUTE_SMS_STATUS
        ]);
    }
    public function instituteHrStatus(){
         return response()->json([
            'data' => GlobalConstant::INSTITUTE_HR_STATUS
        ]);
    }
    public function allDays(){
         return response()->json([
            'data' => GlobalConstant::ALL_DAYS
        ]);
    }
    public function allSmsStatus(){
         return response()->json([
            'data' => GlobalConstant::ALL_SMS_STATUS
        ]);
    }
    
}
