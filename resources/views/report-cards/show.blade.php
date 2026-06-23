<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Report Cards') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('report-cards.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $reportCard->student->admission_no }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Report Card') }}</h1>
                <p class="text-body-secondary mb-0">{{ $reportCard->exam->name }} · {{ $reportCard->student->admission_no }}</p>
            </div>
            <a class="btn btn-outline-primary align-self-start" href="{{ route('report-cards.print', $reportCard) }}" target="_blank" rel="noopener">
                <i class="bi bi-printer me-1" aria-hidden="true"></i>{{ __('Print Report Card') }}
            </a>
        </div>
    </x-slot>

    @include('examinations.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    @include('report-cards.partials.body')
</x-app-layout>
