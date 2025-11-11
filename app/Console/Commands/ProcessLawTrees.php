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
                            {--limit=50 : Number of laws to process}
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
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $query = Law::query()
            ->whereNotNull('content_structure')
            ->whereNotNull('content_text');

        if ($lawId) {
            $query->where('id', $lawId);
        } elseif (! $force) {
            $query->whereNull('processed_at');
        }

        $totalToProcess = min($query->count(), $limit);

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

        $query->orderBy('id')
            ->chunk(50, function ($laws) use ($processor, &$totalProcessed, &$totalSuccess, &$totalFailed, $limit, $progressBar) {
                foreach ($laws as $law) {
                    if ($totalProcessed >= $limit) {
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
        $this->info('✓ Finished processing law trees!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $totalProcessed],
                ['Success', $totalSuccess],
                ['Failed', $totalFailed],
            ]
        );

        return self::SUCCESS;
    }
}
