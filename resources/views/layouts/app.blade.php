<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'School Portal') }}</title>

        @include('layouts.partials.critical-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body>
        <div class="d-lg-flex min-vh-100">
            @include('layouts.sidebar')

            <div class="app-main d-flex flex-column flex-grow-1 min-vh-100">
                @include('layouts.navigation')

                <div class="app-main-body">
                    @isset($header)
                        <header class="page-header bg-white border-bottom">
                            <div class="app-content py-3 py-lg-4">
                                <nav
                                    aria-label="{{ __('Breadcrumb') }}"
                                    @class([
                                        'page-header-breadcrumb',
                                        'page-header-breadcrumb--placeholder' => ! isset($breadcrumb),
                                    ])
                                >
                                    @isset($breadcrumb)
                                        <ol class="breadcrumb small mb-3">
                                            @isset($breadcrumbParent)
                                                <li class="breadcrumb-item">
                                                    <a href="{{ $breadcrumbParentUrl }}">{{ $breadcrumbParent }}</a>
                                                </li>
                                            @else
                                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                                            @endisset
                                            <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb }}</li>
                                        </ol>
                                    @else
                                        <ol class="breadcrumb small mb-3" aria-hidden="true">
                                            <li class="breadcrumb-item"><span>&nbsp;</span></li>
                                        </ol>
                                    @endisset
                                </nav>

                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <main class="app-content app-page-content py-4">
                        {{ $slot }}
                    </main>
                </div>

                <footer class="border-top bg-white">
                    <div class="app-content d-flex flex-column flex-sm-row justify-content-between gap-2 py-3 small text-body-secondary">
                        <span>&copy; {{ now()->year }} {{ config('app.name', 'School Portal') }}</span>
                        <span>{{ __('Multi-Tenant School Administration') }}</span>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>
