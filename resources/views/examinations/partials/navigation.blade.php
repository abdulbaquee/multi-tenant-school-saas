<nav class="nav nav-pills flex-wrap gap-2 mb-4" aria-label="{{ __('Examination navigation') }}">
    @can('viewAny', \App\Models\Exam::class)
        <a class="nav-link @if (request()->routeIs('exams.*')) active @endif" href="{{ route('exams.index') }}">
            <i class="bi bi-journal-text me-1" aria-hidden="true"></i>{{ __('Exams') }}
        </a>
    @endcan
    @can('viewAny', \App\Models\ExamSubject::class)
        <a class="nav-link @if (request()->routeIs('exam-subjects.*')) active @endif" href="{{ route('exam-subjects.index') }}">
            <i class="bi bi-book me-1" aria-hidden="true"></i>{{ __('Exam Subjects') }}
        </a>
    @endcan
</nav>
