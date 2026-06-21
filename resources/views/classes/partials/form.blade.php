<div class="row g-3">
    <div class="col-12"><p class="small text-body-secondary mb-0"><span class="text-danger" aria-hidden="true">*</span> {{ __('Required fields') }}</p></div>
    <div class="col-md-7">
        <label for="name" class="form-label">{{ __('Class Name') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $schoolClass->name ?? '') }}" maxlength="80" placeholder="Class 8" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="code" class="form-label">{{ __('Class Code') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="code" name="code" type="text" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code', $schoolClass->code ?? '') }}" maxlength="30" placeholder="VIII" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label for="sort_order" class="form-label">{{ __('Sort Order') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="sort_order" name="sort_order" type="number" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $schoolClass->sort_order ?? 0) }}" min="0" max="65535" required>
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
