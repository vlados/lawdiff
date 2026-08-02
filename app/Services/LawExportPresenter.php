<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Law;
use App\Models\LawNode;
use App\Models\LawVersion;

/**
 * Shared presentation layer for the public dataset export and any topical
 * fork of it (see `laws:export-public` and `laws:export-ieps`).
 */
class LawExportPresenter
{
    /**
     * Law metadata common to every export. Deliberately excludes the slug,
     * which each export resolves for itself.
     *
     * @return array<string, mixed>
     */
    public function metadata(Law $law): array
    {
        return [
            'unique_id' => $law->unique_id,
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
        ];
    }

    /**
     * Convert a flat node collection into a nested tree.
     *
     * Nesting follows parent_id, which is the only thing that knows a глава
     * contains its articles: chapters are deliberately absent from their
     * descendants' citation paths, so stripping the last "/segment" off a path
     * would orphan every article in the law. Path ancestry stays as the
     * fallback for rows predating parent_id.
     *
     * Nodes whose parent is absent from the collection surface as roots, which
     * is what lets a filtered subset still render as a valid tree.
     *
     * @param  iterable<LawNode>  $nodes
     * @return list<array<string, mixed>>
     */
    public function buildNodeTree(iterable $nodes): array
    {
        $shaped = [];
        $pathById = [];

        foreach ($nodes as $node) {
            $pathById[$node->id] = $node->path;

            $shaped[$node->path] = [
                'path' => $node->path,
                'parent_path' => null,
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
                'parent_id' => $node->parent_id,
            ];
        }

        $roots = [];
        foreach ($shaped as &$entry) {
            $parentPath = $entry['parent_id'] !== null
                ? ($pathById[$entry['parent_id']] ?? null)
                : $this->parentPath((string) $entry['path']);

            unset($entry['parent_id']);

            if ($parentPath !== null && isset($shaped[$parentPath])) {
                $entry['parent_path'] = $parentPath;
                $shaped[$parentPath]['children'][] = &$entry;
            } else {
                $roots[] = &$entry;
            }
        }
        unset($entry);

        return $roots;
    }

    /**
     * The redaction the exported text is, and every redaction known of it.
     *
     * publ_date/start_date at the top level already say when the law last
     * changed, but they say it about the law rather than about the text below
     * them. Naming the version the nodes were built from is what lets a
     * consumer tell current text from stale, and listing the rest is what makes
     * a new redaction visible as a diff instead of a silent rewrite.
     *
     * @return array<string, mixed>
     */
    public function versionInfo(Law $law): array
    {
        $law->loadMissing(['currentVersion', 'versions']);

        return [
            'current' => $law->currentVersion === null ? null : [
                'changed_at' => $this->date($law->currentVersion->changed_at),
                'label' => $law->currentVersion->label(),
                'dv' => $law->currentVersion->dv,
                'publ_year' => $law->currentVersion->publ_year,
                'valid_from' => $this->date($law->currentVersion->valid_from),
                'valid_to' => $this->date($law->currentVersion->valid_to),
                'source_hash' => $law->currentVersion->source_hash,
            ],
            'known' => $law->versions
                ->map(fn (LawVersion $version): array => [
                    'changed_at' => $this->date($version->changed_at),
                    'label' => $version->label(),
                    'valid_from' => $this->date($version->valid_from),
                    'valid_to' => $this->date($version->valid_to),
                ])
                ->all(),
        ];
    }

    /**
     * The law's citation graph, addressed by path so it survives a reprocess:
     * node ids are rebuilt delete-then-insert and mean nothing across runs.
     *
     * @return list<array<string, mixed>>
     */
    public function references(Law $law): array
    {
        return $law->references()
            ->with(['sourceNode:id,path', 'targetNode:id,path'])
            ->orderBy('source_node_id')
            ->orderBy('position')
            ->get()
            ->map(fn ($reference): array => [
                'source_path' => $reference->sourceNode?->path,
                'target_path' => $reference->target_path,
                'target_act' => $reference->target_act_name,
                'citation' => $reference->citation_text,
                'relation' => $reference->relation_type,
                'status' => $reference->resolution_status,
            ])
            ->all();
    }

    public function parentPath(string $path): ?string
    {
        $pos = strrpos($path, '/');

        return $pos === false ? null : substr($path, 0, $pos);
    }

    /**
     * Every ancestor of a path, outermost first. "A/B/C" yields ["A", "A/B"].
     *
     * @return list<string>
     */
    public function ancestorPaths(string $path): array
    {
        $ancestors = [];

        while (($path = $this->parentPath($path)) !== null) {
            $ancestors[] = $path;
        }

        return array_reverse($ancestors);
    }

    public function date(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public function encodeJson(array $payload): string
    {
        return json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        )."\n";
    }
}
