<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BeneficiaryProgram;
use App\Models\ImportLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class YearlyArchiveService
{
    public const ARCHIVE_DIR = 'app/archives';

    /**
     * Build and store a gzip-compressed yearly archive (.json.gz) for long-term sustainability.
     *
     * @return array{
     *     year: int,
     *     filename: string,
     *     path: string,
     *     total_enrollments: int,
     *     uncompressed_bytes: int,
     *     compressed_bytes: int,
     *     compression_ratio: float
     * }
     */
    public function createYearlyArchive(int $year): array
    {
        $archiveDir = storage_path(self::ARCHIVE_DIR);
        File::ensureDirectoryExists($archiveDir);

        $enrollments = BeneficiaryProgram::with([
            'beneficiary:id,full_name,first_name,middle_name,last_name,suffix,date_of_birth,sex,civil_status,barangay,municipality,province,district,government_id_type,government_id_number,contact_number,address,is_student,is_graduating_college',
            'program:id,code,name',
            'project:id,name,implementation_type,municipality,year',
        ])
            ->where('availment_year', $year)
            ->orderBy('id')
            ->get();

        $programCounts = [];
        $rows = [];

        foreach ($enrollments as $bp) {
            $code = $bp->program?->code ?? 'UNKNOWN';
            $programCounts[$code] = ($programCounts[$code] ?? 0) + 1;

            $rows[] = [
                'enrollment_id' => $bp->id,
                'beneficiary_id' => $bp->beneficiary_id,
                'program' => $code,
                'project' => $bp->project?->name,
                'implementation_type' => $bp->project?->implementation_type,
                'availment_year' => (int) $bp->availment_year,
                'status' => $bp->status,
                'full_name' => $bp->beneficiary?->full_name,
                'first_name' => $bp->beneficiary?->first_name,
                'middle_name' => $bp->beneficiary?->middle_name,
                'last_name' => $bp->beneficiary?->last_name,
                'suffix' => $bp->beneficiary?->suffix,
                'date_of_birth' => $bp->beneficiary?->date_of_birth?->format('Y-m-d'),
                'sex' => $bp->beneficiary?->sex,
                'barangay' => $bp->beneficiary?->barangay,
                'municipality' => $bp->beneficiary?->municipality,
                'province' => $bp->beneficiary?->province,
            ];
        }

        $payload = [
            'manifest' => [
                'agency' => 'DOLE Field Office - Bukidnon',
                'year' => $year,
                'generated_at' => now()->toIso8601String(),
                'total_enrollments' => count($rows),
                'programs_breakdown' => $programCounts,
            ],
            'records' => $rows,
        ];

        $json = (string) json_encode($payload, JSON_UNESCAPED_UNICODE);
        $compressed = (string) gzencode($json, 9);

        $filename = sprintf('dole_archive_%d_%s.json.gz', $year, now()->format('Ymd_His'));
        $fullPath = $archiveDir.DIRECTORY_SEPARATOR.$filename;
        File::put($fullPath, $compressed);

        $uncompressedBytes = strlen($json);
        $compressedBytes = strlen($compressed);
        $ratio = $uncompressedBytes > 0
            ? round((1 - ($compressedBytes / $uncompressedBytes)) * 100, 1)
            : 0.0;

        AuditLog::log([
            'action' => 'yearly_archive_created',
            'description' => "Created compressed {$year} archive ({$filename}): ".count($rows)." records, {$compressedBytes} bytes ({$ratio}% saved)",
        ]);

        return [
            'year' => $year,
            'filename' => $filename,
            'path' => $fullPath,
            'total_enrollments' => count($rows),
            'uncompressed_bytes' => $uncompressedBytes,
            'compressed_bytes' => $compressedBytes,
            'compression_ratio' => $ratio,
        ];
    }

    /**
     * List existing yearly compressed archives on disk.
     *
     * @return Collection<int, array{filename: string, size_bytes: int, size_human: string, modified_at: string, year: ?int}>
     */
    public function listArchives(): Collection
    {
        $archiveDir = storage_path(self::ARCHIVE_DIR);
        if (! File::isDirectory($archiveDir)) {
            return collect();
        }

        return collect(File::files($archiveDir))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.json.gz'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values()
            ->map(function ($file) {
                $name = $file->getFilename();
                $size = $file->getSize();
                $year = preg_match('/dole_archive_(\d{4})_/', $name, $m) ? (int) $m[1] : null;

                return [
                    'filename' => $name,
                    'size_bytes' => $size,
                    'size_human' => $this->formatBytes($size),
                    'modified_at' => date('M d, Y h:i A', $file->getMTime()),
                    'year' => $year,
                ];
            });
    }

    /**
     * Safely resolve an archive path preventing directory traversal.
     */
    public function resolveArchivePath(string $filename): ?string
    {
        $safeName = basename($filename);
        if (! str_ends_with($safeName, '.json.gz') || ! preg_match('/^[a-zA-Z0-9._-]+$/', $safeName)) {
            return null;
        }

        $path = storage_path(self::ARCHIVE_DIR.DIRECTORY_SEPARATOR.$safeName);

        return File::exists($path) ? $path : null;
    }

    /**
     * Get per-year enrollment counts available for archiving.
     *
     * @return Collection<int, array{year: int, total: int}>
     */
    public function availableYearsSummary(): Collection
    {
        return BeneficiaryProgram::query()
            ->selectRaw('availment_year as year, COUNT(*) as total')
            ->whereNotNull('availment_year')
            ->groupBy('availment_year')
            ->orderByDesc('availment_year')
            ->get()
            ->map(fn ($row) => [
                'year' => (int) $row->year,
                'total' => (int) $row->total,
            ]);
    }

    /**
     * Safely prune bulky error_log JSON payloads from old import logs without deleting beneficiary history.
     */
    public function pruneOldImportLogs(int $beforeYear): int
    {
        $cutoffDate = sprintf('%04d-01-01 00:00:00', $beforeYear);

        $logs = ImportLog::where('created_at', '<', $cutoffDate)
            ->whereNotNull('error_log')
            ->get();

        $prunedCount = 0;
        foreach ($logs as $log) {
            if (! empty($log->error_log)) {
                $log->update([
                    'error_log' => [['info' => "Archived and pruned detailed row logs prior to {$beforeYear} for database compactness."]],
                ]);
                $prunedCount++;
            }
        }

        if ($prunedCount > 0) {
            AuditLog::log([
                'action' => 'prune_import_logs',
                'description' => "Pruned bulky error payloads from {$prunedCount} import logs prior to {$beforeYear}.",
            ]);
        }

        return $prunedCount;
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
