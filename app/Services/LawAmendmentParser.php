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

        // Split into lines
        $lines = preg_split('/\r\n|\r|\n/', $content);

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
        // Skip lines that are modifications text (contain quotes or specific change keywords)
        if ($this->isModificationText($line)) {
            return;
        }

        // Check for main article reference: "В чл. X"
        if (preg_match('/^В\s+чл\.\s*(\d+[а-я]?)/iu', $line, $matches)) {
            $this->context['article'] = $matches[1];
            unset($this->context['paragraph'], $this->context['point'], $this->context['letter']);

            $this->addTarget($targets);
        }

        // Check for paragraph: "В ал. X" or "1. В ал. X:"
        if (preg_match('/(?:^\d+\.)?\s*В\s+ал\.\s*(\d+)/iu', $line, $matches)) {
            if (isset($this->context['article'])) {
                $this->context['paragraph'] = $matches[1];
                unset($this->context['point'], $this->context['letter']);
                $this->addTarget($targets);
            }
        }

        // Check for point: "в т. X" or "а) в т. X"
        if (preg_match('/в\s+т\.\s*(\d+)/iu', $line, $matches)) {
            if (isset($this->context['article'])) {
                $this->context['point'] = $matches[1];
                unset($this->context['letter']);
                $this->addTarget($targets);
            }
        }

        // Check for letter: 'буква "X"'
        if (preg_match('/буква\s*["\']([а-я])["\']/', $line, $matches)) {
            if (isset($this->context['article'])) {
                $this->context['letter'] = $matches[1];
                $this->addTarget($targets);
            }
        }
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

        if (isset($this->context['article'])) {
            $article = 'чл. '.$this->context['article'];
            $path[] = $article;
            $target['article'] = $article;
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
