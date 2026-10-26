<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->userName().'@sluz.ch',
            'domain' => 'sluz.ch',
        ];
    }

    /** Eine Person einer anderen Domain. */
    public function domain(string $domain): static
    {
        return $this->state(fn () => [
            'email' => fake()->unique()->userName()."@{$domain}",
            'domain' => $domain,
        ]);
    }
}
