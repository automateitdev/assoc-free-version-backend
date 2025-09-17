<?php
namespace App\Services\Student;

use Illuminate\Support\Str;
use App\Services\BaseService;

use App\Models\Student;

use App\Services\Utils\FileUploadService;


class StudentService extends BaseService{

    protected $fileUploadService;


    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $model = new Student();
        parent::__construct($model);
        $this->fileUploadService = app(FileUploadService::class);
    }
  
}
