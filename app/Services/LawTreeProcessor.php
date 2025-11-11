<?php

namespace App\Services;

use App\Models\Law;
use App\Models\LawNode;
use League\HTMLToMarkdown\HtmlConverter;

class LawTreeProcessor
{
    protected HtmlConverter $converter;

    protected int $sortOrder = 0;

    public function __construct()
    {
        $this->converter = new HtmlConverter([
            'strip_tags' => true,
            'remove_nodes' => 'script style',
        ]);
    }

    public function process(Law $law): void
    {
        $this->sortOrder = 0;

        // Delete existing nodes for this law
        $law->nodes()->delete();

        // Create a map of paragraph IDs to their text content
        $textMap = $this->buildTextMap($law->content_text ?? []);

        // Track which pIds are used in the structure
        $usedPIds = [];

        // Build and save the tree nodes
        $this->buildAndSaveNodes(
            $law,
            $law->content_structure ?? [],
            $textMap,
            '',
            0,
            $usedPIds
        );

        // Find and save orphaned paragraphs
        $this->saveOrphanedParagraphs($law, $textMap, $usedPIds);
    }

    protected function buildTextMap(array $contentText): array
    {
        $map = [];

        if (isset($contentText['paragraphs']) && is_array($contentText['paragraphs'])) {
            foreach ($contentText['paragraphs'] as $paragraph) {
                if (isset($paragraph['pId']) && isset($paragraph['text'])) {
                    $map[$paragraph['pId']] = [
                        'text' => $this->convertHtmlToMarkdown($paragraph['text']),
                        'type' => $paragraph['type'] ?? null,
                        'fieldType' => $paragraph['fieldType'] ?? null,
                        'hasInLinks' => $paragraph['hasInLinks'] ?? false,
                    ];
                }
            }
        }

        return $map;
    }

    protected function buildAndSaveNodes(
        Law $law,
        array $nodes,
        array $textMap,
        string $parentPath,
        int $level,
        array &$usedPIds
    ): void {
        foreach ($nodes as $node) {
            if (! isset($node['pId'])) {
                continue;
            }

            // Track that this pId is used
            $usedPIds[] = $node['pId'];

            // Build the path for this node
            $pathSegment = $this->buildPathSegment($node['caption'] ?? null, $node['pId']);
            $currentPath = $parentPath ? $parentPath.'/'.$pathSegment : $pathSegment;

            // Get text data if available
            $textData = $textMap[$node['pId']] ?? null;

            // Create and save the node
            LawNode::create([
                'law_id' => $law->id,
                'path' => $currentPath,
                'p_id' => $node['pId'],
                'caption' => $node['caption'] ?? null,
                'text_markdown' => $textData['text'] ?? null,
                'node_type' => $this->determineNodeType($node['caption'] ?? ''),
                'type' => $textData['type'] ?? null,
                'field_type' => $textData['fieldType'] ?? null,
                'has_in_links' => $textData['hasInLinks'] ?? false,
                'sort_order' => $this->sortOrder++,
                'level' => $level,
                'is_orphaned' => false,
            ]);

            // Recursively process children
            if (isset($node['children']) && is_array($node['children']) && count($node['children']) > 0) {
                $this->buildAndSaveNodes(
                    $law,
                    $node['children'],
                    $textMap,
                    $currentPath,
                    $level + 1,
                    $usedPIds
                );
            }
        }
    }

    protected function saveOrphanedParagraphs(Law $law, array $textMap, array $usedPIds): void
    {
        foreach ($textMap as $pId => $data) {
            if (! in_array($pId, $usedPIds)) {
                $pathSegment = $this->buildOrphanedPath($data);

                LawNode::create([
                    'law_id' => $law->id,
                    'path' => $pathSegment,
                    'p_id' => $pId,
                    'caption' => null,
                    'text_markdown' => $data['text'],
                    'node_type' => $this->determineOrphanedNodeType($data),
                    'type' => $data['type'],
                    'field_type' => $data['fieldType'],
                    'has_in_links' => $data['hasInLinks'],
                    'sort_order' => $this->sortOrder++,
                    'level' => 0,
                    'is_orphaned' => true,
                ]);
            }
        }
    }

    protected function buildPathSegment(?string $caption, int $pId): string
    {
        if (! $caption) {
            return 'NODE_'.$pId;
        }

        // Normalize the caption to create a path segment
        $normalized = mb_strtoupper($caption);

        // Remove ГЛАВА and РАЗДЕЛ prefixes
        $normalized = preg_replace('/^ГЛАВА\s*/u', '', $normalized);
        $normalized = preg_replace('/^РАЗДЕЛ\s*/u', '', $normalized);

        // Remove all non-alphanumeric characters
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', '', $normalized);

        return $normalized ?: 'NODE_'.$pId;
    }

    protected function buildOrphanedPath(array $data): string
    {
        $type = $data['type'] ?? 0;
        $fieldType = $data['fieldType'] ?? 0;

        // Try to determine what kind of orphaned node this is
        if ($fieldType === 1) {
            return 'ЗАГЛАВИЕ';
        }

        if ($fieldType === 2) {
            return 'ПУБЛ_ИНФО';
        }

        if ($fieldType === 9) {
            return 'ЗАБЕЛЕЖКА_'.($data['pId'] ?? uniqid());
        }

        return 'ORPHAN_'.($type ?? 0).'_'.($data['pId'] ?? uniqid());
    }

    protected function determineNodeType(string $caption): string
    {
        $upper = mb_strtoupper($caption);

        if (str_contains($upper, 'ГЛАВА') || str_starts_with($upper, 'ГЛ.')) {
            return 'chapter';
        }

        if (str_contains($upper, 'РАЗДЕЛ') || str_starts_with($upper, 'РАЗД.')) {
            return 'section';
        }

        if (str_contains($upper, 'ЧЛ.') || str_starts_with($upper, 'ЧЛ.')) {
            return 'article';
        }

        if (str_contains($upper, 'АЛ.') || str_starts_with($upper, 'АЛ.')) {
            return 'paragraph';
        }

        if (str_contains($upper, 'Т.') || preg_match('/^\d+\./', $caption)) {
            return 'point';
        }

        return 'unknown';
    }

    protected function determineOrphanedNodeType(array $data): string
    {
        $fieldType = $data['fieldType'] ?? 0;

        return match ($fieldType) {
            1 => 'title',
            2 => 'publication_info',
            9 => 'note',
            default => 'metadata',
        };
    }

    protected function convertHtmlToMarkdown(string $html): string
    {
        try {
            $markdown = $this->converter->convert($html);

            // Clean up the markdown
            $markdown = trim($markdown);

            // Remove excessive newlines
            $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);

            return $markdown;
        } catch (\Exception $e) {
            // If conversion fails, return cleaned HTML
            return strip_tags($html);
        }
    }
}
