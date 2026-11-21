<?php

namespace App\Console\Commands;

use App\Services\YearlyArchiveService;
use Illuminate\Console\Command;

class ArchiveYearData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dole:archive-year {year? : Calendar year to archive (defaults to previous year)} {--prune-logs : Also prune bulky import error logs prior to the following year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a gzip-compressed yearly archive (.json.gz) for DOLE beneficiary records';

    public function handle(YearlyArchiveService $archiveService): int
    {
        $year = $this->argument('year') ? (int) $this->argument('year') : ((int) date('Y') - 1);

        $result = $archiveService->createYearlyArchive($year);

        $this->info(sprintf(
            'Archived %d enrollments for %d into %s (%s compressed, %.1f%% reduction).',
            $result['total_enrollments'],
            $result['year'],
            $result['filename'],
            $archiveService->formatBytes($result['compressed_bytes']),
            $result['compression_ratio']
        ));

        if ($this->option('prune-logs')) {
            $pruned = $archiveService->pruneOldImportLogs($year + 1);
            $this->info("Pruned bulky error payloads from {$pruned} import logs.");
        }

        return self::SUCCESS;
    }
}
