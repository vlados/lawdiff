<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Law;
use App\Models\LawNode;
use App\Models\LegalReference;

/**
 * Turns the citations inside provision text into edges between nodes.
 *
 * This is the layer no embedding replaces. A provision is applicable because
 * another one points at it — a sanction naming the duty, a definition naming
 * the term, an exception naming the rule — and that link is stated, not
 * implied. Similarity search can guess at it; only the citation proves it.
 */
class LegalReferenceExtractor
{
    public function __construct(
        protected LegalCitationParser $parser
    ) {}

    /**
     * Rebuild every reference this law makes. Returns the number stored.
     */
    public function extract(Law $law): int
    {
        $law->references()->delete();

        $nodeIdsByPath = $law->nodes()->pluck('id', 'path')->all();
        $rows = [];
        $now = now();

        $nodes = $law->nodes()
            ->whereNotNull('text_markdown')
            ->orderBy('sort_order')
            ->get();

        foreach ($nodes as $node) {
            foreach ($this->parser->scan((string) $node->text_markdown) as $citation) {
                $row = $this->toRow($law, $node, $citation, $nodeIdsByPath);

                if ($row !== null) {
                    $rows[] = $row + ['created_at' => $now, 'updated_at' => $now];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            LegalReference::insert($chunk);
        }

        return count($rows);
    }

    /**
     * @param  array<string, int>  $nodeIdsByPath
     * @return array<string, mixed>|null
     */
    private function toRow(Law $law, LawNode $node, array $citation, array $nodeIdsByPath): ?array
    {
        $external = $citation['external_act'];

        $targetPath = $citation['is_relative'] && $external === null
            ? $this->parser->resolveRelative($node->path, $citation['components'])
            : $citation['canonical_path'];

        if ($targetPath === null) {
            return null;
        }

        // A citation naming another act says nothing about a path in this one.
        if ($external !== null) {
            return [
                'law_id' => $law->id,
                'source_node_id' => $node->id,
                'target_node_id' => null,
                'target_law_id' => null,
                'target_path' => $targetPath,
                'target_act_name' => $external,
                'citation_text' => $citation['text'],
                'relation_type' => LegalReference::RELATION_REFERS_TO,
                'resolution_status' => LegalReference::STATUS_UNRESOLVED_EXTERNAL,
                'position' => $citation['offset'],
            ];
        }

        $targetPath = $this->preferLocalBlock($node, $citation, $targetPath, $nodeIdsByPath);

        if ($this->isSelfOrAncestor($node->path, $targetPath)) {
            return null;
        }

        $targetId = $nodeIdsByPath[$targetPath] ?? null;

        return [
            'law_id' => $law->id,
            'source_node_id' => $node->id,
            'target_node_id' => $targetId,
            'target_law_id' => $targetId === null ? null : $law->id,
            'target_path' => $targetPath,
            'target_act_name' => null,
            'citation_text' => $citation['text'],
            'relation_type' => LegalReference::RELATION_REFERS_TO,
            'resolution_status' => $targetId === null
                ? LegalReference::STATUS_UNRESOLVED_INTERNAL
                : LegalReference::STATUS_RESOLVED,
            'position' => $citation['offset'],
        ];
    }

    /**
     * A "§ 6" citation names no block, but § numbering restarts in each of
     * them, so the § in the citing text's own block wins when it exists. Kept
     * out of the parser because only the stored node set knows which paths are
     * real.
     *
     * @param  array<string, int>  $nodeIdsByPath
     */
    private function preferLocalBlock(LawNode $node, array $citation, string $targetPath, array $nodeIdsByPath): string
    {
        if (! isset($citation['components']['section'])) {
            return $targetPath;
        }

        $container = $this->parser->containerPrefix($node->path);

        if ($container === null) {
            return $targetPath;
        }

        $local = $container.'/'.$targetPath;

        return isset($nodeIdsByPath[$local]) ? $local : $targetPath;
    }

    /**
     * Article text opens with its own number ("Чл. 80а. Водачът..."), which
     * scans as a citation of itself. Edges to a node's own ancestors are
     * dropped for the same reason: they restate where the text already is
     * rather than pointing anywhere new.
     */
    private function isSelfOrAncestor(string $sourcePath, string $targetPath): bool
    {
        return $sourcePath === $targetPath || str_starts_with($sourcePath, $targetPath.'/');
    }
}
