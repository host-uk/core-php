<?php

namespace Core\Mod\Web\Database\Factories;

use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bio\Block>
 */
class BlockFactory extends Factory
{
    protected $model = Block::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'biolink_id' => Page::factory(),
            'type' => 'link',
            'location_url' => $this->faker->url(),
            'settings' => [
                'name' => $this->faker->sentence(3),
                'text_color' => '#ffffff',
                'background_color' => '#3b82f6',
            ],
            'order' => $this->faker->numberBetween(0, 10),
            'clicks' => $this->faker->numberBetween(0, 100),
            'is_enabled' => true,
            'start_date' => null,
            'end_date' => null,
        ];
    }

    /**
     * Create a disabled block.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Create a block for a specific biolink.
     */
    public function forBioLink(Page $biolink): static
    {
        return $this->state(fn (array $attributes) => [
            'biolink_id' => $biolink->id,
            'workspace_id' => $biolink->workspace_id,
        ]);
    }

    /**
     * Create a heading block.
     */
    public function heading(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'heading',
            'location_url' => null,
            'settings' => [
                'text' => $this->faker->sentence(3),
                'size' => 'h2',
            ],
        ]);
    }

    /**
     * Create a text block.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'paragraph',
            'location_url' => null,
            'settings' => [
                'text' => $this->faker->paragraph(),
            ],
        ]);
    }

    /**
     * Create a social links block.
     */
    public function socials(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'socials',
            'location_url' => null,
            'settings' => [
                'twitter' => 'https://twitter.com/example',
                'instagram' => 'https://instagram.com/example',
            ],
        ]);
    }
}
