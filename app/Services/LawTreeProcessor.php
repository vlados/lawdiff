<?php

namespace App\Services;

use App\Models\Law;
use App\Models\LawNode;
use Illuminate\Support\Facades\DB;
use League\HTMLToMarkdown\HtmlConverter;

class LawTreeProcessor
{
    /**
     * Точка markers: "1.", the letter-suffixed "18а." used when a point is
     * inserted between two existing ones, and the "18\." form html-to-markdown
     * emits when it escapes a period that could otherwise start an ordered
     * list. Bounded to three digits so a stray four-digit number cannot open a
     * точка. Detection and splitting share the pattern so they cannot drift.
     */
    private const POINT_MARKER = '\n\n\s*(\d{1,3}[а-я]?)(\\\.|\.)';

    /**
     * Буква markers: "а)" plus the suffixed "а1)" form.
     */
    private const LETTER_MARKER = '\n\n([а-я]\d?)\)';

    protected HtmlConverter $converter;

    protected int $sortOrder = 0;

    /**
     * The redaction the nodes being built describe. Stamped on every node so a
     * provision can say which version of the law it came from rather than
     * merely which law.
     */
    protected ?int $versionId = null;

    /**
     * Paths already assigned within the current process() run, used to keep
     * every node path unique per law. Duplicate paths silently overwrite each
     * other in the export tree builder, which keys nodes by path.
     *
     * @var array<string, true>
     */
    protected array $usedPaths = [];

    public function __construct(
        protected LawPathBuilder $pathBuilder,
        protected LegalReferenceExtractor $referenceExtractor
    ) {
        $this->converter = new HtmlConverter([
            'strip_tags' => true,
            'remove_nodes' => 'script style',
        ]);
    }

