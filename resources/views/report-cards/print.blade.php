<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Report Card') }} · {{ $reportCard->student->admission_no }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="bg-light py-4">
    <div class="container">
        <div class="no-print mb-3 d-flex gap-2">
            <button class="btn btn-primary" type="button" onclick="window.print()">{{ __('Print') }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('report-cards.show', $reportCard) }}">{{ __('Back') }}</a>
        </div>
        @include('report-cards.partials.body')
    </div>
</body>
</html>
