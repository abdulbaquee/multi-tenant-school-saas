<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamSubjectIndexRequest;
use App\Http\Requests\ExamSubjectStoreRequest;
use App\Models\ExamSubject;
use App\Models\User;
use App\Services\ExamSubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamSubjectController extends Controller
{
    public function __construct(private readonly ExamSubjectService $examSubjects) {}

    public function index(ExamSubjectIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('exam-subjects.index', [
            'examSubjects' => $this->examSubjects->listFor($actor, $request->validated()),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ExamSubject::class);

        /** @var User $actor */
        $actor = $request->user();
        $examId = $request->integer('exam_id') ?: null;
        $classId = $request->integer('class_id') ?: null;

        return view('exam-subjects.create', $this->examSubjects->assignmentOptions($actor, $examId, $classId));
    }

    public function store(ExamSubjectStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $examSubject = $this->examSubjects->assign($request->validated(), $actor);

        return redirect()->route('exam-subjects.show', $examSubject)->with('status', __('Exam Subject assigned successfully.'));
    }

    public function show(Request $request, ExamSubject $examSubject): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('exam-subjects.show', [
            'examSubject' => $this->examSubjects->detailsFor($examSubject, $actor),
        ]);
    }
}
