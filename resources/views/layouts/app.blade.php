<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'School SaaS' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <span class="navbar-brand">{{ $tenantSchool->name ?? 'School SaaS' }}</span>
        @isset($tenantSchool)
            <div class="navbar-nav gap-2">
                <a class="nav-link" href="{{ route('dashboard', $tenantSchool) }}">Dashboard</a>
                <a class="nav-link" href="{{ route('students.index', $tenantSchool) }}">Students</a>
                <a class="nav-link" href="{{ route('attendance.index', $tenantSchool) }}">Attendance</a>
                <a class="nav-link" href="{{ route('fees.index', $tenantSchool) }}">Fees</a>
                <a class="nav-link" href="{{ route('exams.index', $tenantSchool) }}">Exams</a>
                <a class="nav-link" href="{{ route('reports.index', $tenantSchool) }}">Reports</a>
            </div>
        @endisset
    </div>
</nav>
<main class="container pb-5">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
