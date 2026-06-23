<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Exam Subjects') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('View exam subject assignments for classes and subjects.') }}</p>
            </div>
            @can('create', \App\Models\ExamSubject::class)
                <a class="btn btn-primary align-self-start" href="{{ route('exam-subjects.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('Assign Subject') }}</a>
            @endcan
        </div>
    </x-slot>

    @include('examinations.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('exam-subjects.index') }}">
        <div class="col-md-10">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Exam, class, or subject') }}">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary w-100" type="submit">{{ __('Filter') }}</button>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Exam') }}</th>
                        <th scope="col">{{ __('Class') }}</th>
                        <th scope="col">{{ __('Subject') }}</th>
                        <th scope="col">{{ __('Marks') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($examSubjects as $examSubject)
                        <tr>
                            <td>{{ $examSubject->exam->name }}</td>
                            <td>{{ $examSubject->schoolClass->name }}</td>
                            <td>{{ $examSubject->subject->name }}</td>
                            <td>{{ number_format((float) $examSubject->max_marks, 2) }} / {{ number_format((float) $examSubject->passing_marks, 2) }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('exam-subjects.show', $examSubject) }}">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">{{ __('No exam subject assignments match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $examSubjects->links() }}</div>
</x-app-layout>
