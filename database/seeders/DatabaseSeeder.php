<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Administrateur principal pour les tests d'authentification
        $admin = User::firstOrCreate(
            ['email' => 'admin@dughu-dealtoo.com'],
            [
                'name'     => 'Directeur Technique',
                'password' => Hash::make('password'),
                'role'     => UserRole::ADMIN,
            ]
        );

        // 2. Récupération ou création de 5 entreprises clientes
        $organizations = Organization::all();
        if ($organizations->isEmpty()) {
            $organizations = Organization::factory()->count(5)->create();
        }

        foreach ($organizations as $index => $org) {
            // 1. Création ou mise à jour du Directeur Général
            User::updateOrCreate(
                ['email' => "directeur{$index}@dealtoo.com"],
                [
                    'organization_id' => $org->id,
                    'name'            => "Directeur " . $org->name,
                    'password'        => Hash::make('password'),
                    'role'            => UserRole::DIRECTOR,
                ]
            );

            // 2. Création ou mise à jour du Comptable
            User::updateOrCreate(
                ['email' => "comptable{$index}@dealtoo.com"],
                [
                    'organization_id' => $org->id,
                    'name'            => "Comptable " . $org->name,
                    'password'        => Hash::make('password'),
                    'role'            => UserRole::ACCOUNTANT,
                ]
            );

            // 3. Création ou mise à jour du Collaborateur interne
            User::updateOrCreate(
                ['email' => "collaborateur{$index}@dealtoo.com"],
                [
                    'organization_id' => $org->id,
                    'name'            => "Collaborateur " . $org->name,
                    'password'        => Hash::make('password'),
                    'role'            => UserRole::COLLABORATOR,
                ]
            );

            // Création de 4 factures par entreprise avec différents statuts
            $statuses = [InvoiceStatus::PAID, InvoiceStatus::SENT, InvoiceStatus::OVERDUE, InvoiceStatus::DRAFT];

            foreach ($statuses as $statusIndex => $status) {
                $invoiceNumber = sprintf("INV-2026-%02d%02d", $org->id, $statusIndex + 1);

                if (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                    continue;
                }

                $invoice = Invoice::create([
                    'organization_id' => $org->id,
                    'invoice_number'  => $invoiceNumber,
                    'status'          => $status,
                    'issue_date'      => now()->subDays(rand(10, 60))->toDateString(),
                    'due_date'        => now()->addDays(rand(5, 30))->toDateString(),
                    'subtotal'        => 0.00,
                    'tax_amount'      => 0.00,
                    'total'           => 0.00,
                    'notes'           => "Facture B2B émise pour {$org->name}.",
                ]);

                // Création de 2 à 3 lignes pour cette facture
                $itemsCount = rand(2, 3);
                $subtotal = '0.00';
                $taxAmount = '0.00';

                for ($i = 0; $i < $itemsCount; $i++) {
                    $item = InvoiceItem::factory()->create([
                        'invoice_id' => $invoice->id,
                    ]);

                    $subtotal = bcadd($subtotal, (string) $item->total, 2);
                    $lineTax = bcmul((string) $item->total, '0.18', 2); // 18% TVA UEMOA
                    $taxAmount = bcadd($taxAmount, $lineTax, 2);
                }

                $total = bcadd($subtotal, $taxAmount, 2);

                // Mise à jour des totaux exacts calculés
                $invoice->update([
                    'subtotal'   => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total'      => $total,
                    'currency'   => 'XOF',
                ]);

                // Si la facture est marquée PAYÉE, créer le paiement correspondant
                if ($status === InvoiceStatus::PAID) {
                    Payment::create([
                        'invoice_id'     => $invoice->id,
                        'payment_method' => PaymentMethod::BANK_TRANSFER,
                        'status'         => PaymentStatus::COMPLETED,
                        'transaction_id' => 'tx_' . fake()->unique()->uuid(),
                        'amount'         => $total,
                        'currency'       => 'XOF',
                        'paid_at'        => now()->subDays(rand(1, 10)),
                        'notes'          => 'Virement bancaire validé.',
                    ]);
                }
            }
        }
    }
}
