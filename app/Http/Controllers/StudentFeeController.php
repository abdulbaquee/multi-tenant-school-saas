<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentFeeIndexRequest;
use App\Http\Requests\StudentFeeStoreRequest;
use App\Models\StudentFee;
use App\Models\User;
use App\Services\StudentFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentFeeController extends Controller
{
    public function __construct(private readonly StudentFeeService $studentFees) {}

    public function index(StudentFeeIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('student-fees.index', [
            'studentFees' => $this->studentFees->listFor($actor, $request->validated()),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', StudentFee::class);

        /** @var User $actor */
        $actor = $request->user();
        $structureId = $request->integer('fee_structure_id') ?: null;

        return view('student-fees.create', $this->studentFees->assignmentOptions($actor, $structureId));
    }

    public function store(StudentFeeStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $studentFee = $this->studentFees->assign($request->validated(), $actor);

        return redirect()->route('student-fees.show', $studentFee)->with('status', __('Student Fee assigned successfully.'));
    }

    public function show(Request $request, StudentFee $studentFee): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('student-fees.show', [
            'studentFee' => $this->studentFees->detailsFor($studentFee, $actor),
        ]);
    }
}
