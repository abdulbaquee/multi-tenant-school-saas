<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'examination_id',
        'student_id',
        'subject',
        'marks_obtained',
        'marks_total',
        'grade',
    ];

    protected function casts(): array
    {
        return [
            'marks_obtained' => 'decimal:2',
            'marks_total' => 'decimal:2',
        ];
    }

    public function examination(): BelongsTo
    {
        return $this->belongsTo(Examination::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
