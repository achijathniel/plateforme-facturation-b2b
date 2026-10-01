<?php

namespace Tests\Feature\Invoices;

use App\Enums\UserRole;
use App\Jobs\SendInvoiceNotificationJob;
use App\Mail\InvoiceGeneratedMail;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InvoiceNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1 : La création d'une facture pousse le Job dans la file d'attente (Queue).
     */
    public function test_invoice_creation_dispatches_notification_job(): void
    {
        Queue::fake();

        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);
        $token = $accountant->createToken('acc-token')->plainTextToken;

        $payload = [
            'client_name'  => 'Entreprise Client B2B',
            'client_email' => 'comptabilite@client-b2b.com',
            'due_date'     => now()->addDays(30)->toDateString(),
            'items'        => [
                [
                    'description' => 'Abonnement Cloud Annuel',
                    'quantity'    => 1,
                    'unit_price'  => 600000,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/invoices', $payload);

        $response->assertStatus(201);

        // Vérification que le Job a bien été poussé dans la Queue
        Queue::assertPushed(SendInvoiceNotificationJob::class, function ($job) use ($org) {
            return $job->invoice->organization_id === $org->id
                && $job->invoice->client_email === 'comptabilite@client-b2b.com';
        });
    }

    /**
     * Test 2 : Le Job exécute l'envoi de l'email directement à l'adresse de facturation du client B2B.
     */
    public function test_notification_job_sends_email_to_client(): void
    {
        Mail::fake();

        $org = Organization::factory()->create([
            'email' => 'emetteur@societe-fournisseur.com',
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'invoice_number'  => 'INV-2026-TEST01',
            'client_email'    => 'direction@client-dealtoo.com',
        ]);

        // Exécution directe de la méthode handle() du Job
        (new SendInvoiceNotificationJob($invoice))->handle();

        // Vérification que le mail a été envoyé à l'adresse email du client destinataire
        Mail::assertSent(InvoiceGeneratedMail::class, function ($mail) use ($invoice) {
            return $mail->hasTo('direction@client-dealtoo.com')
                && $mail->invoice->id === $invoice->id;
        });
    }

    /**
     * Test 3 : En cas d'absence d'email client, le Job effectue un fallback vers l'email de l'organisation.
     */
    public function test_notification_job_fallbacks_to_organization_email_if_client_email_empty(): void
    {
        Mail::fake();

        $org = Organization::factory()->create([
            'email' => 'fallback@societe-emetteur.com',
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'invoice_number'  => 'INV-2026-TEST02',
            'client_email'    => null,
        ]);

        (new SendInvoiceNotificationJob($invoice))->handle();

        Mail::assertSent(InvoiceGeneratedMail::class, function ($mail) use ($invoice) {
            return $mail->hasTo('fallback@societe-emetteur.com')
                && $mail->invoice->id === $invoice->id;
        });
    }
}
