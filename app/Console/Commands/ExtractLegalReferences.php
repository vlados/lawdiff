<?php

namespace App\Console\Commands;

use App\Models\Law;
use App\Models\LegalReference;
use App\Services\LegalReferenceExtractor;
use Illuminate\Console\Command;

class ExtractLegalReferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laws:extract-references
                            {--limit= : Limit number of laws scanned (default: all processed laws)}
                            {--law-id= : Extract references for a specific law ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild the citation graph from already-processed law nodes';

    /**
     * Processing a law already extracts its references, so this exists for the
     * case where the extractor changes but the parser does not: it rebuilds the
     * graph from stored nodes without re-splitting a single provision.
     */
    public function handle(LegalReferenceExtractor $extractor): int
    {
        $this->info('Starting to extract legal references...');

        $lawId = $this->option('law-id');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $query = Law::query()->whereNotNull('processed_at');

        if ($lawId) {
            $query->where('id', $lawId);
        }

        $matching = $query->count();
        $totalToScan = $limit !== null ? min($matching, $limit) : $matching;

        if ($totalToScan === 0) {
            $this->info('No processed laws found.');

            return self::SUCCESS;
        }

        $this->info("Found {$totalToScan} laws to scan.");

        $totalScanned = 0;
        $totalReferences = 0;
        $totalFailed = 0;

        $progressBar = $this->output->createProgressBar($totalToScan);
        $progressBar->start();

        $query->chunkById(50, function ($laws) use ($extractor, &$totalScanned, &$totalReferences, &$totalFailed, $limit, $progressBar) {
            foreach ($laws as $law) {
                if ($limit !== null && $totalScanned >= $limit) {
                    return false;
                }

                try {
                    $totalReferences += $extractor->extract($law);
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error("Failed to extract references for law {$law->unique_id}: ".$e->getMessage());
                    $totalFailed++;
                }

                $totalScanned++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();

        $this->newLine(2);
        $this->table(
            ['Metric', 'Count'],
            [
                ['Laws scanned', $totalScanned],
                ['References found', $totalReferences],
                ['Resolved to a node', $this->countByStatus(LegalReference::STATUS_RESOLVED)],
                ['Unresolved within the law', $this->countByStatus(LegalReference::STATUS_UNRESOLVED_INTERNAL)],
                ['Pointing at another act', $this->countByStatus(LegalReference::STATUS_UNRESOLVED_EXTERNAL)],
                ['Failed', $totalFailed],
            ]
        );

        if ($totalScanned > 0 && $totalFailed === $totalScanned) {
            $this->error('Every law failed — failing instead of pretending success.');

            return self::FAILURE;
        }

        $this->info('✓ Finished extracting legal references!');

        return self::SUCCESS;
    }

    private function countByStatus(string $status): int
    {
        return LegalReference::query()->where('resolution_status', $status)->count();
    }
}
