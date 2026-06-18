<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(School $school)
    {
        return view('attendance.index', [
            'school' => $school,
            'students' => Student::orderBy('first_name')->get(),
            'attendance' => AttendanceRecord::whereDate('attendance_date', today())->with('student')->get(),
        ]);
    }

    public function store(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late,excused'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        AttendanceRecord::updateOrCreate(
            [
                'school_id' => $school->id,
                'student_id' => $validated['student_id'],
                'attendance_date' => $validated['attendance_date'],
            ],
            [
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
            ],
        );

        return redirect()->route('attendance.index', $school)->with('status', 'Attendance saved.');
    }
}
