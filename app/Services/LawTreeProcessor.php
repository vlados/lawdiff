<?php

namespace App\Services;

use App\Models\Law;
use App\Models\LawNode;
use League\HTMLToMarkdown\HtmlConverter;

class LawTreeProcessor
{
    protected HtmlConverter $converter;

    protected int $sortOrder = 0;

    public function __construct(
        protected LawPathBuilder $pathBuilder
    ) {
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

        // Parse and split article text into алинеи, точки, букви
        $this->parseAndSplitArticles($law);
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

            $caption = $node['caption'] ?? '';
            $nodeType = $this->pathBuilder->determineNodeType($caption);

            // Skip ГЛАВА, РАЗДЕЛ, and transitional section headers - don't create them, but process their children
            if ($this->pathBuilder->shouldSkipNode($nodeType)) {
                // Process children with the same parent path (skip this node in the path)
                if (isset($node['children']) && is_array($node['children']) && count($node['children']) > 0) {
                    $this->buildAndSaveNodes(
                        $law,
                        $node['children'],
                        $textMap,
                        $parentPath,
                        $level,
                        $usedPIds
                    );
                }

                continue;
            }

            // Build the path for this node
            $pathSegment = $this->pathBuilder->buildSegment($caption, $node['pId']);
            $currentPath = $parentPath ? $parentPath.'/'.$pathSegment : $pathSegment;

            // Get text data if available
            $textData = $textMap[$node['pId']] ?? null;

            // Create and save the node
            LawNode::create([
                'law_id' => $law->id,
                'path' => $currentPath,
                'p_id' => $node['pId'],
                'caption' => $caption ?: null,
                'text_markdown' => $textData['text'] ?? null,
                'node_type' => $nodeType,
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
                $data['pId'] = $pId;
                $pathSegment = $this->pathBuilder->buildOrphanedPath($data);

                LawNode::create([
                    'law_id' => $law->id,
                    'path' => $pathSegment,
                    'p_id' => $pId,
                    'caption' => null,
                    'text_markdown' => $data['text'],
                    'node_type' => $this->pathBuilder->determineOrphanedNodeType($data),
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

    protected function convertHtmlToMarkdown(string $html): string
    {
        try {
            // Pre-process: Remove anchor tags and other non-semantic tags
            $html = preg_replace('/<a\s+name="[^"]*"><\/a>/', '', $html);
            $html = preg_replace('/<span[^>]*>/', '', $html);
            $html = preg_replace('/<\/span>/', '', $html);

            // Convert to markdown
            $markdown = $this->converter->convert($html);

            // Clean up the markdown
            $markdown = trim($markdown);

            // Remove excessive newlines
            $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);

            // Remove any remaining HTML tags
            $markdown = strip_tags($markdown);

            return $markdown;
        } catch (\Exception $e) {
            // If conversion fails, return cleaned HTML
            return strip_tags($html);
        }
    }

    protected function parseAndSplitArticles(Law $law): void
    {
        // Get all article and transitional_paragraph nodes that have text to parse
        $articles = $law->nodes()
            ->whereIn('node_type', ['article', 'transitional_paragraph'])
            ->whereNotNull('text_markdown')
            ->orderBy('sort_order')
            ->get();

        foreach ($articles as $article) {
            $this->splitArticleIntoSubnodes($law, $article);
        }
    }

    protected function splitArticleIntoSubnodes(Law $law, LawNode $article): void
    {
        $text = $article->text_markdown;

        if (! $text) {
            return;
        }

        // Check if this node has алинеи (paragraphs) marked with (1), (2), etc.
        // Pattern can be: " (1)" inline or "\n\n(1)" on new line
        // Both regular articles (чл.) and transitional paragraphs (§) can have алинеи
        if (preg_match('/(?:\s\((\d+)\)|\n\n\((\d+)\))/u', $text)) {
            $this->parseAlinees($law, $article, $text);

            return;
        }

        // Check if this node has точки (points) marked with 1., 2., etc.
        // Pattern matches: "\n\n 1." (regular) or "\n\n1\." (escaped period in markdown)
        if (preg_match('/\n\n\s*\d+(\\\\\.|\\.)/u', $text)) {
            $this->parsePoints($law, $article, $text);

            return;
        }

        // Check if this node has букви (letters) marked with а), б), etc.
        if (preg_match('/\n\n[а-я]\)/u', $text)) {
            $this->parseLetters($law, $article, $text);
        }
    }

    protected function parseAlinees(Law $law, LawNode $article, string $text): void
    {
        // Normalize: Convert inline " (N)" or " (Nа)" to "\n\n(N)" for consistent parsing
        // Pattern matches (1), (2), (5а), (5б), etc.
        $text = preg_replace('/\s+\((\d+[а-я]?)\)/u', "\n\n($1)", $text);

        // Split text by алинея pattern: \n\n(1), \n\n(2), \n\n(5а), etc.
        $parts = preg_split('/\n\n\((\d+[а-я]?)\)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        // First part is the article introduction (before first алинея)
        $introduction = trim($parts[0]);

        // Update the article node to only contain the introduction
        $article->update(['text_markdown' => $introduction ?: null]);

        // Process алинеи
        for ($i = 1; $i < count($parts); $i += 2) {
            if (! isset($parts[$i]) || ! isset($parts[$i + 1])) {
                break;
            }

            $alineaNumber = $parts[$i]; // Keep as string to preserve letter suffixes like "5а"
            $alineaText = trim($parts[$i + 1]);

            // Create алинея node
            $alineaPath = $this->pathBuilder->buildAlineaPath($article->path, $alineaNumber);

            $alineaNode = LawNode::create([
                'law_id' => $law->id,
                'path' => $alineaPath,
                'p_id' => $article->p_id,
                'caption' => null,
                'text_markdown' => $alineaText,
                'node_type' => 'paragraph',
                'type' => $article->type,
                'field_type' => $article->field_type,
                'has_in_links' => $article->has_in_links,
                'sort_order' => $this->sortOrder++,
                'level' => $article->level + 1,
                'is_orphaned' => false,
            ]);

            // Check if this алинея contains точки or букви
            if (preg_match('/\n\n\s*\d+(\\\\\.|\\.)/u', $alineaText)) {
                $this->parsePoints($law, $alineaNode, $alineaText);
            } elseif (preg_match('/\n\n[а-я]\)/u', $alineaText)) {
                $this->parseLetters($law, $alineaNode, $alineaText);
            }
        }
    }

    protected function parsePoints(Law $law, LawNode $parent, string $text): void
    {
        // Split text by точка pattern: \n\n 1. or \n\n1\. (escaped)
        // Pattern matches optional space and either regular or escaped period
        $parts = preg_split('/\n\n\s*(\d+)(\\\\\.|\.)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        // First part is introduction (before first точка)
        $introduction = trim($parts[0]);

        // Update the parent node to only contain the introduction
        $parent->update(['text_markdown' => $introduction ?: null]);

        // Process точки
        // With 2 capture groups, array has: [intro, digit, period, text, digit, period, text...]
        for ($i = 1; $i < count($parts); $i += 3) {
            if (! isset($parts[$i]) || ! isset($parts[$i + 2])) {
                break;
            }

            $pointNumber = (int) $parts[$i];
            $pointText = trim($parts[$i + 2]);

            // Create точка node
            $pointPath = $this->pathBuilder->buildPointPath($parent->path, $pointNumber);

            $pointNode = LawNode::create([
                'law_id' => $law->id,
                'path' => $pointPath,
                'p_id' => $parent->p_id,
                'caption' => null,
                'text_markdown' => $pointText,
                'node_type' => 'point',
                'type' => $parent->type,
                'field_type' => $parent->field_type,
                'has_in_links' => $parent->has_in_links,
                'sort_order' => $this->sortOrder++,
                'level' => $parent->level + 1,
                'is_orphaned' => false,
            ]);

            // Check if this точка contains букви
            if (preg_match('/\n\n[а-я]\)/u', $pointText)) {
                $this->parseLetters($law, $pointNode, $pointText);
            }
        }
    }

    protected function parseLetters(Law $law, LawNode $parent, string $text): void
    {
        // Split text by буква pattern: \n\nа), \n\nб), etc.
        $parts = preg_split('/\n\n([а-я])\)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        // First part is introduction (before first буква)
        $introduction = trim($parts[0]);

        // Update the parent node to only contain the introduction
        $parent->update(['text_markdown' => $introduction ?: null]);

        // Process букви
        for ($i = 1; $i < count($parts); $i += 2) {
            if (! isset($parts[$i]) || ! isset($parts[$i + 1])) {
                break;
            }

            $letter = $parts[$i];
            $letterText = trim($parts[$i + 1]);

            // Create буква node with uppercase letter in path
            $letterPath = $this->pathBuilder->buildLetterPath($parent->path, $letter);

            LawNode::create([
                'law_id' => $law->id,
                'path' => $letterPath,
                'p_id' => $parent->p_id,
                'caption' => null,
                'text_markdown' => $letterText,
                'node_type' => 'letter',
                'type' => $parent->type,
                'field_type' => $parent->field_type,
                'has_in_links' => $parent->has_in_links,
                'sort_order' => $this->sortOrder++,
                'level' => $parent->level + 1,
                'is_orphaned' => false,
            ]);
        }
    }
}
