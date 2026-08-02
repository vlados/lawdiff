<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Turns Bulgarian citation text into the canonical paths used by law_nodes.
 *
 * Citations are the only stable way one provision names another, but they are
 * written for humans ("чл. 80а, ал. 1, т. 6") while nodes are addressed by path
 * (ЧЛ80А/АЛ1/Т6). Without the translation nothing can be joined: the amendment
 * parser produced target strings no query could match, and a reference graph
 * would have nowhere to point.
 */
class LegalCitationParser
{
    /**
     * Ranked so a citation can only ever descend. "чл. 80а, ал. 1, т. 6" is one
     * citation; "т. 6 ... чл. 80а" is two, because a точка cannot contain an
     * article.
     */
    private const DEPTH = [
        'article' => 0,
        'section' => 0,
        'paragraph' => 1,
        'point' => 2,
        'letter' => 3,
    ];

    /**
     * Components in citation order, each capturing its number.
     *
     * Numbers carry optional suffixes throughout (чл. 164в, ал. 5а, т. 18б,
     * буква „а1") because the corpus uses them wherever a provision was
     * inserted between two existing ones.
     */
    private const COMPONENTS = [
        'article' => '(?:чл\.|член)\s*(\d{1,4}[а-я]?)',
        'section' => '§\s*(\d{1,4}[а-я]?)',
        'paragraph' => 'ал\.\s*(\d{1,2}[а-я]?)',
        'point' => 'т\.\s*(\d{1,3}[а-я]?)',
        'letter' => 'бук(?:ва|ви)\s*[„“"\']?([а-я]\d?)[”“"\']?',
    ];

    /**
     * What may stand between two components of one citation: separators, and
     * the conjunctions that enumerate siblings ("ал. 1 и ал. 3").
     */
    private const SEPARATOR = '/^[\s,]*(?:и|или)?[\s,]*$/u';

    /**
     * A trailing "от <акт>" turns an otherwise local citation into an external
     * one. Both spellings occur: the full name and the abbreviation.
     */
    private const EXTERNAL_ACT = '/^\s*от\s+(?:(Закона|Закон|Кодекса|Кодекс|Наредбата|Наредба|Правилника|Правилник|Указа|Указ)\s+([^,;.()\n]+)|([А-Я]{2,10})(?![а-я]))/u';

    /**
     * Where an act title ends and prose resumes. Deliberately narrow: "и" and
     * "при" are excluded because real titles contain them ("Закон за
     * здравословни и безопасни условия на труд", "Закон за защита при
     * бедствия"), and swallowing a title is far less harmful than truncating
     * one — the name is a lead for later matching, never the authority.
     */
    private const TITLE_BOUNDARY = ['се', 'си', 'е', 'са', 'бе', 'беше', 'ще', 'да', 'не', 'като', 'както', 'ако', 'когато', 'докато', 'защото', 'освен', 'този', 'тази', 'това', 'тези'];

    /**
     * Titles run long but not unboundedly; past this the match is prose.
     */
    private const TITLE_MAX_WORDS = 12;

    /**
     * Path segment prefixes, ranked on the same scale as DEPTH. Structural
     * containers (ПЗР, ГЛАВА_*) rank below everything and are never stripped.
     */
    private const SEGMENT_DEPTH = [
        'ЧЛ' => 0,
        '§' => 0,
        'АЛ' => 1,
        'Т' => 2,
        'БУКВА_' => 3,
    ];

    /**
     * Build a canonical path from citation components.
     *
     * @param  array{article?: string, section?: string, paragraph?: string, point?: string, letter?: string}  $components
     */
    public function toCanonicalPath(array $components): ?string
    {
        $segments = [];

        if (isset($components['article'])) {
            $segments[] = 'ЧЛ'.mb_strtoupper($components['article']);
        } elseif (isset($components['section'])) {
            $segments[] = '§'.mb_strtoupper($components['section']);
        }

        foreach (['paragraph' => 'АЛ', 'point' => 'Т', 'letter' => 'БУКВА_'] as $key => $prefix) {
            if (isset($components[$key])) {
                $segments[] = $prefix.mb_strtoupper($components[$key]);
            }
        }

        return $segments === [] ? null : implode('/', $segments);
    }

    /**
     * Find every citation in a block of text.
     *
     * @return list<array{
     *     text: string,
     *     offset: int,
     *     components: array<string, string>,
     *     canonical_path: string,
     *     is_relative: bool,
     *     external_act: string|null
     * }>
     */
    public function scan(string $text): array
    {
        $citations = [];

        foreach ($this->group($this->tokenize($text), $text) as $group) {
            $components = [];
            foreach ($group as $token) {
                $components[$token['kind']] = $token['number'];
            }

            $path = $this->toCanonicalPath($components);

            if ($path === null) {
                continue;
            }

            $first = $group[0];
            $last = $group[count($group) - 1];
            $end = $last['offset'] + strlen($last['text']);

            $citations[] = [
                'text' => substr($text, $first['offset'], $end - $first['offset']),
                'offset' => $first['offset'],
                'components' => $components,
                'canonical_path' => $path,
                // No чл./§ anchor: the target sits inside whatever provision the
                // citing text itself belongs to, which only the caller knows.
                'is_relative' => ! isset($components['article']) && ! isset($components['section']),
                'external_act' => $this->externalActAt($text, $end),
            ];
        }

        return $citations;
    }

