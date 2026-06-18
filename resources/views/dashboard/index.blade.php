@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><h6>Students</h6><h3>{{ $studentsCount }}</h3></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><h6>Attendance Today</h6><h3>{{ $attendanceToday }}</h3></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><h6>Pending Fees</h6><h3>{{ number_format($pendingFees, 2) }}</h3></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><h6>Recent Grades</h6><h3>{{ $recentGrades->count() }}</h3></div></div></div>
</div>
<div class="card">
    <div class="card-header">Audit Trail</div>
    <ul class="list-group list-group-flush">
        @forelse ($recentAudits as $log)
            <li class="list-group-item small">{{ $log->created_at }} · {{ $log->action }}</li>
        @empty
            <li class="list-group-item text-muted">No audit activity yet.</li>
        @endforelse
    </ul>
</div>
@endsection
