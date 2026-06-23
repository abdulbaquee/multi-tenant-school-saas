<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Student Fees') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('student-fees.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Assign') }}</x-slot>
    <x-slot name="header">
        <div><h1 class="h3 mb-1">{{ __('Assign Student Fee') }}</h1><p class="text-body-secondary mb-0">{{ __('Assign an active Fee Structure to an eligible active Student Enrollment.') }}</p></div>
    </x-slot>

    @include('fees.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('student-fees.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="fee_structure_id">{{ __('Fee Structure') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <select id="fee_structure_id" name="fee_structure_id" class="form-select @error('fee_structure_id') is-invalid @enderror" required onchange="window.location='{{ route('student-fees.create') }}?fee_structure_id=' + this.value">
                            <option value="">{{ __('Select a structure') }}</option>
                            @foreach ($structures as $structure)
                                <option value="{{ $structure->id }}" @selected((int) old('fee_structure_id', request('fee_structure_id')) === $structure->id)>
                                    {{ $structure->feeCategory->name }} · {{ $structure->schoolClass->name }} · {{ number_format((float) $structure->amount, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('fee_structure_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="student_id">{{ __('Student') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <select id="student_id" name="student_id" class="form-select @error('student_id') is-invalid @enderror" required @if ($structures->isEmpty()) disabled @endif>
                            <option value="">{{ __('Select a student') }}</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected((int) old('student_id') === $student->id)>{{ $student->admission_no }} · {{ $student->first_name }} {{ $student->last_name }}</option>
                            @endforeach
                        </select>
                        @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="discount_amount">{{ __('Discount Amount') }}</label>
                        <input id="discount_amount" name="discount_amount" type="number" step="0.01" min="0" class="form-control @error('discount_amount') is-invalid @enderror" value="{{ old('discount_amount', '0.00') }}">
                        @error('discount_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ __('Payable and balance amounts are calculated on the server.') }}</div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit" @disabled($structures->isEmpty())><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Assign Student Fee') }}</button>
                    <a class="btn btn-link" href="{{ route('student-fees.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
