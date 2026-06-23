<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Exams') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('exams.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Edit') }}</x-slot>
    <x-slot name="header">
        <div><h1 class="h3 mb-1">{{ __('Edit Exam') }}</h1><p class="text-body-secondary mb-0">{{ __('Update scheduled exam dates and type. Scope fields remain locked.') }}</p></div>
    </x-slot>

    @include('examinations.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('exams.update', $exam) }}">
                @csrf
                @method('PUT')
                @include('exams.partials.form', ['exam' => $exam, 'academicYears' => collect(), 'terms' => collect()])
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Update Exam') }}</button>
                    <a class="btn btn-link" href="{{ route('exams.show', $exam) }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
