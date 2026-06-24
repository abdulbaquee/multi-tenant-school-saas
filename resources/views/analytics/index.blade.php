<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ $title }}</h1>
            <p class="text-body-secondary mb-0">{{ $description }}</p>
        </div>
    </x-slot>

    @push('head')
        @vite(['resources/js/analytics.js'])
    @endpush

    <section aria-label="{{ __('Analytics charts') }}">
        <div
            id="analytics-charts"
            class="row g-3 g-xl-4"
            data-charts='@json($charts)'
        >
            @foreach ($charts as $index => $chart)
                <div class="col-12 col-lg-6">
                    <article class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h6 mb-3">{{ $chart['title'] }}</h2>
                            <div class="analytics-chart-canvas">
                                <canvas id="analytics-chart-{{ $index }}" aria-label="{{ $chart['title'] }}"></canvas>
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
