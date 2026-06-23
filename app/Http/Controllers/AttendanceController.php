<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Http\Requests\AttendanceHistoryRequest;
use App\Http\Requests\AttendanceMonthlySummaryRequest;
use App\Http\Requests\AttendanceStoreRequest;
use App\Http\Requests\AttendanceWorkspaceRequest;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendances) {}

    public function index(AttendanceWorkspaceRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('attendance.index', $this->attendances->workspace($actor, $request->validated()));
    }

    public function store(AttendanceStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->attendances->saveRoster($request->validated(), $actor);

        return redirect()->route('attendance.index', [
            'attendance_date' => $request->validated('attendance_date'),
            'section_id' => $request->integer('section_id'),
        ])->with(
            'status',
            __('Attendance saved successfully. :created created, :corrected corrected, :unchanged unchanged.', $result),
        );
    }

    public function edit(AttendanceWorkspaceRequest $request, Attendance $attendance): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('attendance.edit', [
            'attendance' => $this->attendances->correctionForm($attendance, $actor),
        ]);
    }

    public function update(
        AttendanceCorrectionRequest $request,
        Attendance $attendance,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->attendances->correct($attendance, $request->validated(), $actor);

        return redirect()->route('attendance.history')->with('status', 'Attendance corrected successfully.');
    }

    public function history(AttendanceHistoryRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('attendance.history', $this->attendances->history($actor, $request->validated()));
    }

    public function monthlySummary(AttendanceMonthlySummaryRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('attendance.monthly-summary', $this->attendances->monthlySummary($actor, $request->validated()));
    }
}
