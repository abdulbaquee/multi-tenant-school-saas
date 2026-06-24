<?php

namespace App\Reporting;

enum ReportCategory: string
{
    case Students = 'students';
    case Attendance = 'attendance';
    case Fees = 'fees';
    case Examinations = 'examinations';
    case Schools = 'schools';
    case Users = 'users';

    public function label(): string
    {
        return match ($this) {
            self::Students => 'Student Reports',
            self::Attendance => 'Attendance Reports',
            self::Fees => 'Fee Reports',
            self::Examinations => 'Examination Reports',
            self::Schools => 'School Reports',
            self::Users => 'User Reports',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Students => 'Enrollment and class placement summaries with privacy-safe student identifiers.',
            self::Attendance => 'Attendance totals and trends for authorized class or section scope.',
            self::Fees => 'Collection, outstanding balance, and receipt summaries for authorized financial scope.',
            self::Examinations => 'Result and performance summaries for authorized examination scope.',
            self::Schools => 'Platform-wide school activity and status summaries.',
            self::Users => 'User access and role summaries for authorized scope.',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::Students => 'reports.students.index',
            self::Attendance => 'reports.attendance.index',
            self::Fees => 'reports.fees.index',
            self::Examinations => 'reports.examinations.index',
            self::Schools => 'reports.schools.index',
            self::Users => 'reports.users.index',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Students => 'bi-person-vcard',
            self::Attendance => 'bi-calendar2-check',
            self::Fees => 'bi-cash-coin',
            self::Examinations => 'bi-journal-check',
            self::Schools => 'bi-buildings',
            self::Users => 'bi-people',
        };
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Schools,
            self::Users,
            self::Students,
            self::Attendance,
            self::Fees,
            self::Examinations,
        ];
    }
}
