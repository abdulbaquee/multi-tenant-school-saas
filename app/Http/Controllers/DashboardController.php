<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\ExamResult;
use App\Models\FeeInvoice;
use App\Models\School;
use App\Models\Student;

class DashboardController extends Controller
{
    public function __invoke(School $school)
    {
        return view('dashboard.index', [
            'school' => $school,
            'studentsCount' => Student::count(),
            'attendanceToday' => AttendanceRecord::whereDate('attendance_date', today())->count(),
            'pendingFees' => FeeInvoice::where('status', '!=', 'paid')->sum('amount'),
            'recentGrades' => ExamResult::latest()->take(5)->get(),
            'recentAudits' => AuditLog::latest()->take(10)->get(),
        ]);
    }
}
