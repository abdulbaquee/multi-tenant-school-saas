<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentEnrollmentLifecycleRequest;
use App\Http\Requests\StudentEnrollmentStoreRequest;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentEnrollmentController extends Controller
{
    public function __construct(private readonly StudentEnrollmentService $enrollments) {}

    public function create(Request $request, Student $student): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $options = $this->enrollments->formOptions($student, $actor);

        return view('student-enrollments.create', [
            'student' => $student,
            ...$options,
        ]);
    }

    public function store(StudentEnrollmentStoreRequest $request, Student $student): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->enrollments->create($student, $request->validated(), $actor);

        return redirect()->route('students.show', $student)->with('status', 'Student enrolled successfully.');
    }

    public function complete(
        StudentEnrollmentLifecycleRequest $request,
        StudentEnrollment $studentEnrollment,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $enrollment = $this->enrollments->complete($studentEnrollment, $actor);

        return redirect()
            ->route('students.show', $enrollment->student_id)
            ->with('status', 'Enrollment completed successfully.');
    }

    public function transfer(StudentEnrollmentLifecycleRequest $request, Student $student): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->enrollments->transfer($student, $actor);

        return redirect()->route('students.show', $student)->with('status', 'Student transferred successfully.');
    }

    public function graduate(StudentEnrollmentLifecycleRequest $request, Student $student): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->enrollments->graduate($student, $actor);

        return redirect()->route('students.show', $student)->with('status', 'Student graduated successfully.');
    }
}
