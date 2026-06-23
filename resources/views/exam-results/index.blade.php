<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Exam Results') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Review entered marks and result status within your authorized scope.') }}</p>
        </div>
    </x-slot>

    @include('examinations.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('exam-results.index') }}">
        <div class="col-md-5">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Student, exam, or subject') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="exam_id">{{ __('Exam ID') }}</label>
            <input id="exam_id" name="exam_id" type="number" min="1" class="form-control" value="{{ request('exam_id') }}">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>{{ __('Filter') }}</button>
        </div>
        <div class="col-md-2">
            <a class="btn btn-outline-secondary w-100" href="{{ route('exam-results.index') }}">{{ __('Clear') }}</a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Student') }}</th>
                        <th scope="col">{{ __('Exam') }}</th>
                        <th scope="col">{{ __('Subject') }}</th>
                        <th scope="col">{{ __('Marks') }}</th>
                        <th scope="col">{{ __('Grade') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Updated') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ trim($result->student->first_name.' '.$result->student->last_name) }}</div>
                                <div class="small text-body-secondary">{{ $result->student->admission_no }}</div>
                            </td>
                            <td>
                                <a href="{{ route('exam-results.show', $result->exam) }}">{{ $result->exam->name }}</a>
                            </td>
                            <td>{{ $result->subject->name }}</td>
                            <td>{{ $result->marks_obtained }}</td>
                            <td>{{ $result->gradeScale?->grade ?? '—' }}</td>
                            <td><span class="badge text-bg-secondary">{{ ucfirst($result->result_status) }}</span></td>
                            <td>{{ $result->updated_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No exam results found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($results->hasPages())
            <div class="card-footer bg-white">{{ $results->links() }}</div>
        @endif
    </div>
</x-app-layout>
