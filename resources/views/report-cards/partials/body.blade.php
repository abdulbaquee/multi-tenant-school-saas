<div class="card border-0 shadow-sm">
    <div class="card-body">
        @php($canViewFullReportCard = $canViewFullReportCard ?? true)
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h2 class="h4 mb-1">{{ __('Report Card') }}</h2>
                <p class="text-body-secondary mb-0">{{ $reportCard->exam->name }} · {{ $reportCard->exam->academicYear->name }}</p>
            </div>
            @if ($canViewFullReportCard)
                <div class="text-end">
                    <div class="fw-semibold">{{ $reportCard->percentage }}%</div>
                    <div class="small text-body-secondary">{{ $reportCard->gradeScale?->grade ?? '—' }} · {{ ucfirst($reportCard->result_status) }}</div>
                </div>
            @else
                <div class="text-end">
                    <span class="badge text-bg-info">{{ __('Assigned Subject View') }}</span>
                    <div class="small text-body-secondary mt-1">{{ __('Overall summary hidden') }}</div>
                </div>
            @endif
        </div>

        <dl class="row mb-4">
            <dt class="col-sm-4">{{ __('Admission Number') }}</dt><dd class="col-sm-8">{{ $reportCard->student->admission_no }}</dd>
            <dt class="col-sm-4">{{ __('Student Name') }}</dt><dd class="col-sm-8">{{ $reportCard->student->first_name }} {{ $reportCard->student->last_name }}</dd>
            <dt class="col-sm-4">{{ __('Class / Section') }}</dt><dd class="col-sm-8">{{ $reportCard->schoolClass->name }} / {{ $reportCard->section->name }}</dd>
            @if ($canViewFullReportCard)
                <dt class="col-sm-4">{{ __('Total Marks') }}</dt><dd class="col-sm-8">{{ $reportCard->marks_obtained }} / {{ $reportCard->total_marks }}</dd>
            @else
                <dt class="col-sm-4">{{ __('Scope') }}</dt><dd class="col-sm-8">{{ __('Assigned subject results only') }}</dd>
            @endif
            <dt class="col-sm-4">{{ __('Generated At') }}</dt><dd class="col-sm-8 mb-0">{{ $reportCard->generated_at?->format('Y-m-d H:i') ?? '—' }}</dd>
        </dl>

        <h3 class="h6 mb-3">{{ __('Subject Results') }}</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Subject') }}</th>
                        <th scope="col">{{ __('Marks') }}</th>
                        <th scope="col">{{ __('Grade') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subjectResults as $result)
                        <tr>
                            <td>{{ $result->subject->name }}</td>
                            <td>{{ $result->marks_obtained }} / {{ $result->examSubject->max_marks }}</td>
                            <td>{{ $result->gradeScale?->grade ?? '—' }}</td>
                            <td>{{ ucfirst($result->result_status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
