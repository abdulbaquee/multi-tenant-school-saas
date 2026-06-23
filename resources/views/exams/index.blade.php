<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Exams') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Manage tenant exam setup and lifecycle state.') }}</p>
            </div>
            @can('create', \App\Models\Exam::class)
                <a class="btn btn-primary align-self-start" href="{{ route('exams.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New Exam') }}</a>
            @endcan
        </div>
    </x-slot>

    @include('examinations.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('exams.index') }}">
        <div class="col-md-7">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Exam name') }}">
        </div>
        <div class="col-sm-8 col-md-3">
            <label class="form-label" for="state">{{ __('Exam State') }}</label>
            <select id="state" name="state" class="form-select">
                <option value="">{{ __('All states') }}</option>
                @foreach (['scheduled', 'ongoing', 'completed', 'cancelled'] as $state)
                    <option value="{{ $state }}" @selected(request('state') === $state)>{{ ucfirst($state) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('exams.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Exam') }}</th>
                        <th scope="col">{{ __('Academic Year') }}</th>
                        <th scope="col">{{ __('Subjects') }}</th>
                        <th scope="col">{{ __('State') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exams as $exam)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $exam->name }}</div>
                                <div class="small text-body-secondary">{{ ucfirst(str_replace('_', ' ', $exam->exam_type)) }} · {{ $exam->start_date?->format('M j, Y') }} – {{ $exam->end_date?->format('M j, Y') }}</div>
                            </td>
                            <td>{{ $exam->academicYear->name }}</td>
                            <td>{{ $exam->exam_subjects_count }}</td>
                            <td><span class="badge text-bg-secondary">{{ ucfirst($exam->status) }}</span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('exams.show', $exam) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                                @can('update', $exam)<a class="btn btn-sm btn-outline-primary" href="{{ route('exams.edit', $exam) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>@endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">{{ __('No exams match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $exams->links() }}</div>
</x-app-layout>
