<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceReportIndexRequest;
use App\Http\Requests\ExaminationReportIndexRequest;
use App\Http\Requests\FeeReportIndexRequest;
use App\Http\Requests\ReportIndexRequest;
use App\Http\Requests\StudentReportIndexRequest;
use App\Reporting\ReportCategory;
use App\Services\AttendanceReportService;
use App\Services\ExaminationReportService;
use App\Services\FeeReportService;
use App\Services\ReportCsvExportService;
use App\Services\ReportService;
use App\Services\StudentReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly StudentReportService $studentReports,
        private readonly AttendanceReportService $attendanceReports,
        private readonly FeeReportService $feeReports,
        private readonly ExaminationReportService $examinationReports,
        private readonly ReportCsvExportService $csvExports,
    ) {}

    public function index(ReportIndexRequest $request): View
    {
        $this->authorize('reports.view');

        return view('reports.index', $this->reports->hubFor($request->user()));
    }

    public function students(StudentReportIndexRequest $request): View
    {
        $this->authorize('reports.students.view');

        return view('reports.students', $this->studentReports->reportFor(
            $request->user(),
            $request->validated(),
        ));
    }

    public function attendance(AttendanceReportIndexRequest $request): View
    {
        $this->authorize('reports.attendance.view');

        return view('reports.attendance', $this->attendanceReports->reportFor(
            $request->user(),
            $request->validated(),
        ));
    }

    public function exportStudents(StudentReportIndexRequest $request): StreamedResponse
    {
        $this->authorize('reports.students.export');

        $actor = $request->user();
        $rows = $this->studentReports->exportRowsFor($actor, $request->validated());
        $this->studentReports->logExport($actor, count($rows));

        return $this->csvExports->stream(
            $actor,
            ReportCategory::Students,
            'student-report-'.now()->format('Y-m-d-His').'.csv',
            $this->studentReports->exportHeadersFor($actor),
            $rows,
        );
    }

    public function exportAttendance(AttendanceReportIndexRequest $request): StreamedResponse
    {
        $this->authorize('reports.attendance.export');

        $actor = $request->user();
        $rows = $this->attendanceReports->exportRowsFor($actor, $request->validated());
        $this->attendanceReports->logExport($actor, count($rows));

        return $this->csvExports->stream(
            $actor,
            ReportCategory::Attendance,
            'attendance-report-'.now()->format('Y-m-d-His').'.csv',
            $this->attendanceReports->exportHeadersFor($actor),
            $rows,
        );
    }

    public function fees(FeeReportIndexRequest $request): View
    {
        $this->authorize('reports.fees.view');

        return view('reports.fees', $this->feeReports->reportFor(
            $request->user(),
            $request->validated(),
        ));
    }

    public function examinations(ExaminationReportIndexRequest $request): View
    {
        $this->authorize('reports.examinations.view');

        return view('reports.examinations', $this->examinationReports->reportFor(
            $request->user(),
            $request->validated(),
        ));
    }

    public function exportFees(FeeReportIndexRequest $request): StreamedResponse
    {
        $this->authorize('reports.fees.export');

        $actor = $request->user();
        $rows = $this->feeReports->exportRowsFor($actor, $request->validated());
        $this->feeReports->logExport($actor, count($rows));

        return $this->csvExports->stream(
            $actor,
            ReportCategory::Fees,
            'fee-report-'.now()->format('Y-m-d-His').'.csv',
            $this->feeReports->exportHeadersFor($actor),
            $rows,
        );
    }

    public function exportExaminations(ExaminationReportIndexRequest $request): StreamedResponse
    {
        $this->authorize('reports.examinations.export');

        $actor = $request->user();
        $rows = $this->examinationReports->exportRowsFor($actor, $request->validated());
        $this->examinationReports->logExport($actor, count($rows));

        return $this->csvExports->stream(
            $actor,
            ReportCategory::Examinations,
            'examination-report-'.now()->format('Y-m-d-His').'.csv',
            $this->examinationReports->exportHeadersFor($actor),
            $rows,
        );
    }
}
