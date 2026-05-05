<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Law;
use App\Models\LawNode;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExportPublicLaws extends Command
{
    protected $signature = 'laws:export-public
                            {--output= : Output directory (default: <repo>/data)}
                            {--limit= : Limit number of laws exported (debug)}
                            {--law-id= : Export only this law id}
                            {--prune : Delete law files no longer present in the database}';

    protected $description = 'Export processed laws to per-law JSON files plus index.json/index.csv for public consumption';

    /**
     * @var array<string, int> tracks slug usage to disambiguate collisions
     */
    private array $slugUsage = [];

    public function handle(): int
    {
        $outputDir = $this->option('output') ?: base_path('data');
        $lawsDir = $outputDir.DIRECTORY_SEPARATOR.'laws';

        $lawId = $this->option('law-id');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $stats = $this->collectPipelineStats($lawId);
        $this->renderPipelineStats($stats);

        $query = Law::query()
            ->whereNotNull('processed_at')
            ->orderBy('unique_id');

        if ($lawId) {
            $query->where('id', $lawId);
        }

        $eligible = (int) $query->count();
        $total = $limit !== null ? min($eligible, $limit) : $eligible;

        if ($total === 0) {
            $this->warn('No processed laws found to export.');

            return self::SUCCESS;
        }

        $this->info("Exporting {$total} laws to: {$outputDir}");

        File::ensureDirectoryExists($lawsDir);

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $manifest = [];
        $writtenFiles = [];
        $exported = 0;

        $query->with(['nodes' => fn ($q) => $q->orderBy('sort_order')])
            ->chunk(50, function (Collection $laws) use (&$manifest, &$writtenFiles, &$exported, $limit, $lawsDir, $progress): bool {
                foreach ($laws as $law) {
                    if ($limit !== null && $exported >= $limit) {
                        return false;
                    }

                    $slug = $this->slugFor($law);
                    $relative = "laws/{$slug}.json";
                    $absolute = $lawsDir.DIRECTORY_SEPARATOR."{$slug}.json";

                    File::put($absolute, $this->encodeJson($this->lawPayload($law, $slug)));

                    $writtenFiles[] = "{$slug}.json";
                    $manifest[] = $this->manifestRow($law, $slug, $relative);

                    $exported++;
                    $progress->advance();
                }

                return true;
            });

        $progress->finish();
        $this->newLine(2);

        $this->writeIndexJson($outputDir, $manifest);
        $this->writeIndexCsv($outputDir, $manifest);
        $this->writeReadme($outputDir, count($manifest));

        if ($this->option('prune')) {
            $this->prune($lawsDir, $writtenFiles);
        }

        $this->info('✓ Export complete.');

        $this->renderExportVerification(
            stats: $stats,
            outputDir: $outputDir,
            lawsDir: $lawsDir,
            exported: count($manifest),
            eligible: $eligible,
            limited: $limit !== null,
            scopedToLawId: $lawId !== null,
        );

        return self::SUCCESS;
    }

    /**
     * @return array{total:int, has_content:int, content_fetched:int, processed:int, missing_content:int, fetched_not_processed:int}
     */
    private function collectPipelineStats(int|string|null $lawId): array
    {
        $base = Law::query();

        if ($lawId !== null && $lawId !== '') {
            $base->where('id', $lawId);
        }

        $total = (int) (clone $base)->count();
        $hasContent = (int) (clone $base)->where('has_content', true)->count();
        $contentFetched = (int) (clone $base)->whereNotNull('content_fetched_at')->count();
        $processed = (int) (clone $base)->whereNotNull('processed_at')->count();

        return [
            'total' => $total,
            'has_content' => $hasContent,
            'content_fetched' => $contentFetched,
            'processed' => $processed,
            'missing_content' => max($total - $contentFetched, 0),
            'fetched_not_processed' => max($contentFetched - $processed, 0),
        ];
    }

    /**
     * @param  array{total:int, has_content:int, content_fetched:int, processed:int, missing_content:int, fetched_not_processed:int}  $stats
     */
    private function renderPipelineStats(array $stats): void
    {
        $this->info('Law pipeline status:');
        $this->table(
            ['Stage', 'Count'],
            [
                ['Laws in database', $stats['total']],
                ['Marked has_content', $stats['has_content']],
                ['Content fetched (content_fetched_at)', $stats['content_fetched']],
                ['Processed into trees (processed_at) — eligible for export', $stats['processed']],
                ['Missing content (not yet fetched)', $stats['missing_content']],
                ['Fetched but not processed', $stats['fetched_not_processed']],
            ]
        );
    }

    /**
     * @param  array{total:int, has_content:int, content_fetched:int, processed:int, missing_content:int, fetched_not_processed:int}  $stats
     */
    private function renderExportVerification(
        array $stats,
        string $outputDir,
        string $lawsDir,
        int $exported,
        int $eligible,
        bool $limited,
        bool $scopedToLawId,
    ): void {
        $filesOnDisk = File::isDirectory($lawsDir) ? count(File::files($lawsDir)) : 0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Laws in database', $stats['total']],
                ['Eligible for export (processed)', $eligible],
                ['Laws exported (this run)', $exported],
                ['Files on disk in laws/', $filesOnDisk],
                ['Output directory', $outputDir],
            ]
        );

        if ($scopedToLawId || $limited) {
            return;
        }

        $missing = $stats['total'] - $exported;

        if ($missing > 0) {
            $this->warn(sprintf(
                '%d law(s) in the database were not exported because they are not yet processed.',
                $missing
            ));
            $this->line(sprintf(
                '  • %d still need content fetched (run `laws:fetch-contents`)',
                $stats['missing_content']
            ));
            $this->line(sprintf(
                '  • %d have content but are not processed yet (run `laws:process-trees`)',
                $stats['fetched_not_processed']
            ));
        }

        if ($exported !== $eligible) {
            $this->error(sprintf(
                'Export count mismatch: %d eligible processed law(s) but %d file(s) written.',
                $eligible,
                $exported
            ));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function lawPayload(Law $law, string $slug): array
    {
        return [
            'unique_id' => $law->unique_id,
            'slug' => $slug,
            'code' => $law->code,
            'caption' => $law->caption,
            'type' => $law->type,
            'func' => $law->func,
            'base' => $law->base,
            'is_actual' => (bool) $law->is_actual,
            'publ_year' => $law->publ_year,
            'publ_date' => $this->date($law->publ_date),
            'start_date' => $this->date($law->start_date),
            'end_date' => $this->date($law->end_date),
            'act_date' => $this->date($law->act_date),
            'dv' => $law->dv,
            'version' => $law->version,
            'celex' => $law->celex,
            'doc_lead' => $law->doc_lead,
            'seria' => $law->seria,
            'source' => [
                'provider' => 'APIS.BG',
                'unique_id' => $law->unique_id,
                'db_index' => $law->db_index,
            ],
            'fetched_at' => $this->date($law->content_fetched_at),
            'processed_at' => $this->date($law->processed_at),
            'nodes' => $this->buildNodeTree($law->nodes),
        ];
    }

    /**
     * Convert the flat node collection into a nested tree using path hierarchy
     * (parent path = path with the last "/segment" stripped).
     *
     * @param  iterable<LawNode>  $nodes
     * @return list<array<string, mixed>>
     */
    private function buildNodeTree(iterable $nodes): array
    {
        $shaped = [];
        foreach ($nodes as $node) {
            $shaped[$node->path] = [
                'path' => $node->path,
                'p_id' => $node->p_id,
                'caption' => $node->caption,
                'node_type' => $node->node_type,
                'type' => $node->type,
                'field_type' => $node->field_type,
                'level' => $node->level,
                'sort_order' => $node->sort_order,
                'has_in_links' => (bool) $node->has_in_links,
                'is_orphaned' => (bool) $node->is_orphaned,
                'text_markdown' => $node->text_markdown,
                'children' => [],
            ];
        }

        $roots = [];
        foreach ($shaped as &$entry) {
            $parentPath = $this->parentPath((string) $entry['path']);

            if ($parentPath !== null && isset($shaped[$parentPath])) {
                $shaped[$parentPath]['children'][] = &$entry;
            } else {
                $roots[] = &$entry;
            }
        }
        unset($entry);

        return $roots;
    }

    private function parentPath(string $path): ?string
    {
        $pos = strrpos($path, '/');

        return $pos === false ? null : substr($path, 0, $pos);
    }

    /**
     * @return array<string, mixed>
     */
    private function manifestRow(Law $law, string $slug, string $file): array
    {
        return [
            'unique_id' => $law->unique_id,
            'code' => $law->code,
            'slug' => $slug,
            'caption' => $law->caption,
            'type' => $law->type,
            'is_actual' => (bool) $law->is_actual,
            'publ_date' => $this->date($law->publ_date),
            'start_date' => $this->date($law->start_date),
            'end_date' => $this->date($law->end_date),
            'version' => $law->version,
            'file' => $file,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $manifest
     */
    private function writeIndexJson(string $outputDir, array $manifest): void
    {
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'count' => count($manifest),
            'laws' => $manifest,
        ];

        File::put(
            $outputDir.DIRECTORY_SEPARATOR.'index.json',
            $this->encodeJson($payload)
        );
    }

    /**
     * @param  list<array<string, mixed>>  $manifest
     */
    private function writeIndexCsv(string $outputDir, array $manifest): void
    {
        $path = $outputDir.DIRECTORY_SEPARATOR.'index.csv';
        $handle = fopen($path, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open {$path} for writing");
        }

        $columns = ['unique_id', 'code', 'slug', 'caption', 'type', 'is_actual', 'publ_date', 'start_date', 'end_date', 'version', 'file'];
        fputcsv($handle, $columns, escape: '\\');

        foreach ($manifest as $row) {
            $line = array_map(fn (string $col) => $this->csvValue($row[$col] ?? null), $columns);
            fputcsv($handle, $line, escape: '\\');
        }

        fclose($handle);
    }

    private function writeReadme(string $outputDir, int $count): void
    {
        $readme = <<<MD
# Bulgarian Laws — Open Dataset

Generated daily from APIS.BG and committed to this repository.

- `index.json` — machine-readable manifest of every exported law
- `index.csv` — same manifest as a spreadsheet-friendly CSV
- `laws/<slug>.json` — one file per law containing metadata + structured node tree

Each law file includes the structured tree of articles, paragraphs, and items
as parsed by `App\Services\LawTreeProcessor`, with text rendered as Markdown.

**Total laws:** {$count}

## Consuming the data

```bash
# Single law (no clone needed)
curl https://raw.githubusercontent.com/<user>/lawdiff/main/data/laws/<slug>.json

# All laws — clone and iterate
jq '.laws[] | {slug, caption}' data/index.json
```

## Source

Bulgarian legislation data via [legislation.apis.bg](https://legislation.apis.bg/).
The structured tree representation is derived locally; raw text remains the work
of the Bulgarian state and stands in the public domain.
MD;

        File::put($outputDir.DIRECTORY_SEPARATOR.'README.md', $readme."\n");
    }

    /**
     * @param  list<string>  $writtenFiles
     */
    private function prune(string $lawsDir, array $writtenFiles): void
    {
        $kept = array_flip($writtenFiles);
        $existing = File::files($lawsDir);
        $removed = 0;

        foreach ($existing as $file) {
            if (! isset($kept[$file->getFilename()])) {
                File::delete($file->getPathname());
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->info("Pruned {$removed} stale law file(s).");
        }
    }

    private function slugFor(Law $law): string
    {
        $base = Str::slug($law->caption, '-', 'bg');

        if ($base === '') {
            $base = 'law';
        }

        $base = Str::limit($base, 120, '');

        $count = $this->slugUsage[$base] ?? 0;
        $this->slugUsage[$base] = $count + 1;

        if ($count === 0) {
            return $base;
        }

        $this->warn("Slug collision for \"{$law->caption}\" — appending -{$count}");

        return "{$base}-{$count}";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encodeJson(array $payload): string
    {
        return json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        )."\n";
    }

    private function date(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    private function csvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
