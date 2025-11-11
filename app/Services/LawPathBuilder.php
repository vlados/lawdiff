<?php

namespace App\Services;

/**
 * Builds paths for law nodes according to УКАЗ № 883 structure.
 *
 * Regular law structure: чл. → ал. → т. → буква
 * Transitional/Final provisions: § → ал. → т. → буква
 */
class LawPathBuilder
{
    /**
     * Build a path segment from a node caption.
     */
    public function buildSegment(?string $caption, int $pId): string
    {
        if (! $caption) {
            return 'NODE_'.$pId;
        }

        $upper = mb_strtoupper($caption);

        // Special handling for § paragraphs (transitional/final/additional provisions)
        // Captures number and optional Cyrillic letter suffix: § 6а, § 6б, etc.
        if (preg_match('/^§\s*(\d+)([а-я]?)/u', $caption, $matches)) {
            $number = $matches[1];
            $letter = ! empty($matches[2]) ? mb_strtoupper($matches[2]) : '';

            return '§'.$number.$letter;
        }

        // Abbreviated paths for provision sections
        if (str_contains($upper, 'ДОПЪЛНИТЕЛНИ')) {
            return 'ДОП';
        }

        if (str_contains($upper, 'ПРЕХОДНИ') && str_contains($upper, 'ЗАКЛЮЧИТЕЛНИ')) {
            return 'ПЗР';
        }

        if (str_contains($upper, 'ПРЕХОДНИ')) {
            return 'ПРЕХОДНИ';
        }

        if (str_contains($upper, 'ЗАКЛЮЧИТЕЛНИ')) {
            return 'ЗАКЛЮЧИТЕЛНИ';
        }

        // Normalize the caption to create a path segment
        $normalized = $upper;

        // Remove all non-alphanumeric characters
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', '', $normalized);

        return $normalized ?: 'NODE_'.$pId;
    }

    /**
     * Build a path for orphaned nodes based on their metadata.
     */
    public function buildOrphanedPath(array $data): string
    {
        $type = $data['type'] ?? 0;
        $fieldType = $data['fieldType'] ?? 0;
        $pId = $data['pId'] ?? uniqid();

        // Try to determine what kind of orphaned node this is
        if ($fieldType === 1) {
            return 'ЗАГЛАВИЕ';
        }

        if ($fieldType === 2) {
            return 'ПУБЛ_ИНФО';
        }

        if ($fieldType === 9) {
            return 'ЗАБЕЛЕЖКА_'.$pId;
        }

        return 'ORPHAN_'.$type.'_'.$pId;
    }

    /**
     * Build a path for an алинея (paragraph) node.
     * Supports алинеи with letter suffixes like "5а", "5б", etc.
     */
    public function buildAlineaPath(string $parentPath, string $alineaNumber): string
    {
        return $parentPath.'/АЛ'.mb_strtoupper($alineaNumber);
    }

    /**
     * Build a path for a точка (point) node.
     */
    public function buildPointPath(string $parentPath, int $pointNumber): string
    {
        return $parentPath.'/Т'.$pointNumber;
    }

    /**
     * Build a path for a буква (letter) node.
     */
    public function buildLetterPath(string $parentPath, string $letter): string
    {
        return $parentPath.'/БУКВА_'.mb_strtoupper($letter);
    }

    /**
     * Determine the node type from a caption.
     */
    public function determineNodeType(string $caption): string
    {
        $upper = mb_strtoupper($caption);

        if (str_contains($upper, 'ГЛАВА') || str_starts_with($upper, 'ГЛ.')) {
            return 'chapter';
        }

        if (str_contains($upper, 'РАЗДЕЛ') || str_starts_with($upper, 'РАЗД.')) {
            return 'section';
        }

        // Check for transitional/final/additional provisions sections
        if (str_contains($upper, 'ПРЕХОДНИ') || str_contains($upper, 'ЗАКЛЮЧИТЕЛНИ') || str_contains($upper, 'ДОПЪЛНИТЕЛНИ')) {
            return 'transitional_section';
        }

        // Check for § paragraph (used in transitional/final provisions)
        if (str_starts_with($upper, '§') || preg_match('/^§\s*\d+/', $caption)) {
            return 'transitional_paragraph';
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

    /**
     * Determine node type for orphaned nodes based on metadata.
     */
    public function determineOrphanedNodeType(array $data): string
    {
        $fieldType = $data['fieldType'] ?? 0;

        return match ($fieldType) {
            1 => 'title',
            2 => 'publication_info',
            9 => 'note',
            default => 'metadata',
        };
    }

    /**
     * Check if a node type should be skipped (not saved, but children processed).
     */
    public function shouldSkipNode(string $nodeType): bool
    {
        return in_array($nodeType, ['chapter', 'section']);
    }
}
