<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-3 months', 'now');
        $dueDate = (clone $issueDate)->modify('+30 days');

        return [
            'organization_id'   => Organization::factory(),
            'invoice_number'    => 'INV-' . fake()->unique()->numerify('2026-#####'),
            'client_name'       => fake()->company(),
            'client_email'      => fake()->companyEmail(),
            'client_address'    => fake()->address(),
            'client_tax_number' => 'CI-ABJ-' . fake()->numerify('####-B-#####'),
            'client_phone'      => fake()->phoneNumber(),
            'status'            => fake()->randomElement([
                InvoiceStatus::DRAFT,
                InvoiceStatus::SENT,
                InvoiceStatus::PAID,
                InvoiceStatus::OVERDUE,
            ]),
            'issue_date'      => $issueDate->format('Y-m-d'),
            'due_date'        => $dueDate->format('Y-m-d'),
            'subtotal'        => 0.00,
            'tax_amount'      => 0.00,
            'total'           => 0.00,
            'currency'        => 'XOF',
            'notes'           => fake()->optional()->sentence(),
        ];
    }
}
