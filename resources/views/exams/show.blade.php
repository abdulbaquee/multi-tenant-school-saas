<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Exams') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('exams.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $exam->name }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h1 class="h3 mb-0">{{ $exam->name }}</h1>
                    <span class="badge text-bg-secondary">{{ ucfirst($exam->status) }}</span>
                </div>
                <p class="text-body-secondary mb-0">{{ __('Exam setup record for the active school.') }}</p>
            </div>
            @can('update', $exam)<a class="btn btn-outline-primary align-self-start" href="{{ route('exams.edit', $exam) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>@endcan
        </div>
    </x-slot>

    @include('examinations.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">{{ __('Academic Year') }}</dt><dd class="col-sm-8">{{ $exam->academicYear->name }}</dd>
                <dt class="col-sm-4">{{ __('Academic Term') }}</dt><dd class="col-sm-8">{{ $exam->academicTerm?->name ?: '—' }}</dd>
                <dt class="col-sm-4">{{ __('Exam Type') }}</dt><dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $exam->exam_type)) }}</dd>
                <dt class="col-sm-4">{{ __('Dates') }}</dt><dd class="col-sm-8">{{ $exam->start_date?->format('M j, Y') }} – {{ $exam->end_date?->format('M j, Y') }}</dd>
                <dt class="col-sm-4">{{ __('Assigned Subjects') }}</dt><dd class="col-sm-8">{{ $exam->exam_subjects_count }}</dd>
                <dt class="col-sm-4">{{ __('Retained Results') }}</dt><dd class="col-sm-8 mb-0">{{ $exam->exam_results_count }}</dd>
            </dl>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        @can('create', \App\Models\ExamSubject::class)
            <a class="btn btn-outline-primary" href="{{ route('exam-subjects.create', ['exam_id' => $exam->id]) }}"><i class="bi bi-book me-1" aria-hidden="true"></i>{{ __('Assign Subject') }}</a>
        @endcan
        @can('publish', $exam)<button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#publishExamModal"><i class="bi bi-play-circle me-1" aria-hidden="true"></i>{{ __('Publish') }}</button>@endcan
        @can('complete', $exam)<button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#completeExamModal"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Complete') }}</button>@endcan
        @can('cancel', $exam)<button class="btn btn-outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#cancelExamModal"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>{{ __('Cancel') }}</button>@endcan
        @can('archive', $exam)<button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#archiveExamModal"><i class="bi bi-archive me-1" aria-hidden="true"></i>{{ __('Archive') }}</button>@endcan
    </div>

    @can('publish', $exam)@include('academic.partials.confirmation-modal', ['modalId' => 'publishExamModal', 'title' => __('Publish Exam'), 'message' => __('This opens the exam for subject assignment and marks entry.'), 'action' => route('exams.publish', $exam), 'buttonLabel' => __('Publish'), 'buttonClass' => 'btn-success'])@endcan
    @can('complete', $exam)@include('academic.partials.confirmation-modal', ['modalId' => 'completeExamModal', 'title' => __('Complete Exam'), 'message' => __('This marks the exam as completed after operational processing.'), 'action' => route('exams.complete', $exam), 'buttonLabel' => __('Complete'), 'buttonClass' => 'btn-success'])@endcan
    @can('cancel', $exam)@include('academic.partials.confirmation-modal', ['modalId' => 'cancelExamModal', 'title' => __('Cancel Exam'), 'message' => __('Cancelled exams remain retained and cannot accept new assignments.'), 'action' => route('exams.cancel', $exam), 'buttonLabel' => __('Cancel Exam'), 'buttonClass' => 'btn-warning'])@endcan
    @can('archive', $exam)@include('academic.partials.confirmation-modal', ['modalId' => 'archiveExamModal', 'title' => __('Archive Exam'), 'message' => __('Archive only when no retained results or report cards exist.'), 'action' => route('exams.archive', $exam), 'buttonLabel' => __('Archive'), 'buttonClass' => 'btn-danger'])@endcan
</x-app-layout>
