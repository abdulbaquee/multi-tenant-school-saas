<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'School SaaS') }}</title>

        @include('layouts.partials.critical-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="auth-shell d-flex align-items-center py-5">
            <div class="container">
                <div class="auth-card card border-0 shadow-sm mx-auto">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <a href="/" class="d-inline-flex align-items-center justify-content-center brand-mark rounded-circle bg-primary text-white text-decoration-none mb-3">
                                <i class="bi bi-mortarboard fs-4"></i>
                            </a>
                            <h1 class="h4 mb-1">{{ config('app.name', 'School SaaS') }}</h1>
                            <p class="text-body-secondary mb-0">Secure school administration access</p>
                        </div>

                        {{ $slot }}
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
