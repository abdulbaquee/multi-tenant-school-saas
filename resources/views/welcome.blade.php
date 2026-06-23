<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'School SaaS') }}</title>

        @include('layouts.partials.critical-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="min-vh-100 d-flex align-items-center bg-light py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 p-md-5 text-center">
                                <div class="d-inline-flex align-items-center justify-content-center brand-mark rounded-circle bg-primary text-white mb-3">
                                    <i class="bi bi-mortarboard fs-4"></i>
                                </div>

                                <h1 class="h2 mb-3">{{ config('app.name', 'School SaaS') }}</h1>
                                <p class="lead text-body-secondary mb-4">
                                    Multi-tenant school administration foundation for secure authentication and user management.
                                </p>

                                @auth
                                    <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                        {{ __('Go to dashboard') }}
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-primary">
                                        {{ __('Log in') }}
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
