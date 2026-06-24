<?php

namespace App\Services;

use App\Models\User;
use App\Reporting\ReportCategory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCsvExportService
{
    public function __construct(private readonly ReportService $reports) {}

    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<string|int|float|null>>  $rows
     */
    public function stream(
        User $actor,
        ReportCategory $category,
        string $filename,
        array $headers,
        iterable $rows,
    ): StreamedResponse {
        $this->reports->authorizeCategoryExport($actor, $category);

        $safeFilename = str_replace(['"', "\n", "\r"], '', $filename);

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $safeFilename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
