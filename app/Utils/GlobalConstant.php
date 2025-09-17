<?php
namespace App\Utils;

class GlobalConstant
{
    const ACTIVE = true;
    const INACTIVE = false;

    const RESULT = ['Grade', 'Division'];

    const MERITAL_STATUS = ['Single', 'Married', 'Divorced', 'Widowed'];
    const STUDENT_TYPE = ['Regular', 'Irregular'];
    const RESIDENTIAL_TYPE = ['Residential', 'Non-residential'];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    const IMAGE_FIT = 'fit';
    const IMAGE_RESIZE = 'resize';
    const IMAGE_RESIZE_WITH_ASPECT_RATIO ='resize_with_effect_ratio';
    const IMAGE_RESIZE_WITH_CANVAS = 'canvas';

    //subscription form
    const SUBSCRIPTION_STATUS = ['pending','complete'];
    const SUBSCRIPTION_PAYMENT_TYPE = ['trial','one_time','monthly','half_yearly','yearly'];
    const SUBSCRIPTION_EDUCATION_BOARD_LIST = ['Barisal','Chattogram','Cumilla','Dhaka','Dinajpur','Jashore','Mymensingh','Rajshahi','Sylhet'];
    //institute config table
    const INSTITUTE_SERVICE_TYPE = ['saas','onetime'];
    const INSTITUTE_CONFIG_STATUS = ['active','inactive','suspend'];
    const INSTITUTE_BILLING_TYPE = ['monthly','yearly'];
    const INSTITUTE_SMS_STATUS = ['active', 'inactive', 'pending', 'disabled'];
    const INSTITUTE_HR_STATUS = ['active','inactive','suspend'];
    
    const SIGNATURE_PATH = 'signature';

    //common
    const BLOOD = ['No', 'A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
    const GENDER = ['Male', 'Female', 'Others'];
    const RELIGION = ['Islam', 'Hinduism', 'Buddhism', 'Christianity'];
    const PAYSTATUS = ['UNPAID', 'REMOVE', 'PAID', 'REVERSE', 'OMITTED'];
    const SWITCH = ['TRUE', 'FALSE'];
    const RULES = ['Any ID, Any Amount', 'Fixed ID, Fixed Amount', 'Any ID, Fixed Amount', 'Fixed ID, Any Amount'];
    const OPEN_STATUS = ['ACTIVE', 'INACTIVE'];
    const FILE = ['YES', 'NO'];
    const YN = ['YES', 'NO'];
    //Student Attendance
    const DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];
    const SMS_STATUS = ['present','absent'];
    const ATTENDANCE_STATUS = ['P','A','L'];
    const GATEWAY = ['SPG', 'SSL'];
    // for api
    const ALL_DAYS = [
        'saturday' => 'saturday',
        'sunday' => 'sunday',
        'monday' => 'monday',
        'tuesday' => 'tuesday',
        'wednesday' => 'wednesday',
        'thursday' => 'thursday'
    ];
    const ALL_SMS_STATUS = [
        'present' => 'present',
        'absent' => 'absent',
        'both' => 'both',
        'none' => 'none',
    ];
    // for api end

    //role
    const ROLE_TYPE = ['institute','backoffice'];

}
