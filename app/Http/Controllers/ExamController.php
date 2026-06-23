<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamIndexRequest;
use App\Http\Requests\ExamLifecycleRequest;
use App\Http\Requests\ExamStoreRequest;
use App\Http\Requests\ExamUpdateRequest;
use App\Models\Exam;
use App\Models\User;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(private readonly ExamService $exams) {}

    public function index(ExamIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('exams.index', [
            'exams' => $this->exams->listFor($actor, $request->validated()),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Exam::class);

        /** @var User $actor */
        $actor = $request->user();

        return view('exams.create', $this->exams->formOptions($actor));
    }

    public function store(ExamStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $exam = $this->exams->create($request->validated(), $actor);

        return redirect()->route('exams.show', $exam)->with('status', __('Exam created successfully.'));
    }

    public function show(Request $request, Exam $exam): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('exams.show', [
            'exam' => $this->exams->detailsFor($exam, $actor),
        ]);
    }

    public function edit(Exam $exam): View
    {
        $this->authorize('update', $exam);

        return view('exams.edit', compact('exam'));
    }

    public function update(ExamUpdateRequest $request, Exam $exam): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->exams->update($exam, $request->validated(), $actor);

        return redirect()->route('exams.show', $exam)->with('status', __('Exam updated successfully.'));
    }

    public function publish(ExamLifecycleRequest $request, Exam $exam): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->exams->publish($exam, $actor);

        return redirect()->route('exams.show', $exam)->with('status', __('Exam published successfully.'));
    }

    public function complete(ExamLifecycleRequest $request, Exam $exam): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->exams->complete($exam, $actor);

        return redirect()->route('exams.show', $exam)->with('status', __('Exam marked as completed.'));
    }

    public function cancel(ExamLifecycleRequest $request, Exam $exam): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->exams->cancel($exam, $actor);

        return redirect()->route('exams.show', $exam)->with('status', __('Exam cancelled successfully.'));
    }

    public function archive(ExamLifecycleRequest $request, Exam $exam): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->exams->archive($exam, $actor);

        return redirect()->route('exams.index')->with('status', __('Exam archived successfully.'));
    }
}
