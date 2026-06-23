<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Exam Results Summary') }}</h1>
                <p class="text-body-secondary mb-0">{{ $exam->name }} · {{ $exam->academicYear->name }}</p>
            </div>
            @can('process', $exam)
                <button class="btn btn-success align-self-start" type="button" data-bs-toggle="modal" data-bs-target="#processResultsModal">
                    <i class="bi bi-calculator me-1" aria-hidden="true"></i>{{ __('Process Results') }}
                </button>
                @include('academic.partials.confirmation-modal', [
                    'modalId' => 'processResultsModal',
                    'title' => __('Process Results'),
                    'message' => __('Recalculate grades for all entered results in this exam.'),
                    'action' => route('exam-results.process', $exam),
                    'buttonLabel' => __('Process Results'),
                    'buttonClass' => 'btn-success',
                ])
            @endcan
            @can('generate', $exam)
                <button class="btn btn-outline-primary align-self-start" type="button" data-bs-toggle="modal" data-bs-target="#generateReportCardsModal">
                    <i class="bi bi-card-checklist me-1" aria-hidden="true"></i>{{ __('Generate Report Cards') }}
                </button>
                @include('academic.partials.confirmation-modal', [
                    'modalId' => 'generateReportCardsModal',
                    'title' => __('Generate Report Cards'),
                    'message' => __('Generate or refresh report cards for students with complete exam results.'),
                    'action' => route('report-cards.generate', $exam),
                    'buttonLabel' => __('Generate'),
                    'buttonClass' => 'btn-primary',
                ])
            @elsecan('viewAny', \App\Models\Exam::class)
                <p class="small text-body-secondary align-self-start mb-0">
                    {{ __('Publish the exam and enter marks before generating report cards.') }}
                </p>
            @endcan
        </div>
    </x-slot>

    @include('examinations.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-body-secondary small">{{ __('Total Results') }}</div><div class="h4 mb-0">{{ $summary['total'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-body-secondary small">{{ __('Pass') }}</div><div class="h4 mb-0 text-success">{{ $summary['pass'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-body-secondary small">{{ __('Fail') }}</div><div class="h4 mb-0 text-danger">{{ $summary['fail'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-body-secondary small">{{ __('Absent') }}</div><div class="h4 mb-0">{{ $summary['absent'] }}</div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">{{ __('Exam Subjects') }}</h2>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Class') }}</th>
                        <th scope="col">{{ __('Subject') }}</th>
                        <th scope="col">{{ __('Max Marks') }}</th>
                        <th scope="col">{{ __('Passing Marks') }}</th>
                        <th scope="col">{{ __('Results Entered') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subjects as $examSubject)
                        <tr>
                            <td>{{ $examSubject->schoolClass->name }}</td>
                            <td>{{ $examSubject->subject->name }}</td>
                            <td>{{ $examSubject->max_marks }}</td>
                            <td>{{ $examSubject->passing_marks }}</td>
                            <td>{{ $examSubject->exam_results_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">{{ __('No exam subjects assigned.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex flex-wrap gap-3">
        <a class="btn btn-link px-0" href="{{ route('exam-results.index', ['exam_id' => $exam->id]) }}">{{ __('View all results for this exam') }}</a>
        <a class="btn btn-link px-0" href="{{ route('report-cards.index', ['exam_id' => $exam->id]) }}">{{ __('View report cards for this exam') }}</a>
    </div>
</x-app-layout>
