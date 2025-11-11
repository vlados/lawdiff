<?php

namespace App\Console\Commands;

use App\Models\Law;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchBulgarianLaws extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laws:fetch {--page-size=100 : Number of laws per page}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all Bulgarian laws from the APIS.BG API and store them in the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting to fetch Bulgarian laws...');

        $pageSize = (int) $this->option('page-size');
        $pageNumber = 1;
        $totalProcessed = 0;
        $totalCreated = 0;
        $totalUpdated = 0;

        do {
            $this->info("Fetching page {$pageNumber}...");

            try {
                $response = Http::withHeaders([
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'origin' => 'https://legislation.apis.bg',
                    'referer' => 'https://legislation.apis.bg/',
                ])->post('https://web-api.apis.bg/api/obshtina-legislation/DocList', [
                    'pageNumber' => $pageNumber,
                    'pageSize' => $pageSize,
                    'sortOrder' => 1,
                    'sortType' => 1,
                    'filters' => [
                        [
                            'type' => 29,
                            'params' => [
                                'leadFolderId' => 546,
                                'linkedFolder' => 0,
                            ],
                        ],
                    ],
                ]);

                if (! $response->successful()) {
                    $this->error("Failed to fetch page {$pageNumber}. Status: {$response->status()}");
                    break;
                }

                $data = $response->json();
                $laws = $data['data'] ?? [];
                $currentCount = count($laws);

                if ($currentCount === 0) {
                    break;
                }

                foreach ($laws as $lawData) {
                    $law = Law::updateOrCreate(
                        ['unique_id' => $lawData['uniqueId']],
                        [
                            'db_index' => $lawData['dbIndex'] ?? 0,
                            'caption' => $lawData['caption'],
                            'func' => $lawData['func'],
                            'type' => $lawData['type'],
                            'base' => $lawData['base'],
                            'is_actual' => (bool) $lawData['isActual'],
                            'publ_date' => $lawData['publDate'],
                            'start_date' => $lawData['startDate'],
                            'end_date' => $lawData['endDate'],
                            'act_date' => $lawData['actDate'],
                            'publ_year' => $lawData['publYear'],
                            'is_connected' => (bool) $lawData['isConnected'],
                            'has_content' => (bool) $lawData['hasContent'],
                            'code' => $lawData['code'],
                            'dv' => $lawData['dv'],
                            'original_id' => $lawData['originalId'],
                            'version' => $lawData['version'],
                            'celex' => $lawData['celex'],
                            'doc_lead' => $lawData['docLead'],
                            'seria' => $lawData['seria'],
                        ]
                    );

                    if ($law->wasRecentlyCreated) {
                        $totalCreated++;
                    } else {
                        $totalUpdated++;
                    }

                    $totalProcessed++;
                }

                $totalCount = $data['totalCount'] ?? 0;
                $this->info("Processed {$currentCount} laws from page {$pageNumber}. Total: {$totalProcessed}/{$totalCount}");

                $pageNumber++;

                if ($totalProcessed >= $totalCount) {
                    break;
                }
            } catch (\Exception $e) {
                $this->error("Error fetching page {$pageNumber}: ".$e->getMessage());
                break;
            }
        } while (true);

        $this->newLine();
        $this->info('✓ Finished fetching Bulgarian laws!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $totalProcessed],
                ['Created', $totalCreated],
                ['Updated', $totalUpdated],
            ]
        );

        return self::SUCCESS;
    }
}
