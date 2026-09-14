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
            'due_date' => now()->addDays(30)->toDateString(),
            'items'    => [
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
            return $job->invoice->organization_id === $org->id;
        });
    }

    /**
     * Test 2 : Le Job exécute l'envoi de l'email avec le bon destinataire et la bonne facture.
     */
    public function test_notification_job_sends_email_to_organization(): void
    {
        Mail::fake();

        $org = Organization::factory()->create([
            'email' => 'direction@client-dealtoo.com',
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'invoice_number'  => 'INV-2026-TEST01',
        ]);

        // Exécution directe de la méthode handle() du Job
        (new SendInvoiceNotificationJob($invoice))->handle();

        // Vérification que le mail a été envoyé à la bonne adresse avec le bon Mailable
        Mail::assertSent(InvoiceGeneratedMail::class, function ($mail) use ($invoice) {
            return $mail->hasTo('direction@client-dealtoo.com')
                && $mail->invoice->id === $invoice->id;
        });
    }
}
