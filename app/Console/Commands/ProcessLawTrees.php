<?php

namespace App\Console\Commands;

use App\Models\Law;
use App\Services\LawTreeProcessor;
use Illuminate\Console\Command;

class ProcessLawTrees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laws:process-trees
                            {--limit= : Limit number of laws processed (default: all matching)}
                            {--force : Force re-process even if already processed}
                            {--law-id= : Process a specific law ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process law content into structured trees with markdown text';

    /**
     * Execute the console command.
     */
    public function handle(LawTreeProcessor $processor): int
    {
        $this->info('Starting to process law trees...');

        $lawId = $this->option('law-id');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $force = $this->option('force');

        // The payload moved onto the version, so "has content" is now "has a
        // current redaction carrying one".
        $query = Law::query()
            ->whereHas('currentVersion', function ($version): void {
                $version->whereNotNull('content_structure')->whereNotNull('content_text');
            });

        if ($lawId) {
            $query->where('id', $lawId);
        } elseif (! $force) {
            $query->whereNull('processed_at');
        }

        $matching = $query->count();
        $totalToProcess = $limit !== null ? min($matching, $limit) : $matching;

        if ($totalToProcess === 0) {
            $this->info('No laws found to process.');

            return self::SUCCESS;
        }

        $this->info("Found {$totalToProcess} laws to process.");

        $totalProcessed = 0;
        $totalSuccess = 0;
        $totalFailed = 0;

        $progressBar = $this->output->createProgressBar($totalToProcess);
        $progressBar->start();

        // chunkById, not chunk: without --force the filter keys on processed_at, which
        // the loop sets — offset paging would skip past whole pages of pending laws.
        $query->chunkById(50, function ($laws) use ($processor, &$totalProcessed, &$totalSuccess, &$totalFailed, $limit, $progressBar) {
            foreach ($laws as $law) {
                if ($limit !== null && $totalProcessed >= $limit) {
                    return false;
                }

                try {
                    $processor->process($law);

                    $law->update([
                        'processed_at' => now(),
                    ]);

                    $totalSuccess++;
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error("Failed to process law {$law->unique_id}: ".$e->getMessage());
                    $totalFailed++;
                }

                $totalProcessed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();

        $this->newLine(2);
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $totalProcessed],
                ['Success', $totalSuccess],
                ['Failed', $totalFailed],
            ]
        );

        if ($totalProcessed > 0 && $totalSuccess === 0) {
            $this->error('Every law failed to process — failing instead of pretending success.');

            return self::FAILURE;
        }

        $this->info('✓ Finished processing law trees!');

        return self::SUCCESS;
    }
}
