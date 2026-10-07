<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Définition de l'état par défaut du modèle Client.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name'            => fake()->company(),
            'email'           => fake()->unique()->companyEmail(),
            'phone'           => fake()->phoneNumber(),
            'address'         => fake()->address(),
            'tax_number'      => 'CI-ABJ-' . fake()->numerify('####-B-#####'),
            'notes'           => fake()->sentence(),
        ];
    }
}
