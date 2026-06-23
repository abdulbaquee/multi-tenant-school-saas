<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamResultIndexRequest;
use App\Http\Requests\ExamResultProcessRequest;
use App\Models\Exam;
use App\Models\User;
use App\Services\ExamResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamResultController extends Controller
{
    public function __construct(private readonly ExamResultService $examResults) {}

    public function index(ExamResultIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('exam-results.index', [
            'results' => $this->examResults->listFor($actor, $request->validated()),
        ]);
    }

    public function show(ExamResultIndexRequest $request, Exam $exam): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('exam-results.show', $this->examResults->examSummary($exam, $actor));
    }

    public function process(ExamResultProcessRequest $request, Exam $exam): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->examResults->processResults($exam, $actor);

        return redirect()->route('exam-results.show', $exam)->with(
            'status',
            __('Results processed successfully. :processed rows recalculated.', $result),
        );
    }
}
