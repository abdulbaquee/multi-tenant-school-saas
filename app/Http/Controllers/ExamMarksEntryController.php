<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamMarksEntryStoreRequest;
use App\Http\Requests\ExamMarksEntryWorkspaceRequest;
use App\Models\User;
use App\Services\ExamResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamMarksEntryController extends Controller
{
    public function __construct(private readonly ExamResultService $examResults) {}

    public function index(ExamMarksEntryWorkspaceRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('exam-marks-entry.index', $this->examResults->marksEntryWorkspace($actor, $request->validated()));
    }

    public function store(ExamMarksEntryStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->examResults->saveMarksRoster($request->validated(), $actor);

        return redirect()->route('exam-marks-entry.index', [
            'exam_subject_id' => $request->integer('exam_subject_id'),
        ])->with(
            'status',
            __('Marks saved successfully. :created created, :corrected corrected, :unchanged unchanged.', $result),
        );
    }
}
