<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function index(School $school)
    {
        return view('fees.index', [
            'school' => $school,
            'students' => Student::orderBy('first_name')->get(),
            'invoices' => FeeInvoice::with('student')->latest()->paginate(20),
        ]);
    }

    public function store(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ]);

        FeeInvoice::create($validated + ['school_id' => $school->id, 'status' => 'unpaid']);

        return redirect()->route('fees.index', $school)->with('status', 'Fee invoice created.');
    }

    public function markPaid(School $school, FeeInvoice $invoice): RedirectResponse
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return redirect()->route('fees.index', $school)->with('status', 'Invoice marked paid.');
    }
}
