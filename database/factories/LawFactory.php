<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Law>
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
}
