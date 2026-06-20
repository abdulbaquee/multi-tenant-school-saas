<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('School Settings') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Manage the profile and operating preferences for :school.', ['school' => $school->name]) }}</p>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <nav id="settings-section-navigation" class="nav nav-pills flex-nowrap gap-1 mb-4 p-2 bg-white border rounded-2 shadow-sm settings-section-nav overflow-x-auto" aria-label="{{ __('Settings sections') }}">
        <a class="nav-link active" href="#general" data-settings-section="general" aria-current="location">{{ __('General') }}</a>
        <a class="nav-link" href="#academic" data-settings-section="academic">{{ __('Academic') }}</a>
        <a class="nav-link" href="#attendance" data-settings-section="attendance">{{ __('Attendance') }}</a>
        <a class="nav-link" href="#grading" data-settings-section="grading">{{ __('Grading') }}</a>
        <a class="nav-link" href="#logo" data-settings-section="logo">{{ __('Logo') }}</a>
    </nav>

    <form method="POST" action="{{ route('school-settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <p class="small text-body-secondary mb-4">
                    <span class="text-danger" aria-hidden="true">*</span> {{ __('Required fields') }}
                </p>

                <section id="general" class="scroll-mt-3">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-building text-primary" aria-hidden="true"></i>
                        <h2 class="h5 mb-0">{{ __('General Settings') }}</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="school_name" class="form-label">
                                {{ __('School Name') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
                            </label>
                            <input id="school_name" name="school_name" type="text" class="form-control @error('school_name') is-invalid @enderror" value="{{ old('school_name', $school->name) }}" maxlength="150" autocomplete="organization" required>
                            @error('school_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="school_code" class="form-label">{{ __('School Code') }}</label>
                            <input id="school_code" type="text" class="form-control" value="{{ $school->code }}" disabled>
                            <div class="form-text">{{ __('Managed by the platform administrator.') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label for="school_email" class="form-label">
                                {{ __('Official Email') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
                            </label>
                            <input id="school_email" name="school_email" type="email" class="form-control @error('school_email') is-invalid @enderror" value="{{ old('school_email', $school->email) }}" maxlength="150" autocomplete="email" required>
                            @error('school_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">{{ __('Official Phone') }}</label>
                            <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $school->phone) }}" maxlength="30" autocomplete="tel">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="principal_name" class="form-label">{{ __('Principal Name') }}</label>
                            <input id="principal_name" name="principal_name" type="text" class="form-control @error('principal_name') is-invalid @enderror" value="{{ old('principal_name', $school->principal_name) }}" maxlength="150" autocomplete="name">
                            @error('principal_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="website" class="form-label">{{ __('Website') }}</label>
                            <input id="website" name="website" type="url" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $school->website) }}" maxlength="255" placeholder="https://example.edu" autocomplete="url">
                            @error('website')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">{{ __('Address') }}</label>
                            <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3" autocomplete="street-address">{{ old('address', $school->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <label for="city" class="form-label">{{ __('City') }}</label>
                            <input id="city" name="city" type="text" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $school->city) }}" maxlength="100" autocomplete="address-level2">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <label for="state" class="form-label">{{ __('State') }}</label>
                            <input id="state" name="state" type="text" class="form-control @error('state') is-invalid @enderror" value="{{ old('state', $school->state) }}" maxlength="100" autocomplete="address-level1">
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <label for="country" class="form-label">
                                {{ __('Country') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
                            </label>
                            <input id="country" name="country" type="text" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', $school->country) }}" maxlength="100" autocomplete="country-name" required>
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <label for="postal_code" class="form-label">{{ __('Postal Code') }}</label>
                            <input id="postal_code" name="postal_code" type="text" class="form-control @error('postal_code') is-invalid @enderror" value="{{ old('postal_code', $school->postal_code) }}" maxlength="20" autocomplete="postal-code">
                            @error('postal_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <section id="academic" class="scroll-mt-3">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-calendar3 text-primary" aria-hidden="true"></i>
                        <h2 class="h5 mb-0">{{ __('Academic Settings') }}</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="timezone" class="form-label">
                                {{ __('Timezone') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
                            </label>
                            <select id="timezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror" required>
                                @foreach ($timezones as $timezone)
                                    <option value="{{ $timezone }}" @selected(old('timezone', $settings->timezone) === $timezone)>{{ $timezone }}</option>
                                @endforeach
                            </select>
                            @error('timezone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="currency" class="form-label">
                                {{ __('Currency Code') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
                            </label>
                            <input id="currency" name="currency" type="text" class="form-control text-uppercase @error('currency') is-invalid @enderror" value="{{ old('currency', $settings->currency) }}" minlength="3" maxlength="3" placeholder="INR" required>
                            @error('currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="academic_year_start_month" class="form-label">
                                {{ __('Academic Year Starts') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
                            </label>
                            <select id="academic_year_start_month" name="academic_year_start_month" class="form-select @error('academic_year_start_month') is-invalid @enderror" required>
                                @foreach (range(1, 12) as $month)
                                    <option value="{{ $month }}" @selected((int) old('academic_year_start_month', $settings->academic_year_start_month) === $month)>{{ \Carbon\Carbon::create()->month($month)->format('F') }}</option>
                                @endforeach
                            </select>
                            @error('academic_year_start_month')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <section id="attendance" class="scroll-mt-3">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-clock text-primary" aria-hidden="true"></i>
                        <h2 class="h5 mb-0">{{ __('Attendance Settings') }}</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="attendance_start_time" class="form-label">{{ __('Attendance Start Time') }}</label>
                            <input id="attendance_start_time" name="attendance_start_time" type="time" class="form-control @error('attendance_start_time') is-invalid @enderror" value="{{ old('attendance_start_time', $settings->attendance_start_time ? substr($settings->attendance_start_time, 0, 5) : '') }}">
                            @error('attendance_start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('Leave blank if the school does not use a fixed attendance start time.') }}</div>
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <section id="grading" class="scroll-mt-3">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-bar-chart text-primary" aria-hidden="true"></i>
                        <h2 class="h5 mb-0">{{ __('Grading Settings') }}</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="grading_system" class="form-label">
                                {{ __('Grading System') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
                            </label>
                            <select id="grading_system" name="grading_system" class="form-select @error('grading_system') is-invalid @enderror" required>
                                <option value="percentage" @selected(old('grading_system', $settings->grading_system) === 'percentage')>{{ __('Percentage') }}</option>
                                <option value="letter" @selected(old('grading_system', $settings->grading_system) === 'letter')>{{ __('Letter Grade') }}</option>
                                <option value="gpa" @selected(old('grading_system', $settings->grading_system) === 'gpa')>{{ __('GPA') }}</option>
                            </select>
                            @error('grading_system')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <section id="logo" class="scroll-mt-3">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-image text-primary" aria-hidden="true"></i>
                        <h2 class="h5 mb-0">{{ __('Logo Management') }}</h2>
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-md-auto">
                            <div class="school-logo-preview border rounded-2 bg-light d-flex align-items-center justify-content-center overflow-hidden">
                                @if ($settings->logo_path)
                                    <img class="w-100 h-100 object-fit-contain" src="{{ url('storage/'.$settings->logo_path) }}" alt="{{ __(':school logo', ['school' => $school->name]) }}">
                                @else
                                    <i class="bi bi-building fs-1 text-body-secondary" aria-hidden="true"></i>
                                    <span class="visually-hidden">{{ __('No school logo uploaded') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md">
                            <label for="logo_upload" class="form-label">{{ __('Upload Logo') }}</label>
                            <input id="logo_upload" name="logo" type="file" class="form-control @error('logo') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('JPG, JPEG, PNG, or WebP. Maximum 2 MB.') }}</div>

                            @if ($settings->logo_path)
                                <div class="form-check mt-3">
                                    <input name="remove_logo" type="hidden" value="0">
                                    <input id="remove_logo" name="remove_logo" type="checkbox" class="form-check-input @error('remove_logo') is-invalid @enderror" value="1" @checked(old('remove_logo'))>
                                    <label class="form-check-label" for="remove_logo">{{ __('Remove current logo') }}</label>
                                    @error('remove_logo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <div class="border-top mt-4 pt-4">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Save Settings') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        (() => {
            const navigation = document.getElementById('settings-section-navigation');

            if (!navigation) {
                return;
            }

            const links = [...navigation.querySelectorAll('[data-settings-section]')];
            const sections = links
                .map((link) => document.getElementById(link.dataset.settingsSection))
                .filter(Boolean);

            const activate = (sectionId) => {
                links.forEach((link) => {
                    const selected = link.dataset.settingsSection === sectionId;

                    link.classList.toggle('active', selected);

                    if (selected) {
                        link.setAttribute('aria-current', 'location');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });
            };

            const syncToViewport = () => {
                const activationLine = navigation.getBoundingClientRect().bottom + 48;
                let activeSection = sections[0]?.id;

                sections.forEach((section) => {
                    if (section.getBoundingClientRect().top <= activationLine) {
                        activeSection = section.id;
                    }
                });

                if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
                    activeSection = sections.at(-1)?.id;
                }

                if (activeSection) {
                    activate(activeSection);
                }
            };

            links.forEach((link) => {
                link.addEventListener('click', () => activate(link.dataset.settingsSection));
            });

            window.addEventListener('scroll', syncToViewport, { passive: true });
            window.addEventListener('resize', syncToViewport);
            syncToViewport();
        })();
    </script>
</x-app-layout>
