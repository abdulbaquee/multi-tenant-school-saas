<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolSettingUpdateRequest;
use App\Models\SchoolSetting;
use App\Services\SchoolSettingService;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolSettingController extends Controller
{
    public function __construct(private readonly SchoolSettingService $schoolSettings) {}

    public function edit(Request $request): View
    {
        $this->authorize('viewAny', SchoolSetting::class);

        $settings = $this->schoolSettings->for($request->user());
        $school = $request->user()->school()->firstOrFail();
        $timezones = DateTimeZone::listIdentifiers();

        return view('school-settings.edit', compact('school', 'settings', 'timezones'));
    }

    public function update(SchoolSettingUpdateRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', SchoolSetting::class);

        $settings = $this->schoolSettings->for($request->user());
        $this->authorize('update', $settings);
        $this->schoolSettings->update($settings, $request->validated(), $request->user());

        return redirect()
            ->route('school-settings.edit')
            ->with('status', 'School settings updated successfully.');
    }
}
