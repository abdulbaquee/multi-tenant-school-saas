<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(School $school)
    {
        return view('students.index', [
            'school' => $school,
            'students' => Student::latest()->paginate(20),
        ]);
    }

    public function store(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'admission_number' => ['required', 'string', 'max:100', 'unique:students,admission_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:120'],
            'dob' => ['nullable', 'date'],
        ]);

        Student::create($validated + ['school_id' => $school->id, 'status' => 'active']);

        return redirect()->route('students.index', $school)->with('status', 'Student added.');
    }

    public function update(Request $request, School $school, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:active,inactive,graduated'],
        ]);

        $student->update($validated);

        return redirect()->route('students.index', $school)->with('status', 'Student updated.');
    }

    public function destroy(School $school, Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->route('students.index', $school)->with('status', 'Student removed.');
    }
}
