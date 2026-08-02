<?php

namespace Database\Factories;

use App\Models\Law;
use App\Models\LawVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Law>
 */
class LawFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unique_id' => fake()->unique()->numberBetween(1000, 99999),
            'db_index' => 0,
            'caption' => 'ЗАКОН за '.fake()->words(3, true),
            'func' => 1,
            'type' => 4,
            'base' => 'NARH',
            'is_actual' => true,
            'publ_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'start_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'end_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'act_date' => fake()->dateTimeBetween('-10 years', '-1 year'),
            'publ_year' => fake()->year(),
            'is_connected' => fake()->boolean(),
            'has_content' => true,
            'code' => (string) fake()->numberBetween(1000, 99999),
            'dv' => fake()->numberBetween(1, 100),
            'original_id' => fake()->optional()->numberBetween(1000, 99999),
            'version' => fake()->optional()->word(),
            'celex' => fake()->optional()->word(),
            'doc_lead' => fake()->optional()->word(),
            'seria' => fake()->optional()->word(),
        ];
    }

    /**
     * A law has no content of its own — the payload belongs to a redaction of
     * it. Accepting content_structure/content_text here and turning them into
     * the law's first version keeps "a law with this text" sayable in one
     * expression, which is what every test actually means by it.
     *
     * @param  array<string, mixed>  $attributes
     * @return Law|Collection<int, Law>
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        $structure = $attributes['content_structure'] ?? null;
        $text = $attributes['content_text'] ?? null;
        $hasContent = array_key_exists('content_structure', $attributes)
            || array_key_exists('content_text', $attributes);

        unset($attributes['content_structure'], $attributes['content_text']);

        $created = parent::create($attributes, $parent);

        if (! $hasContent) {
            return $created;
        }

        $laws = $created instanceof Collection ? $created : collect([$created]);

        foreach ($laws as $law) {
            $version = LawVersion::create([
                'law_id' => $law->id,
                'changed_at' => $law->publ_date?->toDateString() ?? now()->toDateString(),
                'dv' => $law->dv,
                'publ_year' => $law->publ_year,
                'valid_from' => $law->start_date?->toDateString(),
                'valid_to' => $law->end_date?->toDateString(),
                'content_structure' => $structure,
                'content_text' => $text,
                'source_hash' => LawVersion::hashPayload($structure, $text),
                'fetched_at' => $law->content_fetched_at,
                'processed_at' => $law->processed_at,
            ]);

            $law->forceFill(['current_version_id' => $version->id])->save();
            $law->setRelation('currentVersion', $version);
        }

        return $created;
    }
}
