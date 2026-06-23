<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Exam Subjects') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('exam-subjects.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $examSubject->subject->name }}</x-slot>
    <x-slot name="header">
        <div><h1 class="h3 mb-1">{{ __('Exam Subject Assignment') }}</h1><p class="text-body-secondary mb-0">{{ __('Assigned class and subject scope for the selected exam.') }}</p></div>
    </x-slot>

    @include('examinations.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">{{ __('Exam') }}</dt><dd class="col-sm-8"><a href="{{ route('exams.show', $examSubject->exam) }}">{{ $examSubject->exam->name }}</a></dd>
                <dt class="col-sm-4">{{ __('Class') }}</dt><dd class="col-sm-8">{{ $examSubject->schoolClass->name }}</dd>
                <dt class="col-sm-4">{{ __('Subject') }}</dt><dd class="col-sm-8">{{ $examSubject->subject->name }}</dd>
                <dt class="col-sm-4">{{ __('Exam Date') }}</dt><dd class="col-sm-8">{{ $examSubject->exam_date?->format('M j, Y') ?: '—' }}</dd>
                <dt class="col-sm-4">{{ __('Maximum Marks') }}</dt><dd class="col-sm-8">{{ number_format((float) $examSubject->max_marks, 2) }}</dd>
                <dt class="col-sm-4">{{ __('Passing Marks') }}</dt><dd class="col-sm-8">{{ number_format((float) $examSubject->passing_marks, 2) }}</dd>
                <dt class="col-sm-4">{{ __('Retained Results') }}</dt><dd class="col-sm-8 mb-0">{{ $examSubject->exam_results_count }}</dd>
            </dl>
        </div>
    </div>
</x-app-layout>
