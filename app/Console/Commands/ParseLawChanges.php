<?php

namespace App\Console\Commands;

use App\Services\LawAmendmentParser;
use Illuminate\Console\Command;

class ParseLawChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'law:parse-changes {file : The path to the law amendment document}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse a law amendment document (ЗАКОН за изменение и допълнение) and extract amendments with target paths';

    /**
     * Execute the console command.
     */
    public function handle(LawAmendmentParser $parser): int
    {
        $filePath = $this->argument('file');

        // Check if file exists
        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        try {
            $this->info('Parsing law amendment document...');
            $this->newLine();

            $result = $parser->parse($filePath);

            // Display law name
            $this->info('📋 Amendment Law:');
            $this->line("   {$result['law_name']}");
            $this->newLine();

            // Display target law
            $this->info('🎯 Target Law:');
            $this->line("   {$result['target_law_name']}");
            $this->newLine();

            // Display amendments
            $this->info("📝 Found {$this->formatNumber(count($result['amendments']))} amendments (§ paragraphs):");
            $this->newLine();

            foreach ($result['amendments'] as $index => $amendment) {
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                $this->line("§ {$amendment['paragraph_number']}");
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                $this->newLine();

                // Display target paths
                if (! empty($amendment['targets'])) {
                    $this->line('<fg=green>🎯 Target Paths (affected elements in original law):</>');
                    foreach ($amendment['targets'] as $target) {
                        $this->line("   → {$target['path']}");
                    }
                    $this->newLine();
                }

                $this->line('<fg=cyan>📄 Amendment Content:</>');
                $this->line($this->truncateText($amendment['content'], 300));
                $this->newLine();

                if ($amendment['motives']) {
                    $this->line('<fg=yellow>💡 Motives:</>');
                    $this->line($this->truncateText($amendment['motives'], 200));
                    $this->newLine();
                }

                if ($index < count($result['amendments']) - 1) {
                    $this->newLine();
                }
            }

            $this->newLine();
            $this->info('✅ Parsing completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error parsing file: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    protected function formatNumber(int $number): string
    {
        return number_format($number, 0, '.', ',');
    }

    protected function truncateText(string $text, int $maxLength): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength).'...';
    }
}
