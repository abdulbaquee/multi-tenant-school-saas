<x-app-layout>
    @php($canViewFullReportCards = auth()->user()?->hasRoleCode(\App\Models\Role::SCHOOL_ADMIN) ?? false)
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Report Cards') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('View generated operational report cards within your authorized scope.') }}</p>
            </div>
            @if ($filterExam && auth()->user()->can('generate', $filterExam))
                <button class="btn btn-outline-primary align-self-start" type="button" data-bs-toggle="modal" data-bs-target="#generateReportCardsModal">
                    <i class="bi bi-card-checklist me-1" aria-hidden="true"></i>{{ __('Generate Report Cards') }}
                </button>
            @endif
        </div>
    </x-slot>

    @include('examinations.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if (session('warning'))<div class="alert alert-warning" role="alert">{{ session('warning') }}</div>@endif

    @can('viewAny', \App\Models\Exam::class)
        <div class="alert alert-info" role="status">
            {{ __('Generate report cards from an ongoing or completed exam: open the exam, use Results Summary, or filter by Exam ID below after marks are entered for every subject.') }}
        </div>
    @endcan

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('report-cards.index') }}">
        <div class="col-md-5">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Student or exam') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="exam_id">{{ __('Exam ID') }}</label>
            <input id="exam_id" name="exam_id" type="number" min="1" class="form-control" value="{{ request('exam_id') }}">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>{{ __('Filter') }}</button>
        </div>
        <div class="col-md-2">
            <a class="btn btn-outline-secondary w-100" href="{{ route('report-cards.index') }}">{{ __('Clear') }}</a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Student') }}</th>
                        <th scope="col">{{ __('Exam') }}</th>
                        <th scope="col">{{ __('Class') }}</th>
                        @if ($canViewFullReportCards)
                            <th scope="col">{{ __('Percentage') }}</th>
                            <th scope="col">{{ __('Grade') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                        @else
                            <th scope="col">{{ __('Scope') }}</th>
                        @endif
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportCards as $reportCard)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ trim($reportCard->student->first_name.' '.$reportCard->student->last_name) }}</div>
                                <div class="small text-body-secondary">{{ $reportCard->student->admission_no }}</div>
                            </td>
                            <td>{{ $reportCard->exam->name }}</td>
                            <td>{{ $reportCard->schoolClass->name }}</td>
                            @if ($canViewFullReportCards)
                                <td>{{ $reportCard->percentage }}%</td>
                                <td>{{ $reportCard->gradeScale?->grade ?? '—' }}</td>
                                <td><span class="badge text-bg-secondary">{{ ucfirst($reportCard->result_status) }}</span></td>
                            @else
                                <td><span class="badge text-bg-info">{{ __('Assigned subjects') }}</span></td>
                            @endif
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('report-cards.show', $reportCard) }}">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canViewFullReportCards ? 7 : 5 }}" class="text-center text-body-secondary py-4">{{ __('No report cards found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reportCards->hasPages())
            <div class="card-footer bg-white">{{ $reportCards->links() }}</div>
        @endif
    </div>

    @if ($filterExam && auth()->user()->can('generate', $filterExam))
        @include('academic.partials.confirmation-modal', [
            'modalId' => 'generateReportCardsModal',
            'title' => __('Generate Report Cards'),
            'message' => __('Generate or refresh report cards for students with complete exam results.'),
            'action' => route('report-cards.generate', $filterExam),
            'buttonLabel' => __('Generate'),
            'buttonClass' => 'btn-primary',
        ])
    @endif
</x-app-layout>
