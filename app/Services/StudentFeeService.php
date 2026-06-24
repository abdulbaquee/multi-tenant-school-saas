<?php

namespace App\Services;

use App\Models\FeeStructure;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentFeeService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, state?: string|null}  $filters
     * @return LengthAwarePaginator<int, StudentFee>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', StudentFee::class));

        return StudentFee::query()
            ->with([
                'student',
                'feeStructure.feeCategory',
                'feeStructure.schoolClass',
                'academicYear',
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('student', fn ($query) => $query
                        ->where('admission_no', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search));
                });
            })
            ->when(filled($filters['state'] ?? null), fn ($query) => $query->where('status', $filters['state']))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(StudentFee $studentFee, User $actor): StudentFee
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('view', $studentFee));

        return $studentFee->load([
            'student',
            'feeStructure.feeCategory',
            'feeStructure.schoolClass',
            'academicYear',
        ]);
    }

    /**
     * @return array{
     *     structures: Collection<int, FeeStructure>,
     *     students: Collection<int, Student>
     * }
     */
    public function assignmentOptions(User $actor, ?int $feeStructureId = null): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', StudentFee::class));

        $structures = FeeStructure::query()
            ->with(['feeCategory', 'academicYear', 'schoolClass'])
            ->where('status', FeeStructure::STATUS_ACTIVE)
            ->orderByDesc('created_at')
            ->get();

        $students = new Collection;

        if ($feeStructureId !== null) {
            $structure = $structures->firstWhere('id', $feeStructureId);

            if ($structure instanceof FeeStructure) {
                $students = $this->eligibleStudentsForStructure($structure);
            }
        }

        return compact('structures', 'students');
    }

    /**
     * @param  array{
     *     fee_structure_id: int,
     *     student_id: int,
     *     discount_amount?: numeric-string|float|int|null
     * }  $data
     */
    public function assign(array $data, User $actor): StudentFee
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', StudentFee::class));

        return DB::transaction(function () use ($data, $actor): StudentFee {
            $this->lockTenantSchool();

            $structure = FeeStructure::query()
                ->lockForUpdate()
                ->with(['feeCategory', 'academicYear', 'schoolClass'])
                ->whereKey($data['fee_structure_id'])
                ->where('status', FeeStructure::STATUS_ACTIVE)
                ->first();

            if (! $structure instanceof FeeStructure) {
                throw ValidationException::withMessages([
                    'fee_structure_id' => 'Select an active Fee Structure in the current school.',
                ]);
            }

            $student = Student::query()
                ->lockForUpdate()
                ->whereKey($data['student_id'])
                ->whereNull('deleted_at')
                ->where('status', Student::STATUS_ACTIVE)
                ->first();

            if (! $student instanceof Student) {
                throw ValidationException::withMessages([
                    'student_id' => 'Select an active Student in the current school.',
                ]);
            }

            $this->assertEligibleEnrollment($student, $structure);

            if (StudentFee::query()->where('student_id', $student->id)->where('fee_structure_id', $structure->id)->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => 'This Student already has an assignment for the selected Fee Structure.',
                ]);
            }

            $amount = $this->formatMoney($structure->amount);
            $discount = $this->formatMoney($data['discount_amount'] ?? 0);

            if (bccomp($discount, '0.00', 2) === -1) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'Discount cannot be negative.',
                ]);
            }

            if (bccomp($discount, $amount, 2) === 1) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'Discount cannot exceed the assigned amount.',
                ]);
            }

            $payable = bcsub($amount, $discount, 2);
            $paid = '0.00';
            $balance = $payable;

            $studentFee = StudentFee::create([
                'student_id' => $student->id,
                'fee_structure_id' => $structure->id,
                'academic_year_id' => $structure->academic_year_id,
                'amount' => $amount,
                'discount_amount' => $discount,
                'payable_amount' => $payable,
                'paid_amount' => $paid,
                'balance_amount' => $balance,
                'due_date' => $structure->due_date,
                'status' => StudentFee::STATUS_PENDING,
            ]);

            $this->logMutation($studentFee, $actor, 'assigned', [], $this->auditValues($studentFee));

            return $studentFee;
        });
    }

    /**
     * @return Collection<int, Student>
     */
    private function eligibleStudentsForStructure(FeeStructure $structure): Collection
    {
        return Student::query()
            ->whereNull('deleted_at')
            ->where('status', Student::STATUS_ACTIVE)
            ->whereHas('enrollments', fn ($query) => $query
                ->where('academic_year_id', $structure->academic_year_id)
                ->where('class_id', $structure->class_id)
                ->where('status', StudentEnrollment::STATUS_ACTIVE))
            ->whereDoesntHave('studentFees', fn ($query) => $query->where('fee_structure_id', $structure->id))
            ->orderBy('admission_no')
            ->get(['id', 'admission_no', 'first_name', 'last_name', 'status']);
    }

    private function assertEligibleEnrollment(Student $student, FeeStructure $structure): void
    {
        $hasActiveEnrollment = $student->enrollments()
            ->where('academic_year_id', $structure->academic_year_id)
            ->where('class_id', $structure->class_id)
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->exists();

        if (! $hasActiveEnrollment) {
            throw ValidationException::withMessages([
                'student_id' => 'The Student must have an active Enrollment matching the Fee Structure Academic Year and Class.',
            ]);
        }
    }

    private function formatMoney(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function authorizeActorContext(User $actor): void
    {
        $valid = filled($actor->school_id)
            && $actor->hasRoleCode(Role::SCHOOL_ADMIN)
            && $actor->canEstablishTenantContext()
            && $this->tenantContext->isTenant()
            && $this->tenantContext->tenantId() === (int) $actor->school_id;

        $this->authorize($valid);
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }

    private function lockTenantSchool(): School
    {
        return School::query()
            ->whereKey($this->tenantContext->tenantId())
            ->where('status', School::STATUS_ACTIVE)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(StudentFee $studentFee): array
    {
        return [
            'student_id' => $studentFee->student_id,
            'fee_structure_id' => $studentFee->fee_structure_id,
            'academic_year_id' => $studentFee->academic_year_id,
            'amount' => $studentFee->amount,
            'discount_amount' => $studentFee->discount_amount,
            'payable_amount' => $studentFee->payable_amount,
            'paid_amount' => $studentFee->paid_amount,
            'balance_amount' => $studentFee->balance_amount,
            'due_date' => optional($studentFee->due_date)?->toDateString(),
            'status' => $studentFee->status,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function logMutation(
        StudentFee $studentFee,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity($actor, 'fee_management', $action, $studentFee, 'Student Fee '.$action.'.');
        $this->securityLogs->audit($actor, $studentFee, $action, $oldValues, $newValues);
    }
}
