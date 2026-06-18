@extends('layouts.app', ['title' => 'Exams & Grading'])

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-header">Create Examination</div><div class="card-body">
            <form method="POST" action="{{ route('exams.store', $school) }}" class="row g-2">
                @csrf
                <div class="col-12"><input class="form-control" name="name" placeholder="Exam name" required></div>
                <div class="col-12"><input class="form-control" name="class_name" placeholder="Class"></div>
                <div class="col-12"><input class="form-control" type="date" name="exam_date" required></div>
                <div class="col-12"><button class="btn btn-primary w-100">Create Exam</button></div>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        @forelse($exams as $exam)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ $exam->name }} ({{ $exam->exam_date }})</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('exams.results.store', [$school, $exam]) }}" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-3"><select class="form-select" name="student_id" required>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }}</option>@endforeach</select></div>
                        <div class="col-md-3"><input class="form-control" name="subject" placeholder="Subject" required></div>
                        <div class="col-md-2"><input class="form-control" type="number" step="0.01" min="0" name="marks_obtained" placeholder="Scored" required></div>
                        <div class="col-md-2"><input class="form-control" type="number" step="0.01" min="1" name="marks_total" placeholder="Total" required></div>
                        <div class="col-md-2"><button class="btn btn-outline-primary w-100">Add</button></div>
                    </form>
                    <ul class="list-group list-group-flush">
                        @forelse($exam->results as $result)
                            <li class="list-group-item">{{ $result->student?->first_name }} {{ $result->student?->last_name }} · {{ $result->subject }} · {{ $result->marks_obtained }}/{{ $result->marks_total }} · Grade {{ $result->grade }}</li>
                        @empty
                            <li class="list-group-item text-muted">No results yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @empty
            <div class="alert alert-secondary">No examinations yet.</div>
        @endforelse
    </div>
</div>
@endsection
