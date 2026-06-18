@extends('layouts.app', ['title' => 'Reports'])

@section('content')
<div class="row g-3">
    <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><h6>Total Students</h6><h3>{{ $studentCount }}</h3></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><h6>Present Today</h6><h3>{{ $presentToday }}</h3></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><h6>Absent Today</h6><h3>{{ $absentToday }}</h3></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><h6>Fee Collection %</h6><h3>{{ $feeCollectionRate }}%</h3></div></div></div>
</div>
@endsection
