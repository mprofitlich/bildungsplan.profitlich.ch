<?php

namespace Database\Factories;

use App\Models\Kurs;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kurs>
 */
class KursFactory extends Factory
{
    protected $model = Kurs::class;

    public function definition(): array
    {
        return [
            'besitzer_id' => User::factory(),
            'titel' => fake()->words(3, true),
            'beschrieb' => '',
            'ziele' => ['a1.1-schule'],
        ];
    }
}
