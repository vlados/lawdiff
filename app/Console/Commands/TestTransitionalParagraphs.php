<?php

namespace App\Console\Commands;

use App\Models\Law;
use App\Services\LawTreeProcessor;
use Illuminate\Console\Command;

class TestTransitionalParagraphs extends Command
{
    protected $signature = 'law:test-transitional {law_id=310}';

    protected $description = 'Test processing of transitional and final provisions (§ paragraphs)';

    public function handle(LawTreeProcessor $processor): int
    {
        $lawId = $this->argument('law_id');

        $law = Law::find($lawId);

        if (! $law) {
            $this->error("Law with ID {$lawId} not found");

            return Command::FAILURE;
        }

        $this->info("Law: {$law->caption}");
        $this->newLine();

        $this->info('Processing law tree...');
        $processor->process($law);
        $this->newLine();

        // Check the results
        $totalNodes = $law->nodes()->count();
        $this->info("Total nodes created: {$totalNodes}");
        $this->newLine();

        // Show transitional paragraph nodes
        $transitionalNodes = $law->nodes()
            ->where('node_type', 'transitional_paragraph')
            ->orderBy('sort_order')
            ->get();

        $this->info("Transitional paragraphs found: {$transitionalNodes->count()}");
        $this->newLine();

        if ($transitionalNodes->count() > 0) {
            $this->info('First 5 transitional paragraphs:');
            $this->newLine();

            foreach ($transitionalNodes->take(5) as $node) {
                $this->line("Path: {$node->path}");
                $this->line("Type: {$node->node_type}");
                $this->line('Text: '.mb_substr($node->text_markdown ?? '', 0, 80).'...');

                // Check for child nodes
                $children = $law->nodes()
                    ->where('path', 'like', $node->path.'/%')
                    ->where('level', $node->level + 1)
                    ->get();

                if ($children->count() > 0) {
                    $this->line("Children: {$children->count()} nodes");
                    foreach ($children->take(3) as $child) {
                        $this->line("  - {$child->path} ({$child->node_type})");
                    }
                }

                $this->newLine();
            }
        }

        // Show regular articles for comparison
        $articles = $law->nodes()
            ->where('node_type', 'article')
            ->orderBy('sort_order')
            ->get();

        $this->info("Regular articles: {$articles->count()}");
        $this->newLine();

        return Command::SUCCESS;
    }
}
