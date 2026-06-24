<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemLogCsvExportService
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<string|int|float|null>>  $rows
     */
    public function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
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
