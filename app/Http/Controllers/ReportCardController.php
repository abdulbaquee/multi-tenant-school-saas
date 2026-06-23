<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportCardGenerateRequest;
use App\Http\Requests\ReportCardIndexRequest;
use App\Models\Exam;
use App\Models\ReportCard;
use App\Models\User;
use App\Services\ReportCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    public function __construct(private readonly ReportCardService $reportCards) {}

    public function index(ReportCardIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $filters = $request->validated();
        $filterExam = filled($filters['exam_id'] ?? null)
            ? Exam::query()->find($filters['exam_id'])
            : null;

        return view('report-cards.index', [
            'reportCards' => $this->reportCards->listFor($actor, $filters),
            'filterExam' => $filterExam,
        ]);
    }

    public function show(Request $request, ReportCard $reportCard): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->authorize('view', $reportCard);

        return view('report-cards.show', $this->reportCards->detailFor($reportCard, $actor));
    }

    public function print(Request $request, ReportCard $reportCard): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->authorize('view', $reportCard);

        return view('report-cards.print', $this->reportCards->detailFor($reportCard, $actor));
    }

    public function generate(ReportCardGenerateRequest $request, Exam $exam): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->reportCards->generateForExam($exam, $actor);

        $message = $result['created'] === 0 && $result['regenerated'] === 0
            ? __('No report cards were generated. Ensure every eligible student has marks for all exam subjects in their class, then try again. Skipped: :skipped.', $result)
            : __('Report cards generated. :created created, :regenerated regenerated, :skipped skipped.', $result);

        return redirect()
            ->route('report-cards.index', ['exam_id' => $exam->id])
            ->with($result['created'] === 0 && $result['regenerated'] === 0 ? 'warning' : 'status', $message);
    }
}
