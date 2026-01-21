<?php

namespace Core\Mod\Web\Database\Factories;

use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bio\Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'project_id' => null,
            'domain_id' => null,
            'type' => 'biolink',
            'url' => Str::slug($this->faker->words(2, true)),
            'location_url' => null,
            'settings' => [
                'seo' => [
                    'title' => $this->faker->sentence(4),
                    'description' => $this->faker->paragraph(),
                ],
                'background' => [
                    'type' => 'color',
                    'color' => '#ffffff',
                ],
            ],
            'clicks' => $this->faker->numberBetween(0, 1000),
            'unique_clicks' => $this->faker->numberBetween(0, 500),
            'is_enabled' => true,
            'is_verified' => true,
            'start_date' => null,
            'end_date' => null,
            'last_click_at' => null,
        ];
    }

    /**
     * Create a short link (redirect).
     */
    public function shortLink(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'link',
            'location_url' => $this->faker->url(),
        ]);
    }

    /**
     * Create a disabled biolink.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Create a biolink for a specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Create a biolink for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a scheduled biolink (has start/end dates).
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);
    }
}
