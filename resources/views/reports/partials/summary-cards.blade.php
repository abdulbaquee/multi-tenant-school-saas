@if (! empty($summary))
    <div class="row g-3 g-xl-4 mb-4">
        @foreach ($summary as $metric)
            <div class="col-12 col-md-4">
                <article class="metric-card h-100 bg-white border p-4">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <p class="small text-body-secondary mb-2">{{ $metric['label'] }}</p>
                            <p class="h3 mb-0 text-break">{{ $metric['value'] }}</p>
                        </div>
                        <span class="metric-icon d-inline-flex align-items-center justify-content-center rounded-2 bg-{{ $metric['tone'] }}-subtle text-{{ $metric['tone'] }}">
                            <i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i>
                        </span>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
@endif