    /**
     * Runs in a transaction: the rebuild is delete-then-insert, and a mid-run
     * failure must roll back to the previous complete node set rather than
     * leave a partially rebuilt law that the export would happily ship.
     */
    public function process(Law $law): void
    {
        DB::transaction(function () use ($law): void {
            $this->sortOrder = 0;
            $this->usedPaths = [];
            $this->versionId = $law->current_version_id;

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
                '',
                null,
                0,
                $usedPIds
            );

            // Find and save orphaned paragraphs
            $this->saveOrphanedParagraphs($law, $textMap, $usedPIds);

            // Parse and split article text into алинеи, точки, букви
            $this->parseAndSplitArticles($law);

            // Inside the transaction and after the split, so references can
            // only ever describe the node set they were extracted from.
            $this->referenceExtractor->extract($law);
        });
    }

    /**
     * Reserve a unique path, suffixing repeats with -2, -3, … Repeated captions
     * are real in the corpus (budget-law appendices restate "Чл. 1" per annex;
     * orphan paths like ЗАГЛАВИЕ are constants), and each occurrence must keep
     * its own node.
     */
    protected function uniquePath(string $path): string
    {
        if (! isset($this->usedPaths[$path])) {
            $this->usedPaths[$path] = true;

            return $path;
        }

        $suffix = 2;
        while (isset($this->usedPaths["{$path}-{$suffix}"])) {
            $suffix++;
        }

        $this->usedPaths["{$path}-{$suffix}"] = true;

        return "{$path}-{$suffix}";
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

    /**
     * Two path chains are threaded down the tree rather than one.
     *
     * $citationPath is what descendants cite against and skips structural
     * containers, so an article keeps ЧЛ80А whether or not a глава wraps it.
     * $containerPath nests the containers among themselves, so the "Раздел I"
     * of every chapter still gets a distinct path.
     */
    protected function buildAndSaveNodes(
        Law $law,
        array $nodes,
        array $textMap,
        string $citationPath,
        string $containerPath,
        ?int $parentId,
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
            $isContainer = $this->pathBuilder->isCitationTransparent($nodeType);

            $pathSegment = $isContainer
                ? $this->pathBuilder->buildStructuralSegment($caption, $node['pId'])
                : $this->pathBuilder->buildSegment($caption, $node['pId']);

            $base = $isContainer ? $containerPath : $citationPath;
            $currentPath = $this->uniquePath($base ? $base.'/'.$pathSegment : $pathSegment);

            // Get text data if available
            $textData = $textMap[$node['pId']] ?? null;

            // Create and save the node
            $saved = LawNode::create([
                'law_id' => $law->id,
                'law_version_id' => $this->versionId,
                'parent_id' => $parentId,
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
                    $isContainer ? $citationPath : $currentPath,
                    $currentPath,
                    $saved->id,
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
                $pathSegment = $this->uniquePath($this->pathBuilder->buildOrphanedPath($data));

                LawNode::create([
                    'law_id' => $law->id,
                    'law_version_id' => $this->versionId,
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
        // Bounded to 1-2 digits: real алинеи never reach 100, but amendment history
        // references like "(2011)" do — an unbounded \d+ turned years into алинеи.
        if (preg_match('/(?:\s\((\d{1,2})\)|\n\n\((\d{1,2})\))/u', $text)) {
            $this->parseAlinees($law, $article, $text);

            return;
        }

        // Check if this node has точки (points) marked with 1., 2., 18а., etc.
        if (preg_match('/'.self::POINT_MARKER.'/u', $text)) {
            $this->parsePoints($law, $article, $text);

            return;
        }

        // Check if this node has букви (letters) marked with а), б), etc.
        if (preg_match('/'.self::LETTER_MARKER.'/u', $text)) {
            $this->parseLetters($law, $article, $text);
        }
    }

    protected function parseAlinees(Law $law, LawNode $article, string $text): void
    {
        // Normalize: Convert inline " (N)" or " (Nа)" to "\n\n(N)" for consistent parsing
        // Pattern matches (1), (2), (5а), (5б), etc. — bounded to 1-2 digits so
        // year references like "(2011)" in amendment history are left alone.
        $text = preg_replace('/\s+\((\d{1,2}[а-я]?)\)/u', "\n\n($1)", $text);

        // Split text by алинея pattern: \n\n(1), \n\n(2), \n\n(5а), etc.
        $parts = preg_split('/\n\n\((\d{1,2}[а-я]?)\)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

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
            $alineaPath = $this->uniquePath($this->pathBuilder->buildAlineaPath($article->path, $alineaNumber));

            $alineaNode = LawNode::create([
                'law_id' => $law->id,
                'law_version_id' => $this->versionId,
                'parent_id' => $article->id,
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
            if (preg_match('/'.self::POINT_MARKER.'/u', $alineaText)) {
                $this->parsePoints($law, $alineaNode, $alineaText);
            } elseif (preg_match('/'.self::LETTER_MARKER.'/u', $alineaText)) {
                $this->parseLetters($law, $alineaNode, $alineaText);
            }
        }
    }

    protected function parsePoints(Law $law, LawNode $parent, string $text): void
    {
        $parts = preg_split('/'.self::POINT_MARKER.'/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

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

            $pointNumber = $parts[$i]; // Keep as string to preserve letter suffixes like "18а"
            $pointText = trim($parts[$i + 2]);

            // Create точка node
            $pointPath = $this->uniquePath($this->pathBuilder->buildPointPath($parent->path, $pointNumber));

            $pointNode = LawNode::create([
                'law_id' => $law->id,
                'law_version_id' => $this->versionId,
                'parent_id' => $parent->id,
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
            if (preg_match('/'.self::LETTER_MARKER.'/u', $pointText)) {
                $this->parseLetters($law, $pointNode, $pointText);
            }
        }
    }

    protected function parseLetters(Law $law, LawNode $parent, string $text): void
    {
        // Split text by буква pattern: \n\nа), \n\nб), \n\nа1), etc.
        $parts = preg_split('/'.self::LETTER_MARKER.'/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

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
            $letterPath = $this->uniquePath($this->pathBuilder->buildLetterPath($parent->path, $letter));

            LawNode::create([
                'law_id' => $law->id,
                'law_version_id' => $this->versionId,
                'parent_id' => $parent->id,
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
