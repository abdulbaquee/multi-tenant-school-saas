@extends('layouts.app', ['title' => 'Attendance'])

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card"><div class="card-header">Mark Attendance</div><div class="card-body">
            <form method="POST" action="{{ route('attendance.store', $school) }}" class="row g-2">
                @csrf
                <div class="col-12"><label class="form-label">Student</label><select class="form-select" name="student_id" required>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Date</label><input class="form-control" type="date" name="attendance_date" value="{{ now()->toDateString() }}" required></div>
                <div class="col-12"><label class="form-label">Status</label><select class="form-select" name="status"><option>present</option><option>absent</option><option>late</option><option>excused</option></select></div>
                <div class="col-12"><input class="form-control" name="remarks" placeholder="Remarks"></div>
                <div class="col-12"><button class="btn btn-primary w-100">Save</button></div>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="card-header">Today's Attendance</div>
            <ul class="list-group list-group-flush">
                @forelse($attendance as $entry)
                    <li class="list-group-item">{{ $entry->student?->first_name }} {{ $entry->student?->last_name }} - <strong>{{ $entry->status }}</strong></li>
                @empty
                    <li class="list-group-item text-muted">No attendance records for today.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
