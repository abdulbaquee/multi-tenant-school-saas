{{-- First-paint critical CSS + icon font preload (targets ~90-95% flicker reduction, not guaranteed zero). --}}
@php($bootstrapIconsFontUrl = Illuminate\Support\Facades\Vite::asset('node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff2'))

<link rel="preload" href="{{ $bootstrapIconsFontUrl }}" as="font" type="font/woff2" crossorigin>

<style>
    @font-face {
        font-display: block;
        font-family: bootstrap-icons;
        src: url("{{ $bootstrapIconsFontUrl }}") format("woff2");
    }

    :root {
        --school-primary: #2563eb;
        --school-surface: #f8fafc;
        --school-border: #e2e8f0;
        --school-sidebar-width: 260px;
        --school-topbar-height: 70px;
        --school-icon-slot-width: 1.25rem;
    }

    html {
        overflow-y: scroll;
        scrollbar-gutter: stable;
    }

    body {
        margin: 0;
        background: var(--school-surface);
        overflow-x: hidden;
    }

    .bi::before,
    [class^="bi-"]::before,
    [class*=" bi-"]::before {
        display: inline-block;
        font-family: bootstrap-icons !important;
        font-style: normal;
        font-weight: normal !important;
        font-variant: normal;
        line-height: 1;
        vertical-align: -.125em;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .bi-mortarboard::before { content: "\f6fe"; }
    .bi-speedometer2::before { content: "\f580"; }
    .bi-people::before { content: "\f4d0"; }
    .bi-shield-lock::before { content: "\f538"; }
    .bi-diagram-3::before { content: "\f2ee"; }
    .bi-person-vcard::before { content: "\f8c9"; }
    .bi-calendar2-check::before { content: "\f1f8"; }
    .bi-buildings::before { content: "\f87d"; }
    .bi-sliders::before { content: "\f56b"; }
    .bi-person::before { content: "\f4e1"; }
    .bi-list::before { content: "\f479"; }
    .bi-person-circle::before { content: "\f4d7"; }
    .bi-box-arrow-right::before { content: "\f1c3"; }

    .sidebar-link > .bi,
    .app-icon-button .bi,
    .app-account-button > .bi,
    .brand-mark .bi {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--school-icon-slot-width);
        flex: 0 0 var(--school-icon-slot-width);
        font-size: 1.125rem;
        line-height: 1;
        text-align: center;
    }

    .brand-mark {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
    }

    .app-main {
        min-width: 0;
    }

    .app-sidebar {
        width: var(--school-sidebar-width);
        --bs-offcanvas-width: var(--school-sidebar-width);
    }

    .app-topbar {
        min-height: var(--school-topbar-height);
        height: var(--school-topbar-height);
        z-index: 1020;
    }

    .app-sidebar-brand {
        min-height: var(--school-topbar-height);
        height: var(--school-topbar-height);
    }

    .app-icon-button {
        width: 44px;
        height: 44px;
        padding: 0;
    }

    .app-account-button {
        width: 160px;
        min-width: 160px;
        min-height: 44px;
    }

    .sidebar-link {
        display: flex;
        width: 100%;
        min-height: 44px;
        align-items: center;
        gap: 0.75rem;
    }

    .nav-link.sidebar-link {
        transition: none;
    }

    .navbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding: 0.5rem 0;
    }

    .sticky-top {
        position: sticky;
        top: 0;
    }

    .bg-white {
        background-color: #fff !important;
    }

    .border-bottom {
        border-bottom: 1px solid var(--school-border) !important;
    }

    .border-end {
        border-right: 1px solid var(--school-border) !important;
    }

    .btn {
        display: inline-block;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        text-align: center;
        text-decoration: none;
        vertical-align: middle;
        cursor: pointer;
        user-select: none;
        border: 1px solid transparent;
        border-radius: 0.375rem;
        background-color: transparent;
        transition: none;
    }

    .btn-outline-secondary {
        color: #6c757d;
        border-color: #6c757d;
    }

    .btn-primary {
        color: #fff;
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .auth-shell {
        min-height: 100vh;
    }

    .d-none { display: none !important; }
    .d-flex { display: flex !important; }
    .flex-column { flex-direction: column !important; }
    .flex-grow-1 { flex-grow: 1 !important; }
    .flex-shrink-0 { flex-shrink: 0 !important; }
    .align-items-center { align-items: center !important; }
    .justify-content-center { justify-content: center !important; }
    .min-vh-100 { min-height: 100vh !important; }
    .h-100 { height: 100% !important; }
    .ms-auto { margin-left: auto !important; }
    .gap-2 { gap: 0.5rem !important; }
    .gap-3 { gap: 1rem !important; }

    @media (min-width: 992px) {
        .d-lg-flex { display: flex !important; }
        .d-lg-none { display: none !important; }

        .app-sidebar.offcanvas-lg {
            position: sticky;
            top: 0;
            width: var(--school-sidebar-width) !important;
            height: 100vh !important;
            flex: 0 0 var(--school-sidebar-width);
            background: #fff !important;
            border-right: 1px solid var(--school-border) !important;
            transform: none !important;
            visibility: visible !important;
        }
    }
</style>
