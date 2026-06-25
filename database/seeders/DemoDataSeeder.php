<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Password123';

    public function run(): void
    {
        if (School::query()->where('code', 'SHA')->exists()) {
            $this->ensureDemoTeacherProfiles();
            $this->command?->warn('Demo schools already exist. Synchronized demo teacher profiles only.');

            return;
        }

        /** @var array<string, int> $roleIds */
        $roleIds = DB::table('roles')->pluck('id', 'code')->all();
        $now = now();
        $passwordHash = Hash::make(self::DEMO_PASSWORD);

        $schoolDefinitions = [
            [
                'code' => 'SHA',
                'name' => 'Springdale High A',
                'email' => 'school-sha@example.com',
                'full_fee_demo' => true,
                'users' => [
                    ['role' => Role::SCHOOL_ADMIN, 'name' => 'School Admin A', 'email' => 'admin.sha@example.com'],
                    ['role' => Role::TEACHER, 'name' => 'Teacher A', 'email' => 'teachera.sha@example.com'],
                    ['role' => Role::ACCOUNTANT, 'name' => 'Accountant A', 'email' => 'accountant.sha@example.com'],
                ],
            ],
            [
                'code' => 'SHB',
                'name' => 'Springdale High B',
                'email' => 'school-shb@example.com',
                'full_fee_demo' => false,
                'users' => [
                    ['role' => Role::SCHOOL_ADMIN, 'name' => 'School Admin B', 'email' => 'admin.shb@example.com'],
                    ['role' => Role::TEACHER, 'name' => 'Teacher B', 'email' => 'teachera.shb@example.com'],
                    ['role' => Role::ACCOUNTANT, 'name' => 'Accountant B', 'email' => 'accountant.shb@example.com'],
                ],
            ],
            [
                'code' => 'SHC',
                'name' => 'Springdale High C',
                'email' => 'school-shc@example.com',
                'full_fee_demo' => false,
                'users' => [
                    ['role' => Role::SCHOOL_ADMIN, 'name' => 'School Admin C', 'email' => 'admin.shc@example.com'],
                    ['role' => Role::TEACHER, 'name' => 'Teacher C', 'email' => 'teachera.shc@example.com'],
                    ['role' => Role::ACCOUNTANT, 'name' => 'Accountant C', 'email' => 'accountant.shc@example.com'],
                ],
            ],
        ];

        foreach ($schoolDefinitions as $definition) {
            $school = School::create([
                'name' => $definition['name'],
                'code' => $definition['code'],
                'email' => $definition['email'],
                'status' => School::STATUS_ACTIVE,
            ]);

            DB::table('school_settings')->insert([
                'school_id' => $school->id,
                'timezone' => 'Asia/Kolkata',
                'currency' => 'INR',
                'academic_year_start_month' => 4,
                'grading_system' => 'percentage',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($definition['users'] as $userDefinition) {
                User::create([
                    'school_id' => $school->id,
                    'role_id' => (int) $roleIds[$userDefinition['role']],
                    'name' => $userDefinition['name'],
                    'email' => $userDefinition['email'],
                    'email_verified_at' => $now,
                    'password' => $passwordHash,
                    'status' => User::STATUS_ACTIVE,
                ]);
            }

            if ($definition['full_fee_demo']) {
                $this->seedSchoolAFeeDemo($school);
            } else {
                $this->seedMinimalTenantBaseline($school);
            }
        }

        $this->command?->info('Demo schools and Fee data seeded.');
        $this->command?->info('School A Fee demo: login admin.sha@example.com / '.self::DEMO_PASSWORD);
    }

    private function seedSchoolAFeeDemo(School $school): void
    {
        app(TenantContext::class)->runAsTenant($school->id, function (): void {
            $year = AcademicYear::create([
                'name' => '2026-2027',
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => true,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);

            $class8 = SchoolClass::create([
                'name' => 'Class 8',
                'code' => 'VIII',
                'sort_order' => 8,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);

            $class9 = SchoolClass::create([
                'name' => 'Class 9',
                'code' => 'IX',
                'sort_order' => 9,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);

            $section8A = Section::create([
                'class_id' => $class8->id,
                'name' => 'A',
                'capacity' => 40,
                'status' => Section::STATUS_ACTIVE,
            ]);

            $this->linkDemoTeacher($school, $section8A);

            $section9A = Section::create([
                'class_id' => $class9->id,
                'name' => 'A',
                'capacity' => 40,
                'status' => Section::STATUS_ACTIVE,
            ]);

            $students = [
                [
                    'admission_no' => 'SHA-001',
                    'first_name' => 'Aarav',
                    'last_name' => 'Sharma',
                    'class_id' => $class8->id,
                    'section_id' => $section8A->id,
                    'roll_no' => '8A-01',
                ],
                [
                    'admission_no' => 'SHA-002',
                    'first_name' => 'Isha',
                    'last_name' => 'Patel',
                    'class_id' => $class8->id,
                    'section_id' => $section8A->id,
                    'roll_no' => '8A-02',
                ],
                [
                    'admission_no' => 'SHA-003',
                    'first_name' => 'Rohan',
                    'last_name' => 'Mehta',
                    'class_id' => $class8->id,
                    'section_id' => $section8A->id,
                    'roll_no' => '8A-03',
                ],
                [
                    'admission_no' => 'SHA-004',
                    'first_name' => 'Neha',
                    'last_name' => 'Kumar',
                    'class_id' => $class9->id,
                    'section_id' => $section9A->id,
                    'roll_no' => '9A-01',
                ],
            ];

            $studentModels = [];

            foreach ($students as $studentDefinition) {
                $student = Student::create([
                    'admission_no' => $studentDefinition['admission_no'],
                    'first_name' => $studentDefinition['first_name'],
                    'last_name' => $studentDefinition['last_name'],
                    'gender' => Student::GENDER_PREFER_NOT_TO_SAY,
                    'date_of_birth' => '2015-05-10',
                    'guardian_name' => 'Guardian '.$studentDefinition['admission_no'],
                    'guardian_phone' => '9876500001',
                    'guardian_email' => strtolower($studentDefinition['admission_no']).'@guardian.example.com',
                    'admission_date' => '2026-04-02',
                    'status' => Student::STATUS_ACTIVE,
                ]);

                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'class_id' => $studentDefinition['class_id'],
                    'section_id' => $studentDefinition['section_id'],
                    'roll_no' => $studentDefinition['roll_no'],
                    'enrollment_date' => '2026-04-02',
                    'status' => StudentEnrollment::STATUS_ACTIVE,
                ]);

                $studentModels[$studentDefinition['admission_no']] = $student;
            }

            $tuition = FeeCategory::create([
                'name' => 'Tuition',
                'description' => 'Annual tuition charges for the current academic year.',
                'status' => FeeCategory::STATUS_ACTIVE,
            ]);

            FeeCategory::create([
                'name' => 'Transport',
                'description' => 'School transport service charges.',
                'status' => FeeCategory::STATUS_ACTIVE,
            ]);

            FeeCategory::create([
                'name' => 'Laboratory',
                'description' => 'Science laboratory usage charges.',
                'status' => FeeCategory::STATUS_ACTIVE,
            ]);

            FeeCategory::create([
                'name' => 'Legacy Misc',
                'description' => 'Inactive category kept for lifecycle testing.',
                'status' => FeeCategory::STATUS_INACTIVE,
            ]);

            $transport = FeeCategory::query()->where('name', 'Transport')->firstOrFail();

            $tuitionStructure = FeeStructure::create([
                'fee_category_id' => $tuition->id,
                'academic_year_id' => $year->id,
                'class_id' => $class8->id,
                'amount' => '15000.00',
                'due_date' => '2026-08-01',
                'frequency' => FeeStructure::FREQUENCY_ANNUAL,
                'status' => FeeStructure::STATUS_ACTIVE,
            ]);

            $transportStructure = FeeStructure::create([
                'fee_category_id' => $transport->id,
                'academic_year_id' => $year->id,
                'class_id' => $class8->id,
                'amount' => '6000.00',
                'due_date' => '2026-08-01',
                'frequency' => FeeStructure::FREQUENCY_ANNUAL,
                'status' => FeeStructure::STATUS_ACTIVE,
            ]);

            FeeStructure::create([
                'fee_category_id' => $tuition->id,
                'academic_year_id' => $year->id,
                'class_id' => $class9->id,
                'amount' => '16000.00',
                'due_date' => '2026-08-01',
                'frequency' => FeeStructure::FREQUENCY_ANNUAL,
                'status' => FeeStructure::STATUS_ACTIVE,
            ]);

            FeeStructure::create([
                'fee_category_id' => FeeCategory::query()->where('name', 'Laboratory')->value('id'),
                'academic_year_id' => $year->id,
                'class_id' => $class8->id,
                'amount' => '1200.00',
                'due_date' => '2026-07-01',
                'frequency' => FeeStructure::FREQUENCY_MONTHLY,
                'status' => FeeStructure::STATUS_INACTIVE,
            ]);

            $this->assignStudentFee(
                $studentModels['SHA-001'],
                $tuitionStructure,
                $year,
                '15000.00',
                '0.00',
                '15000.00',
            );

            $this->assignStudentFee(
                $studentModels['SHA-002'],
                $tuitionStructure,
                $year,
                '15000.00',
                '1000.00',
                '14000.00',
            );

            $this->assignStudentFee(
                $studentModels['SHA-001'],
                $transportStructure,
                $year,
                '6000.00',
                '0.00',
                '6000.00',
            );
        });
    }

    private function seedMinimalTenantBaseline(School $school): void
    {
        app(TenantContext::class)->runAsTenant($school->id, function (): void {
            AcademicYear::create([
                'name' => '2026-2027',
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => true,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);

            SchoolClass::create([
                'name' => 'Class 8',
                'code' => 'VIII',
                'sort_order' => 8,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);

            $class8 = SchoolClass::query()->where('code', 'VIII')->firstOrFail();
            $section = Section::create([
                'class_id' => $class8->id,
                'name' => 'A',
                'capacity' => 40,
                'status' => Section::STATUS_ACTIVE,
            ]);

            $this->linkDemoTeacher($school, $section);

            FeeCategory::create([
                'name' => 'Tuition',
                'description' => 'Baseline tenant category for isolation testing.',
                'status' => FeeCategory::STATUS_ACTIVE,
            ]);
        });
    }

    private function assignStudentFee(
        Student $student,
        FeeStructure $structure,
        AcademicYear $year,
        string $amount,
        string $discount,
        string $payable,
    ): void {
        StudentFee::create([
            'student_id' => $student->id,
            'fee_structure_id' => $structure->id,
            'academic_year_id' => $year->id,
            'amount' => $amount,
            'discount_amount' => $discount,
            'payable_amount' => $payable,
            'paid_amount' => '0.00',
            'balance_amount' => $payable,
            'due_date' => $structure->due_date,
            'status' => StudentFee::STATUS_PENDING,
        ]);
    }

    private function ensureDemoTeacherProfiles(): void
    {
        foreach (['SHA', 'SHB', 'SHC'] as $code) {
            $school = School::query()->where('code', $code)->first();

            if (! $school instanceof School) {
                continue;
            }

            app(TenantContext::class)->runAsTenant($school->id, function () use ($school): void {
                $section = Section::query()
                    ->whereNull('deleted_at')
                    ->where('status', Section::STATUS_ACTIVE)
                    ->orderBy('id')
                    ->first();

                if (! $section instanceof Section) {
                    return;
                }

                $this->linkDemoTeacher($school, $section);
            });
        }
    }

    private function linkDemoTeacher(School $school, Section $section): void
    {
        $teacherUser = User::query()
            ->where('school_id', $school->id)
            ->whereHas('role', fn ($query) => $query->where('code', Role::TEACHER))
            ->orderBy('id')
            ->first();

        if (! $teacherUser instanceof User) {
            return;
        }

        $teacher = $teacherUser->teacherProfile()
            ->whereNull('deleted_at')
            ->first();

        if (! $teacher instanceof Teacher) {
            $teacher = Teacher::create([
                'user_id' => $teacherUser->id,
                'employee_code' => 'TCH-'.$school->code,
                'joining_date' => '2025-04-01',
                'status' => Teacher::STATUS_ACTIVE,
            ]);
        }

        if ((int) $section->teacher_id !== (int) $teacher->id) {
            $section->update(['teacher_id' => $teacher->id]);
        }
    }
}
