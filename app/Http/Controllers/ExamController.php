<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use App\Models\Examination;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(School $school)
    {
        return view('exams.index', [
            'school' => $school,
            'students' => Student::orderBy('first_name')->get(),
            'exams' => Examination::latest()->with('results.student')->get(),
        ]);
    }

    public function store(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:120'],
            'exam_date' => ['required', 'date'],
        ]);

        Examination::create($validated + ['school_id' => $school->id]);

        return redirect()->route('exams.index', $school)->with('status', 'Examination created.');
    }

    public function storeResult(Request $request, School $school, Examination $exam): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'subject' => ['required', 'string', 'max:120'],
            'marks_obtained' => ['required', 'numeric', 'min:0'],
            'marks_total' => ['required', 'numeric', 'min:1'],
        ]);

        $percentage = ($validated['marks_obtained'] / $validated['marks_total']) * 100;
        $grade = match (true) {
            $percentage >= 80 => 'A',
            $percentage >= 70 => 'B',
            $percentage >= 60 => 'C',
            $percentage >= 50 => 'D',
            default => 'F',
        };

        ExamResult::create($validated + [
            'school_id' => $school->id,
            'examination_id' => $exam->id,
            'grade' => $grade,
        ]);

        return redirect()->route('exams.index', $school)->with('status', 'Exam result recorded.');
    }
}
