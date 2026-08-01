<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Law;
use App\Models\LawNode;

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
