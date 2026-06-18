<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\FeeInvoice;
use App\Models\School;
use App\Models\Student;

class ReportController extends Controller
{
    public function index(School $school)
    {
        $totalFees = FeeInvoice::sum('amount');
        $paidFees = FeeInvoice::where('status', 'paid')->sum('amount');

        return view('reports.index', [
            'school' => $school,
            'studentCount' => Student::count(),
            'presentToday' => AttendanceRecord::whereDate('attendance_date', today())->where('status', 'present')->count(),
            'absentToday' => AttendanceRecord::whereDate('attendance_date', today())->where('status', 'absent')->count(),
            'feeCollectionRate' => $totalFees > 0 ? round(($paidFees / $totalFees) * 100, 2) : 0,
        ]);
    }
}
