<?php

namespace Database\Factories;

use App\VerificationResults;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VerificationResult>
 */
class VerificationResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'result' => fake()->randomElement([VerificationResults::INVALID_ISSUER, VerificationResults::INVALID_RECIPIENT, VerificationResults::INVALID_SIGNATURE, VerificationResults::VERIFIED])
        ];
    }
}
