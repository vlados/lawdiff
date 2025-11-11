<?php

namespace App\Services;

class LawAmendmentParser
{
    protected array $context = [];

    public function parse(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);

        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Extract law name
        $lawName = $this->extractLawName($content);
        $targetLawName = $this->extractTargetLawName($content);

        // Extract all amendments (paragraphs)
        $amendments = $this->extractAmendments($content);

        return [
            'law_name' => $lawName,
            'target_law_name' => $targetLawName,
            'amendments' => $amendments,
        ];
    }

    protected function extractLawName(string $content): ?string
    {
        // Match "ЗАКОН за изменение и допълнение на [LAW NAME]"
        if (preg_match('/ЗАКОН\s+за\s+изменение\s+и\s+допълнение\s+на\s+(.+?)(?:\n|\(обн\.|$)/isu', $content, $matches)) {
            return 'ЗАКОН за изменение и допълнение на '.trim($matches[1]);
        }

        return null;
    }

    protected function extractTargetLawName(string $content): ?string
    {
        // Extract the name of the law being amended
        if (preg_match('/ЗАКОН\s+за\s+изменение\s+и\s+допълнение\s+на\s+(.+?)(?:\n|\(обн\.|$)/isu', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function extractAmendments(string $content): array
    {
        $amendments = [];

        // Split content by paragraphs (§)
        preg_match_all('/§\s*(\d+)\.\s*(.+?)(?=§\s*\d+\.|Преходни\s+и\s+Заключителни|$)/isu', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $paragraphNumber = (int) $match[1];
            $paragraphContent = trim($match[2]);

            // Extract motives if present
            $motives = null;
            if (preg_match('/Мотиви:\s*(.+?)(?=\n\s*$)/isu', $paragraphContent, $motivesMatch)) {
                $motives = trim($motivesMatch[1]);
                // Remove motives from content
                $paragraphContent = preg_replace('/\s*Мотиви:\s*.+$/isu', '', $paragraphContent);
                $paragraphContent = trim($paragraphContent);
            }

            // Extract target paths
            $targets = $this->extractTargetPaths($paragraphContent);

            $amendments[] = [
                'paragraph_number' => $paragraphNumber,
                'content' => $paragraphContent,
                'motives' => $motives,
                'targets' => $targets,
            ];
        }

        return $amendments;
    }

    protected function extractTargetPaths(string $content): array
    {
        $targets = [];
        $this->context = [];

        // Remove motives section to avoid extracting references from there
        $contentWithoutMotives = preg_replace('/Мотиви:\s*.+$/isu', '', $content);

        // Split into lines
        $lines = preg_split('/\r\n|\r|\n/', $contentWithoutMotives);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Parse the line and update context
            $this->processLine($line, $targets);
        }

        // Remove duplicates
        $uniqueTargets = [];
        foreach ($targets as $target) {
            $key = $target['path'];
            if (! isset($uniqueTargets[$key])) {
                $uniqueTargets[$key] = $target;
            }
        }

        return array_values($uniqueTargets);
    }

    protected function processLine(string $line, array &$targets): void
    {
        // Try to parse a complete reference in one line first
        // Pattern: "В чл. X, ал. Y, т. Z, буква "а""
        // This handles lines like: "В чл. 151, ал. 1, т. 12 думите..."
        if ($this->parseCompleteReference($line, $targets)) {
            return;
        }

        // Skip lines that are ONLY modifications text (no structural references)
        if ($this->isModificationText($line)) {
            return;
        }

        // Check for main article reference: "В чл. X" or "чл. X"
        // Handles: "В чл. 21", "чл. 151", "чл. 164в" (with letter suffixes)
        if (preg_match('/В\s+чл\.\s*(\d+[а-я]?)/iu', $line, $matches)) {
            $this->context['article'] = $matches[1];
            unset($this->context['paragraph'], $this->context['point'], $this->context['letter'], $this->context['section']);

            $this->addTarget($targets);
        }

        // Check for section reference: "В § X" or "§ X"
        // Handles: "§ 6", "§ 104", "§ 18б" (with letter suffixes)
        if (preg_match('/(?:В\s+)?§\s*(\d+[а-я]?)/iu', $line, $matches)) {
            $this->context['section'] = $matches[1];
            unset($this->context['article'], $this->context['paragraph'], $this->context['point'], $this->context['letter']);

            $this->addTarget($targets);
        }

        // Check for paragraph: "В ал. X" or "ал. X"
        if (preg_match('/(?:В\s+)?ал\.\s*(\d+[а-я]?)/iu', $line, $matches)) {
            if (isset($this->context['article']) || isset($this->context['section'])) {
                $this->context['paragraph'] = $matches[1];
                unset($this->context['point'], $this->context['letter']);
                $this->addTarget($targets);
            }
        }

        // Check for point: "т. X"
        if (preg_match('/т\.\s*(\d+[а-я]?)/iu', $line, $matches)) {
            if (isset($this->context['article']) || isset($this->context['section'])) {
                $this->context['point'] = $matches[1];
                unset($this->context['letter']);
                $this->addTarget($targets);
            }
        }

        // Check for letter: 'буква "X"'
        if (preg_match('/буква\s*["\']([а-я])["\']/', $line, $matches)) {
            if (isset($this->context['article']) || isset($this->context['section'])) {
                $this->context['letter'] = $matches[1];
                $this->addTarget($targets);
            }
        }
    }

    protected function parseCompleteReference(string $line, array &$targets): bool
    {
        // Try to match complete reference patterns like:
        // "В чл. 151, ал. 1, т. 12"
        // "В чл. 21, в таблицата към ал. 1"
        // "В чл. 164в, ал. 2, т. 5, буква "б""
        // "В § 6, т. 18б"

        $foundComplete = false;

        // Pattern for article-based complete reference (allowing extra text like "в таблицата към")
        // Matches: "В чл. X" optionally followed by "ал. Y", "т. Z", "буква "а""
        // Commas are optional between components
        if (preg_match('/В\s+чл\.\s*(\d+[а-я]?)(?:[^,]*?(?:,\s*)?(?:в\s+таблицата\s+към\s+)?ал\.\s*(\d+[а-я]?))?(?:[^,]*?(?:,\s*)?т\.\s*(\d+[а-я]?))?(?:[^,]*?(?:,\s*)?буква\s*["\']([а-я])["\'])?/iu', $line, $matches)) {
            $this->context['article'] = $matches[1];
            unset($this->context['section']);

            if (! empty($matches[2])) {
                $this->context['paragraph'] = $matches[2];
            } else {
                unset($this->context['paragraph']);
            }

            if (! empty($matches[3])) {
                $this->context['point'] = $matches[3];
            } else {
                unset($this->context['point']);
            }

            if (! empty($matches[4])) {
                $this->context['letter'] = $matches[4];
            } else {
                unset($this->context['letter']);
            }

            $this->addTarget($targets);
            $foundComplete = true;
        }

        // Pattern for section-based complete reference (Supplementary provisions)
        if (preg_match('/В\s+§\s*(\d+[а-я]?)(?:,\s*т\.\s*(\d+[а-я]?))?/iu', $line, $matches)) {
            $this->context['section'] = $matches[1];
            unset($this->context['article'], $this->context['paragraph'], $this->context['letter']);

            if (! empty($matches[2])) {
                $this->context['point'] = $matches[2];
            } else {
                unset($this->context['point']);
            }

            $this->addTarget($targets);
            $foundComplete = true;
        }

        return $foundComplete;
    }

    protected function isModificationText(string $line): bool
    {
        // Lines that contain modification keywords and quotes are not structural references
        $modificationKeywords = [
            'думите.*се заменят',
            'се заличават',
            'се отменя',
            'се изменя',
            'се добавя',
            'се поставя',
            'създават се',
        ];

        foreach ($modificationKeywords as $keyword) {
            if (preg_match('/'.$keyword.'/iu', $line)) {
                return true;
            }
        }

        return false;
    }

    protected function addTarget(array &$targets): void
    {
        $path = [];
        $target = [];

        // Handle either article or section reference
        if (isset($this->context['article'])) {
            $article = 'чл. '.$this->context['article'];
            $path[] = $article;
            $target['article'] = $article;
        } elseif (isset($this->context['section'])) {
            $section = '§ '.$this->context['section'];
            $path[] = $section;
            $target['section'] = $section;
        }

        if (isset($this->context['paragraph'])) {
            $paragraph = 'ал. '.$this->context['paragraph'];
            $path[] = $paragraph;
            $target['paragraph'] = $paragraph;
        }

        if (isset($this->context['point'])) {
            $point = 'т. '.$this->context['point'];
            $path[] = $point;
            $target['point'] = $point;
        }

        if (isset($this->context['letter'])) {
            $letter = 'буква "'.$this->context['letter'].'"';
            $path[] = $letter;
            $target['letter'] = $letter;
        }

        if (! empty($path)) {
            $target['path'] = implode(' > ', $path);
            $targets[] = $target;
        }
    }
}
