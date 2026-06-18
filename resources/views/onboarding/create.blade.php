@extends('layouts.app', ['title' => 'School Onboarding'])

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">Onboard a New School</div>
            <div class="card-body">
                <form method="POST" action="{{ route('onboarding.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-6"><label class="form-label">School Name</label><input class="form-control" name="school_name" required></div>
                    <div class="col-md-6"><label class="form-label">School Slug</label><input class="form-control" name="school_slug" required></div>
                    <div class="col-md-6"><label class="form-label">Admin Name</label><input class="form-control" name="admin_name" required></div>
                    <div class="col-md-6"><label class="form-label">Admin Email</label><input class="form-control" type="email" name="admin_email" required></div>
                    <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
                    <div class="col-md-6"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="password_confirmation" required></div>
                    <div class="col-12"><button class="btn btn-primary">Create Tenant School</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
