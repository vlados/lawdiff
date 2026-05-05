<?php

namespace App\Console\Commands;

use App\Models\Law;
use App\Services\LawTreeProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchLawContents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laws:fetch-contents
                            {--limit= : Limit number of laws fetched (default: all matching)}
                            {--force : Force re-fetch content even if already fetched}
                            {--law-id= : Fetch content for a specific law ID}
                            {--throttle-ms=100 : Delay between API calls in milliseconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store the content of Bulgarian laws from the APIS.BG API';

    /**
     * Execute the console command.
     */
    public function handle(LawTreeProcessor $processor): int
    {
        $this->info('Starting to fetch law contents...');

        $lawId = $this->option('law-id');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $force = $this->option('force');
        $throttleMs = max(0, (int) $this->option('throttle-ms'));

        $query = Law::query();

        if ($lawId) {
            $query->where('id', $lawId);
        } elseif (! $force) {
            $query->where(function ($q) {
                // Fetch laws without content
                $q->whereNull('content_fetched_at')
                    // OR laws published after last fetch AND publication date has passed
                    ->orWhere(function ($q) {
                        $q->whereColumn('publ_date', '>', 'content_fetched_at')
                            ->where('publ_date', '<=', now());
                    })
                    // OR laws starting after last fetch AND start date has passed
                    ->orWhere(function ($q) {
                        $q->whereColumn('start_date', '>', 'content_fetched_at')
                            ->where('start_date', '<=', now());
                    });
            });
        }
        //        dd($query->toRawSql());

        $matching = (int) $query->count();
        $totalToProcess = $limit !== null ? min($matching, $limit) : $matching;

        if ($totalToProcess === 0) {
            $this->info('No laws found to fetch content for.');

            return self::SUCCESS;
        }

        $this->info("Found {$totalToProcess} laws to fetch content for.");

        $totalProcessed = 0;
        $totalSuccess = 0;
        $totalFailed = 0;

        $progressBar = $this->output->createProgressBar($totalToProcess);
        $progressBar->start();

        $query->orderBy('id')
            ->chunk(50, function ($laws) use ($processor, &$totalProcessed, &$totalSuccess, &$totalFailed, $limit, $throttleMs, $progressBar) {
                foreach ($laws as $law) {
                    if ($limit !== null && $totalProcessed >= $limit) {
                        return false;
                    }

                    try {
                        $contentStructure = $this->fetchDocContent($law->unique_id, $law->db_index);
                        $contentText = $this->fetchDocTextJson($law->unique_id, $law->db_index);

                        $law->update([
                            'content_structure' => $contentStructure,
                            'content_text' => $contentText,
                            'content_fetched_at' => now(),
                        ]);

                        // Process the tree with markdown conversion and save to law_nodes
                        $processor->process($law->fresh());

                        $law->update([
                            'processed_at' => now(),
                        ]);

                        $totalSuccess++;
                    } catch (\Exception $e) {
                        $this->newLine();
                        $this->error("Failed to fetch content for law {$law->unique_id}: ".$e->getMessage());
                        $totalFailed++;
                    }

                    $totalProcessed++;
                    $progressBar->advance();

                    if ($throttleMs > 0) {
                        usleep($throttleMs * 1000);
                    }
                }

                return true;
            });

        $progressBar->finish();

        $this->newLine(2);
        $this->info('✓ Finished fetching law contents!');
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

    protected function fetchDocContent(int $uniqueId, int $dbIndex): array
    {
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'origin' => 'https://legislation.apis.bg',
            'referer' => 'https://legislation.apis.bg/',
        ])
            ->retry(3, 500, throw: false)
            ->get('https://web-api.apis.bg/api/obshtina-legislation/DocContent', [
                'uniqueId' => $uniqueId,
                'dbIndex' => $dbIndex,
            ]);

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch DocContent. Status: {$response->status()}");
        }

        return $response->json();
    }

    protected function fetchDocTextJson(int $uniqueId, int $dbIndex): array
    {
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'origin' => 'https://legislation.apis.bg',
            'referer' => 'https://legislation.apis.bg/',
        ])
            ->retry(3, 500, throw: false)
            ->get('https://web-api.apis.bg/api/obshtina-legislation/DocTextJson/', [
                'uniqueId' => $uniqueId,
                'dbIndex' => $dbIndex,
                'searchText' => '',
            ]);

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch DocTextJson. Status: {$response->status()}");
        }

        return $response->json();
    }
}
