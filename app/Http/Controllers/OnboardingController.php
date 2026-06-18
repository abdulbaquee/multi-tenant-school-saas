<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OnboardingController extends Controller
{
    public function create()
    {
        return view('onboarding.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_slug' => ['required', 'string', 'alpha_dash', 'max:120', 'unique:schools,slug'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $school = DB::transaction(function () use ($validated) {
            $school = School::create([
                'name' => $validated['school_name'],
                'slug' => $validated['school_slug'],
            ]);

            User::create([
                'school_id' => $school->id,
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['password']),
                'role' => 'school_admin',
            ]);

            return $school;
        });

        return redirect()->route('dashboard', $school)->with('status', 'School onboarded successfully.');
    }
}
