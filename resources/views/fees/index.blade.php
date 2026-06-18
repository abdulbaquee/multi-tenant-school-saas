@extends('layouts.app', ['title' => 'Fees'])

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card"><div class="card-header">Create Invoice</div><div class="card-body">
            <form method="POST" action="{{ route('fees.store', $school) }}" class="row g-2">
                @csrf
                <div class="col-12"><select class="form-select" name="student_id" required>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }}</option>@endforeach</select></div>
                <div class="col-12"><input class="form-control" name="title" placeholder="Fee title" required></div>
                <div class="col-6"><input class="form-control" type="number" step="0.01" min="0" name="amount" placeholder="Amount" required></div>
                <div class="col-6"><input class="form-control" type="date" name="due_date" required></div>
                <div class="col-12"><button class="btn btn-primary w-100">Create</button></div>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="card-header">Invoices</div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Student</th><th>Title</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</td>
                        <td>{{ $invoice->title }}</td>
                        <td>{{ number_format((float) $invoice->amount, 2) }}</td>
                        <td>{{ $invoice->status }}</td>
                        <td>
                            @if($invoice->status !== 'paid')
                                <form method="POST" action="{{ route('fees.mark-paid', [$school, $invoice]) }}">@csrf @method('PATCH') <button class="btn btn-sm btn-outline-success">Mark Paid</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No invoices yet.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
        <div class="mt-3">{{ $invoices->links() }}</div>
    </div>
</div>
@endsection
