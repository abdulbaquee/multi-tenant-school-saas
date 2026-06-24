<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ $title }}</h1>
            <p class="text-body-secondary mb-0">{{ $description }}</p>
        </div>
    </x-slot>

    <section aria-label="{{ __('Report categories') }}">
        <div class="row g-3 g-xl-4">
            @foreach ($categories as $category)
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column gap-3">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div>
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-2 bg-primary-subtle text-primary p-2 mb-3">
                                        <i class="bi {{ $category['icon'] }}" aria-hidden="true"></i>
                                    </div>
                                    <h2 class="h5 mb-2">{{ $category['label'] }}</h2>
                                    <p class="text-body-secondary mb-0">{{ $category['description'] }}</p>
                                </div>
                            </div>

                            <div class="mt-auto d-flex flex-wrap gap-2">
                                @if ($category['route'])
                                    <a class="btn btn-primary btn-sm" href="{{ route($category['route']) }}">
                                        <i class="bi bi-bar-chart-line me-1" aria-hidden="true"></i>{{ __('Open report') }}
                                    </a>
                                @else
                                    <span class="badge text-bg-light border">{{ __('Coming in next reporting prompt') }}</span>
                                @endif

                                @if ($category['can_export'])
                                    <span class="badge text-bg-secondary align-self-center">{{ __('CSV export enabled') }}</span>
                                @endif
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>

        @if ($categories === [])
            <div class="alert alert-secondary mb-0" role="status">
                {{ __('No report categories are available for your role.') }}
            </div>
        @endif
    </section>
</x-app-layout>
