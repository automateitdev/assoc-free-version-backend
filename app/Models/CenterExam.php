<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CenterExam extends Model
{
    use HasFactory;

    protected $table = 'center_exam';

    protected $fillable = [
        'exam_id',
        'center_id',
        'center_name'
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }
}
