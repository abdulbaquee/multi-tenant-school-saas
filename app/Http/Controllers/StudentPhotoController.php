<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentPhotoUpdateRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentPhotoController extends Controller
{
    public function __construct(private readonly StudentService $students) {}

    public function show(Request $request, Student $student): StreamedResponse
    {
        $this->authorize('viewPhoto', $student);
        /** @var User $actor */
        $actor = $request->user();
        $path = $this->students->photoPathFor($student, $actor);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(StudentPhotoUpdateRequest $request, Student $student): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->students->replacePhoto($student, $request->file('photo'), $actor);

        return redirect()->route('students.show', $student)->with('status', 'Student photo updated successfully.');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('updatePhoto', $student);
        /** @var User $actor */
        $actor = $request->user();
        $this->students->removePhoto($student, $actor);

        return redirect()->route('students.show', $student)->with('status', 'Student photo removed successfully.');
    }
}
