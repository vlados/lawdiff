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
     * Supports точки with letter suffixes like "18а", "18б", etc.
     */
    public function buildPointPath(string $parentPath, string $pointNumber): string
    {
        return $parentPath.'/Т'.mb_strtoupper($pointNumber);
    }

    /**
     * Build a path for a буква (letter) node.
     * Supports букви with numeric suffixes like "а1", "б1", etc.
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
     * Whether a node is a structural container that must not appear in its
     * descendants' paths.
     *
     * Article numbering runs law-wide and continues across chapters, so the
     * citation for чл. 80а is ЧЛ80А no matter which глава holds it — putting
     * the chapter in the path would make paths unciteable and break every
     * reference the moment a chapter is renamed or renumbered. Chapters and
     * sections are still saved as nodes; parent_id carries the hierarchy.
     *
     * Transitional sections are deliberately NOT transparent: § numbering
     * restarts in each ПЗР block, so the ПЗР/§1 prefix is what keeps the § of
     * one amending act apart from the § of the next.
     */
    public function isCitationTransparent(string $nodeType): bool
    {
        return in_array($nodeType, ['chapter', 'section'], true);
    }

    /**
     * Build a path segment for a structural container. Unlike citable segments
     * these keep word boundaries, since a chapter is identified by its caption
     * rather than by a number ("Глава първа" -> ГЛАВА_ПЪРВА).
     */
    public function buildStructuralSegment(?string $caption, int $pId): string
    {
        if (! $caption) {
            return 'NODE_'.$pId;
        }

        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', '_', mb_strtoupper($caption));
        $normalized = trim((string) $normalized, '_');

        return $normalized ?: 'NODE_'.$pId;
    }
}
