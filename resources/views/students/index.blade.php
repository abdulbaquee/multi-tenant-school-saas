@extends('layouts.app', ['title' => 'Students'])

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card"><div class="card-header">Add Student</div><div class="card-body">
            <form method="POST" action="{{ route('students.store', $school) }}" class="row g-2">
                @csrf
                <div class="col-12"><input class="form-control" name="admission_number" placeholder="Admission Number" required></div>
                <div class="col-6"><input class="form-control" name="first_name" placeholder="First Name" required></div>
                <div class="col-6"><input class="form-control" name="last_name" placeholder="Last Name" required></div>
                <div class="col-12"><input class="form-control" name="email" type="email" placeholder="Email"></div>
                <div class="col-6"><input class="form-control" name="class_name" placeholder="Class"></div>
                <div class="col-6"><input class="form-control" name="dob" type="date"></div>
                <div class="col-12"><button class="btn btn-primary w-100">Save</button></div>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="card-header">Student List</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Admission #</th><th>Name</th><th>Class</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($students as $student)
                        <tr><td>{{ $student->admission_number }}</td><td>{{ $student->first_name }} {{ $student->last_name }}</td><td>{{ $student->class_name }}</td><td>{{ $student->status }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No students yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $students->links() }}</div>
    </div>
</div>
@endsection
