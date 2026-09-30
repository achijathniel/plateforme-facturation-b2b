<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Invoices\CreateInvoiceAction;
use App\Actions\Invoices\SendInvoiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class PortalInvoiceController extends Controller
{
    /**
     * Affiche la liste des factures appartenant à l'organisation du comptable.
     */
    public function index(
        Request $request,
        InvoiceRepositoryInterface $invoiceRepository
    ): Response {
        Gate::authorize('viewAny', Invoice::class);

        $invoices = $invoiceRepository->paginateForUser($request->user(), 15);

        return Inertia::render('Portal/Invoices/Index', [
            'invoices'     => $invoices,
            'organization' => [
                'id'   => $request->user()->organization?->id,
                'name' => $request->user()->organization?->name ?? 'Mon Entreprise',
            ],
        ]);
    }

    /**
     * Affiche le formulaire de création d'une nouvelle facture pour le comptable.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Invoice::class);

        return Inertia::render('Portal/Invoices/Create', [
            'organization' => [
                'id'   => $request->user()->organization?->id,
                'name' => $request->user()->organization?->name ?? 'Mon Entreprise',
            ],
            'defaultDueDate' => now()->addDays(30)->toDateString(),
        ]);
    }

    /**
     * Traite l'enregistrement de la facture (Brouillon DRAFT ou Émission SENT immédiate).
     */
    public function store(
        StoreInvoiceRequest $request,
        CreateInvoiceAction $createInvoiceAction,
        SendInvoiceAction $sendInvoiceAction
    ): RedirectResponse {
        $invoice = $createInvoiceAction->execute($request->toDTO());

        // Si l'utilisateur a choisi "Émettre et envoyer au client"
        if ($request->input('action') === 'send') {
            $invoice = $sendInvoiceAction->execute($invoice);
            $message = sprintf('La facture %s a été émise et envoyée par email avec succès.', $invoice->invoice_number);
        } else {
            $message = sprintf('Le brouillon de facture %s a été enregistré avec succès.', $invoice->invoice_number);
        }

        return redirect()->route('portal.dashboard')->with('success', $message);
    }

    /**
     * Émet et expédie par email une facture existante en brouillon.
     */
    public function send(
        int $id,
        InvoiceRepositoryInterface $invoiceRepository,
        SendInvoiceAction $sendInvoiceAction
    ): RedirectResponse {
        $invoice = $invoiceRepository->findById($id);

        if (! $invoice) {
            abort(404, 'Facture introuvable.');
        }

        Gate::authorize('update', $invoice);

        $sentInvoice = $sendInvoiceAction->execute($invoice);

        return redirect()->back()->with(
            'success',
            sprintf('La facture %s a été transmise au client par email.', $sentInvoice->invoice_number)
        );
    }
}
