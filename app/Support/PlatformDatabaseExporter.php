<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

final class PlatformDatabaseExporter
{
    public function exportToZip(int $backupId): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The ZipArchive extension is required for platform backups.');
        }

        $timestamp = now()->format('Y-m-d-His');
        $directory = "backups/platform/{$timestamp}";
        $sqlFilename = "platform-backup-{$backupId}-{$timestamp}.sql";
        $zipFilename = "platform-backup-{$backupId}-{$timestamp}.zip";
        $relativeSqlPath = "{$directory}/{$sqlFilename}";
        $relativeZipPath = "{$directory}/{$zipFilename}";

        Storage::disk('local')->makeDirectory($directory);

        $this->writeSqlDump(Storage::disk('local')->path($relativeSqlPath));

        $zip = new ZipArchive;
        $opened = $zip->open(Storage::disk('local')->path($relativeZipPath), ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            throw new RuntimeException('Unable to create the platform backup archive.');
        }

        $zip->addFile(Storage::disk('local')->path($relativeSqlPath), $sqlFilename);
        $zip->addFromString('manifest.json', json_encode([
            'backup_id' => $backupId,
            'backup_scope' => 'platform',
            'generated_at' => now()->toIso8601String(),
            'table_count' => count($this->tableNames()),
        ], JSON_THROW_ON_ERROR));
        $zip->close();

        Storage::disk('local')->delete($relativeSqlPath);

        return $relativeZipPath;
    }

    private function writeSqlDump(string $absolutePath): void
    {
        $handle = fopen($absolutePath, 'w');

        if ($handle === false) {
            throw new RuntimeException('Unable to create the platform backup SQL file.');
        }

        fwrite($handle, '-- Platform backup generated at '.now()->toDateTimeString().PHP_EOL);

        foreach ($this->tableNames() as $table) {
            fwrite($handle, PHP_EOL."-- Table: {$table}".PHP_EOL);

            foreach (DB::table($table)->cursor() as $row) {
                $columns = array_keys((array) $row);
                $values = array_map(
                    fn (mixed $value): string => $this->quoteValue($value),
                    array_values((array) $row),
                );

                $columnList = implode(', ', array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns));
                $valueList = implode(', ', $values);

                fwrite($handle, "INSERT INTO {$this->quoteIdentifier($table)} ({$columnList}) VALUES ({$valueList});".PHP_EOL);
            }
        }

        fclose($handle);
    }

    /**
     * @return list<string>
     */
    private function tableNames(): array
    {
        return collect(Schema::getTableListing())
            ->map(function (string $table): string {
                return str_contains($table, '.')
                    ? substr($table, strpos($table, '.') + 1)
                    : $table;
            })
            ->reject(fn (string $table): bool => str_starts_with($table, 'sqlite_'))
            ->sort()
            ->values()
            ->all();
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return DB::getPdo()->quote((string) $value);
    }
}
