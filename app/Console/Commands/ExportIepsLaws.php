<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Law;
use App\Models\LawNode;
use App\Services\LawExportPresenter;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

/**
 * Topical fork of `laws:export-public`, scoped to индивидуални електрически
 * превозни средства (ИЕПС).
 *
 * A provision is exported when a keyword pattern matches its text or when it is
 * named in the allowlist; each such node brings its full subtree and its
 * ancestor chain, so every exported provision reads on its own.
 *
 * Slugs are carried through from the public export rather than recomputed.
 * `ExportPublicLaws::slugFor()` disambiguates collisions with a per-run counter,
 * so recomputing over a narrower corpus could assign a different slug to the
 * same law and break cross-referencing between the two datasets.
 */
class ExportIepsLaws extends Command
{
    protected $signature = 'laws:export-ieps
                            {--output= : Output directory (default: <repo>/data/ieps)}
                            {--source-index= : Public export index.json used for slug alignment (default: <repo>/data/index.json)}
                            {--prune : Delete law files that no longer match}
                            {--skip-floor : Bypass the floor guard (debugging only)}';

    protected $description = 'Export every ИЕПС-related provision as a standalone dataset forked from the public export';

    public function handle(LawExportPresenter $presenter): int
    {
        $outputDir = $this->option('output') ?: base_path('data'.DIRECTORY_SEPARATOR.'ieps');
        $sourceIndex = $this->option('source-index') ?: base_path('data'.DIRECTORY_SEPARATOR.'index.json');
        $lawsDir = $outputDir.DIRECTORY_SEPARATOR.'laws';

        if ($this->wouldClobberSource($outputDir, $sourceIndex)) {
            $this->error('Refusing to write into the public export directory — that would overwrite its README.md and index.json.');

            return self::FAILURE;
        }

        /** @var list<string> $patterns */
        $patterns = config('ieps.keywords', []);

        /** @var array<string, list<string>> $allowlist */
        $allowlist = config('ieps.include', []);

        if (($invalid = $this->invalidPatterns($patterns)) !== []) {
            $this->error('Invalid keyword pattern(s) in config/ieps.php: '.implode(', ', $invalid));

            return self::FAILURE;
        }

        try {
            $slugs = $this->loadSlugMap($sourceIndex);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Scanning corpus for ИЕПС provisions...');
        $this->line(sprintf('  %d keyword pattern(s), %d allowlisted law(s)', count($patterns), count($allowlist)));

        $payloads = [];
        $unaligned = [];
        $scanned = 0;

        $pending = [];
        foreach ($allowlist as $slug => $prefixes) {
            foreach ($prefixes as $prefix) {
                $pending[$slug][$prefix] = true;
            }
        }

        $query = Law::query()->whereNotNull('processed_at');

        $progress = $this->output->createProgressBar((int) $query->count());
        $progress->start();

        $query->chunkById(50, function (Collection $laws) use (
            $presenter,
            $patterns,
            $allowlist,
            $slugs,
            &$payloads,
            &$pending,
            &$unaligned,
            &$scanned,
            $progress,
        ): void {
            foreach ($laws as $law) {
                $scanned++;
                $progress->advance();

                $slug = $slugs[$law->unique_id] ?? null;

                $nodes = LawNode::query()
                    ->where('law_id', $law->id)
                    ->orderBy('sort_order')
                    ->get();

                if ($nodes->isEmpty()) {
                    continue;
                }

                $seeds = $this->matchedPaths($nodes, $patterns);
                $matched = array_keys($seeds);
                $allowlisted = [];

                foreach ($slug !== null ? ($allowlist[$slug] ?? []) : [] as $prefix) {
                    foreach ($this->resolvePrefix($nodes, $prefix) as $path) {
                        $seeds[$path] = true;
                        $allowlisted[] = $path;
                        unset($pending[$slug][$prefix]);
                    }
                }

                if ($seeds === []) {
                    continue;
                }

                if ($slug === null) {
                    $unaligned[] = "{$law->caption} (unique_id {$law->unique_id})";

                    continue;
                }

                $kept = $this->expand($presenter, $nodes, $seeds);

                $payloads[$slug] = [
                    'law' => $law,
                    'slug' => $slug,
                    'matched' => $matched,
                    'allowlisted' => array_values(array_unique($allowlisted)),
                    'total_nodes' => $nodes->count(),
                    'nodes' => $nodes->filter(fn (LawNode $node): bool => isset($kept[$node->path]))->values(),
                ];
            }
        });

        $progress->finish();
        $this->newLine(2);

        if ($unaligned !== []) {
            $this->error('These laws contain ИЕПС provisions but are missing from the public index — run `laws:export-public` first:');
            foreach ($unaligned as $caption) {
                $this->line("  • {$caption}");
            }

            return self::FAILURE;
        }

        if (($stale = $this->staleAllowlistEntries($pending)) !== []) {
            $this->error('Allowlist entries in config/ieps.php resolved to nothing (renumbered, renamed, or law not in corpus):');
            foreach ($stale as $entry) {
                $this->line("  • {$entry}");
            }

            return self::FAILURE;
        }

        ksort($payloads);

        $manifest = array_map(
            fn (array $data): array => $this->manifestRow($presenter, $data),
            array_values($payloads)
        );

        $nodeCount = (int) array_sum(array_column($manifest, 'exported_nodes'));
        $matchedCount = (int) array_sum(array_column($manifest, 'matched_nodes'));

        $this->renderSummary($scanned, $manifest, $nodeCount, $outputDir);

        // Guard before writing: a tripped floor must never leave the dataset gutted.
        if (($floor = $this->enforceFloor(count($manifest), $nodeCount, $matchedCount)) !== self::SUCCESS) {
            return $floor;
        }

        File::ensureDirectoryExists($lawsDir);

        $writtenFiles = [];

        foreach ($payloads as $slug => $data) {
            File::put(
                $lawsDir.DIRECTORY_SEPARATOR."{$slug}.json",
                $presenter->encodeJson($this->lawPayload($presenter, $data))
            );

            $writtenFiles[] = "{$slug}.json";
        }

        $this->writeIndexJson($presenter, $outputDir, $manifest, $nodeCount, $patterns, $allowlist);
        $this->writeIndexCsv($outputDir, $manifest);
        $this->writeReadme($outputDir, count($manifest), $nodeCount);

        if ($this->option('prune')) {
            $this->prune($lawsDir, $writtenFiles);
        }

        $this->info('✓ ИЕПС export complete.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $patterns
     * @return list<string>
     */
    private function invalidPatterns(array $patterns): array
    {
        $invalid = [];

        foreach ($patterns as $pattern) {
            if (@preg_match($pattern, '') === false) {
                $invalid[] = $pattern;
            }
        }

        return $invalid;
    }

    /**
     * Map unique_id => public slug, so both datasets agree on filenames.
     *
     * @return array<int, string>
     */
    private function loadSlugMap(string $sourceIndex): array
    {
        if (! File::exists($sourceIndex)) {
            throw new \RuntimeException(
                "Public index not found at {$sourceIndex}. Run `laws:export-public` first, or pass --source-index."
            );
        }

        $decoded = json_decode(File::get($sourceIndex), true);

        if (! is_array($decoded) || ! isset($decoded['laws']) || ! is_array($decoded['laws'])) {
            throw new \RuntimeException("Malformed public index at {$sourceIndex}: expected a 'laws' array.");
        }

        $map = [];

        foreach ($decoded['laws'] as $row) {
            if (is_array($row) && isset($row['unique_id'], $row['slug'])) {
                $map[(int) $row['unique_id']] = (string) $row['slug'];
            }
        }

        return $map;
    }

    /**
     * Paths whose text matches any keyword pattern.
     *
     * @param  iterable<LawNode>  $nodes
     * @param  list<string>  $patterns
     * @return array<string, true>
     */
    private function matchedPaths(iterable $nodes, array $patterns): array
    {
        $matched = [];

        foreach ($nodes as $node) {
            $text = $node->text_markdown;

            if ($text === null || $text === '') {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text) === 1) {
                    $matched[$node->path] = true;
                    break;
                }
            }
        }

        return $matched;
    }

    /**
     * Concrete paths covered by one allowlisted prefix. The trailing separator
     * matters: prefix "ЧЛ80" must not swallow "ЧЛ80А".
     *
     * @param  iterable<LawNode>  $nodes
     * @return list<string>
     */
    private function resolvePrefix(iterable $nodes, string $prefix): array
    {
        $paths = [];

        foreach ($nodes as $node) {
            if ($node->path === $prefix || str_starts_with($node->path, $prefix.'/')) {
                $paths[] = $node->path;
            }
        }

        return $paths;
    }

    /**
     * Every seed keeps itself, its full subtree, and its ancestor chain.
     *
     * @param  iterable<LawNode>  $nodes
     * @param  array<string, true>  $seeds
     * @return array<string, true>
     */
    private function expand(LawExportPresenter $presenter, iterable $nodes, array $seeds): array
    {
        $kept = [];

        foreach ($nodes as $node) {
            foreach ([...$presenter->ancestorPaths($node->path), $node->path] as $candidate) {
                if (isset($seeds[$candidate])) {
                    $kept[$node->path] = true;
                    break;
                }
            }
        }

        foreach (array_keys($seeds) as $seed) {
            foreach ($presenter->ancestorPaths((string) $seed) as $ancestor) {
                $kept[$ancestor] = true;
            }
        }

        return $kept;
    }

    /**
     * @param  array<string, array<string, true>>  $pending
     * @return list<string>
     */
    private function staleAllowlistEntries(array $pending): array
    {
        $stale = [];

        foreach ($pending as $slug => $prefixes) {
            foreach (array_keys($prefixes) as $prefix) {
                $stale[] = "{$slug} => {$prefix}";
            }
        }

        return $stale;
    }

    /**
     * @param  array{law: Law, slug: string, matched: list<string>, allowlisted: list<string>, total_nodes: int, nodes: Collection<int, LawNode>}  $data
     * @return array<string, mixed>
     */
    private function lawPayload(LawExportPresenter $presenter, array $data): array
    {
        $matched = $data['matched'];
        sort($matched);

        $allowlisted = $data['allowlisted'];
        sort($allowlisted);

        return [
            'topic' => 'ieps',
            'slug' => $data['slug'],
            ...$presenter->metadata($data['law']),
            'ieps' => [
                'matched_paths' => $matched,
                'allowlisted_paths' => $allowlisted,
                'exported_nodes' => $data['nodes']->count(),
                'total_nodes_in_law' => $data['total_nodes'],
                'full_text' => 'data/laws/'.$data['slug'].'.json',
            ],
            'nodes' => $presenter->buildNodeTree($data['nodes']),
        ];
    }

    /**
     * @param  array{law: Law, slug: string, matched: list<string>, allowlisted: list<string>, total_nodes: int, nodes: Collection<int, LawNode>}  $data
     * @return array<string, mixed>
     */
    private function manifestRow(LawExportPresenter $presenter, array $data): array
    {
        $law = $data['law'];

        return [
            'unique_id' => $law->unique_id,
            'code' => $law->code,
            'slug' => $data['slug'],
            'caption' => $law->caption,
            'type' => $law->type,
            'is_actual' => (bool) $law->is_actual,
            'publ_date' => $presenter->date($law->publ_date),
            'start_date' => $presenter->date($law->start_date),
            'end_date' => $presenter->date($law->end_date),
            'version' => $law->version,
            'matched_nodes' => count($data['matched']),
            'exported_nodes' => $data['nodes']->count(),
            'total_nodes_in_law' => $data['total_nodes'],
            'file' => 'laws/'.$data['slug'].'.json',
            'full_text' => 'data/laws/'.$data['slug'].'.json',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $manifest
     * @param  list<string>  $patterns
     * @param  array<string, list<string>>  $allowlist
     */
    private function writeIndexJson(
        LawExportPresenter $presenter,
        string $outputDir,
        array $manifest,
        int $nodeCount,
        array $patterns,
        array $allowlist,
    ): void {
        $payload = [
            'topic' => 'ieps',
            'topic_label' => 'Индивидуални електрически превозни средства',
            'generated_at' => now()->toIso8601String(),
            'count' => count($manifest),
            'node_count' => $nodeCount,
            'criteria' => [
                'keywords' => $patterns,
                'allowlist' => $allowlist,
                'rule' => 'A selected node is exported together with its full subtree and its ancestor chain.',
            ],
            'laws' => $manifest,
        ];

        File::put($outputDir.DIRECTORY_SEPARATOR.'index.json', $presenter->encodeJson($payload));
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

        $columns = ['unique_id', 'code', 'slug', 'caption', 'type', 'is_actual', 'publ_date', 'start_date', 'end_date', 'version', 'matched_nodes', 'exported_nodes', 'total_nodes_in_law', 'file', 'full_text'];
        fputcsv($handle, $columns, escape: '\\');

        foreach ($manifest as $row) {
            $line = array_map(fn (string $col): string => $this->csvValue($row[$col] ?? null), $columns);
            fputcsv($handle, $line, escape: '\\');
        }

        fclose($handle);
    }

    private function writeReadme(string $outputDir, int $count, int $nodeCount): void
    {
        $readme = <<<MD
        # ИЕПС — Индивидуални електрически превозни средства

        Topical fork of the [main dataset](../README.md), regenerated daily alongside it.

        Every provision of Bulgarian legislation that regulates индивидуални електрически
        превозни средства (individual electric vehicles — e-scooters and similar).

        - `index.json` — manifest, plus the exact criteria used to select provisions
        - `index.csv` — same manifest as CSV
        - `laws/<slug>.json` — the ИЕПС provisions of one law

        Slugs match the main dataset. Each entry carries a `full_text` pointer to the
        complete law at `data/laws/<slug>.json` (repo-relative).

        **Laws:** {$count} · **Provisions:** {$nodeCount}

        ## Selection rule

        A node is selected when its text matches a keyword pattern, or when it is named in
        the allowlist in `config/ieps.php`. Each selected node is exported together with
        its **full subtree** and its **ancestor chain**, so every provision reads on its
        own — a matched алинея carries its точки down and its член caption up.

        The allowlist exists because keyword matching alone is not sufficient: чл. 80а,
        ал. 8 and ал. 10 ЗДвП govern the ИЕПС registry itself but refer to their own
        subject indirectly, as "регистъра по ал. 5", so no keyword can reach them.

        ## Scope

        Derived from the same corpus as the main dataset — the Закони folder of
        legislation.apis.bg — and reflecting the law currently in force.

        **Not covered:** наредби, including the общински наредби under чл. 80а, ал. 4 ЗДвП
        where much of the operative registration detail lives; кодекси; правилници. There
        is no amendment overlay and no version history.

        The `full_text` pointer on each law always resolves. What is not followed are
        references *inside* the provision text: чл. 80а, ал. 5 invokes "системата по чл. 52а
        от Закона за електронното управление", and the exporter does not pull that article
        in — read it in the main dataset.
        MD;

        File::put($outputDir.DIRECTORY_SEPARATOR.'README.md', $readme."\n");
    }

    /**
     * @param  list<string>  $writtenFiles
     */
    private function prune(string $lawsDir, array $writtenFiles): void
    {
        $kept = array_flip($writtenFiles);
        $removed = 0;

        foreach (File::files($lawsDir) as $file) {
            if (! isset($kept[$file->getFilename()])) {
                File::delete($file->getPathname());
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->info("Pruned {$removed} stale law file(s).");
        }
    }

    /**
     * @param  list<array<string, mixed>>  $manifest
     */
    private function renderSummary(int $scanned, array $manifest, int $nodeCount, string $outputDir): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Laws scanned', $scanned],
                ['Laws with ИЕПС provisions', count($manifest)],
                ['Provisions exported', $nodeCount],
                ['Output directory', $outputDir],
            ]
        );

        foreach ($manifest as $row) {
            $this->line(sprintf(
                '  • %s — %d matched, %d exported of %d',
                $row['caption'],
                $row['matched_nodes'],
                $row['exported_nodes'],
                $row['total_nodes_in_law']
            ));
        }
    }

    /**
     * `matched` is guarded separately from `nodes`: the allowlisted subtrees alone
     * account for most of the node count, so a total collapse of keyword matching
     * would otherwise slip past a combined threshold.
     */
    private function enforceFloor(int $laws, int $nodes, int $matched): int
    {
        if ($this->option('skip-floor')) {
            $this->warn('Floor guard skipped.');

            return self::SUCCESS;
        }

        $breaches = [];

        foreach ([
            'law(s)' => [$laws, (int) config('ieps.floor.laws', 0)],
            'provision(s)' => [$nodes, (int) config('ieps.floor.nodes', 0)],
            'keyword match(es)' => [$matched, (int) config('ieps.floor.matched', 0)],
        ] as $label => [$actual, $minimum]) {
            if ($actual < $minimum) {
                $breaches[] = "{$actual} {$label}, expected at least {$minimum}";
            }
        }

        if ($breaches === []) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('Floor guard tripped.');

        foreach ($breaches as $breach) {
            $this->line("  • {$breach}");
        }

        $this->line('  Nothing was written; the previous dataset is untouched.');
        $this->line('  The corpus may be stale, or ИЕПС provisions may have been renumbered.');
        $this->line('  Investigate before committing — do not lower the floor to make this pass.');

        return self::FAILURE;
    }

    private function wouldClobberSource(string $outputDir, string $sourceIndex): bool
    {
        $normalise = static function (string $path): string {
            $real = realpath($path);

            return rtrim($real !== false ? $real : $path, DIRECTORY_SEPARATOR);
        };

        return $normalise($outputDir) === $normalise(dirname($sourceIndex));
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