    /**
     * Anchor a relative citation ("по ал. 5") to the node that made it.
     *
     * The base is the citing node's own path with everything at or below the
     * citation's top level stripped: ЧЛ80А/АЛ1/Т6 citing "ал. 5" resolves
     * against ЧЛ80А, while the same node citing "буква „б"" keeps the whole
     * path. Container segments are never stripped, so a § keeps its ПЗР block.
     *
     * @param  array<string, string>  $components
     */
    public function resolveRelative(string $sourcePath, array $components): ?string
    {
        $relative = $this->toCanonicalPath($components);

        if ($relative === null) {
            return null;
        }

        $topDepth = min(array_map(
            fn (string $kind): int => self::DEPTH[$kind],
            array_keys($components)
        ));

        $segments = explode('/', $sourcePath);

        while ($segments !== []) {
            $depth = $this->segmentDepth(end($segments));

            if ($depth === null || $depth < $topDepth) {
                break;
            }

            array_pop($segments);
        }

        return $segments === [] ? $relative : implode('/', $segments).'/'.$relative;
    }

    /**
     * The structural container a node sits in, i.e. everything before its first
     * чл./§ segment. ДОП/§6/Т18 yields ДОП; ЧЛ80А/АЛ1 yields null.
     *
     * A citation of "§ 6" carries no block, but § numbering restarts in every
     * ПЗР, so the § meant is almost always the one in the citing text's own
     * block.
     */
    public function containerPrefix(string $path): ?string
    {
        $segments = explode('/', $path);

        foreach ($segments as $index => $segment) {
            if ($this->segmentDepth($segment) !== null) {
                return $index === 0 ? null : implode('/', array_slice($segments, 0, $index));
            }
        }

        return null;
    }

    private function segmentDepth(string $segment): ?int
    {
        foreach (self::SEGMENT_DEPTH as $prefix => $depth) {
            if (str_starts_with($segment, $prefix)) {
                return $depth;
            }
        }

        return null;
    }

    /**
     * @return list<array{kind: string, number: string, offset: int, text: string}>
     */
    private function tokenize(string $text): array
    {
        $tokens = [];

        foreach (self::COMPONENTS as $kind => $pattern) {
            if (! preg_match_all('/'.$pattern.'/u', $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $match) {
                $tokens[] = [
                    'kind' => $kind,
                    'number' => $match[1][0],
                    'offset' => $match[0][1],
                    'text' => $match[0][0],
                ];
            }
        }

        usort($tokens, fn (array $a, array $b): int => $a['offset'] <=> $b['offset']);

        return $tokens;
    }

    /**
     * Chain tokens into citations.
     *
     * A token extends the current citation when it descends a level with only
     * separators in between, so "ал. 1, т. 6" is one citation while
     * "ал. 1 ... предвидените в т. 6" is two. A token at the same level after a
     * conjunction opens a sibling that inherits the levels above it, which is
     * how "чл. 80а, ал. 1 и ал. 3" yields two complete paths rather than one
     * path and one fragment.
     *
     * @param  list<array{kind: string, number: string, offset: int, text: string}>  $tokens
     * @return list<list<array{kind: string, number: string, offset: int, text: string}>>
     */
    private function group(array $tokens, string $text): array
    {
        $groups = [];
        $current = [];

        foreach ($tokens as $token) {
            if ($current === []) {
                $current = [$token];

                continue;
            }

            $previous = $current[count($current) - 1];
            $gap = substr(
                $text,
                $previous['offset'] + strlen($previous['text']),
                $token['offset'] - $previous['offset'] - strlen($previous['text'])
            );

            if (preg_match(self::SEPARATOR, $gap) === 1) {
                $previousDepth = self::DEPTH[$previous['kind']];
                $depth = self::DEPTH[$token['kind']];

                if ($depth > $previousDepth) {
                    $current[] = $token;

                    continue;
                }

                if ($depth === $previousDepth && $depth > 0) {
                    $groups[] = $current;
                    $ancestors = array_filter(
                        $current,
                        fn (array $ancestor): bool => self::DEPTH[$ancestor['kind']] < $depth
                    );
                    $current = [...array_values($ancestors), $token];

                    continue;
                }
            }

            $groups[] = $current;
            $current = [$token];
        }

        if ($current !== []) {
            $groups[] = $current;
        }

        return $groups;
    }

    private function externalActAt(string $text, int $offset): ?string
    {
        if (! preg_match(self::EXTERNAL_ACT, substr($text, $offset), $matches)) {
            return null;
        }

        if (($matches[3] ?? '') !== '') {
            return $matches[3];
        }

        return trim($matches[1].' '.$this->trimToTitle($matches[2]));
    }

    private function trimToTitle(string $tail): string
    {
        $words = preg_split('/\s+/u', trim($tail), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $title = [];

        foreach ($words as $word) {
            if (in_array(mb_strtolower($word), self::TITLE_BOUNDARY, true)) {
                break;
            }

            $title[] = $word;

            if (count($title) === self::TITLE_MAX_WORDS) {
                break;
            }
        }

        return implode(' ', $title);
    }
}
