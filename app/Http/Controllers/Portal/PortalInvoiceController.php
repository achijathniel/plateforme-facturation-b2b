<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Invoices\CreateInvoiceAction;
use App\Actions\Invoices\SendInvoiceAction;
use App\Actions\Invoices\UpdateInvoiceAction;
use App\Actions\Portal\GetPortalInvoicesAction;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\StoreInvoiceRequest;
use App\Http\Requests\Invoices\UpdateInvoiceRequest;
use App\Http\Requests\Portal\PortalInvoiceFilterRequest;
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
     * Respecte le pattern Skinny Controller avec Form Request, DTO et Action dédiés.
     */
    public function index(
        PortalInvoiceFilterRequest $request,
        GetPortalInvoicesAction $action
    ): Response {
        Gate::authorize('viewAny', Invoice::class);

        $filterData = $request->toDTO();
        $invoices = $action->execute($request->user(), $filterData);

        return Inertia::render('Portal/Invoices/Index', [
            'invoices'     => $invoices,
            'organization' => [
                'id'   => $request->user()->organization?->id,
                'name' => $request->user()->organization?->name ?? 'Mon Entreprise',
            ],
            'filters'      => [
                'search'         => $filterData->search,
                'status'         => $filterData->status?->value,
                'per_page'       => $filterData->perPage,
                'sort_by'        => $filterData->sortBy,
                'sort_direction' => $filterData->sortDirection,
            ],
            'statuses'     => collect(InvoiceStatus::cases())->map(fn (InvoiceStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
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

    /**
     * Affiche la vue détaillée clean et imprimable d'une facture.
     */
    public function show(
        int $id,
        InvoiceRepositoryInterface $invoiceRepository
    ): Response {
        $invoice = $invoiceRepository->findById($id);

        if (! $invoice) {
            abort(404, 'Facture introuvable.');
        }

        Gate::authorize('view', $invoice);

        $invoice->loadMissing(['items', 'payments', 'organization']);

        return Inertia::render('Portal/Invoices/Show', [
            'invoice' => [
                'id'                => $invoice->id,
                'invoice_number'    => $invoice->invoice_number,
                'client_name'       => $invoice->client_name,
                'client_email'      => $invoice->client_email,
                'client_address'    => $invoice->client_address,
                'client_tax_number' => $invoice->client_tax_number,
                'client_phone'      => $invoice->client_phone,
                'status'            => $invoice->status->value,
                'issue_date'        => $invoice->issue_date?->toDateString(),
                'due_date'          => $invoice->due_date?->toDateString(),
                'subtotal'          => (string) $invoice->subtotal,
                'tax_amount'        => (string) $invoice->tax_amount,
                'total'             => (string) $invoice->total,
                'currency'          => $invoice->currency ?? 'XOF',
                'notes'             => $invoice->notes,
                'can_edit'          => Gate::allows('update', $invoice),
                'can_send'          => $invoice->status === InvoiceStatus::DRAFT && Gate::allows('update', $invoice),
                'items'             => $invoice->items->map(fn ($item) => [
                    'id'          => $item->id,
                    'description' => $item->description,
                    'quantity'    => (float) $item->quantity,
                    'unit_price'  => (string) $item->unit_price,
                    'total'       => (string) $item->total,
                ]),
                'payments'          => $invoice->payments->map(fn ($p) => [
                    'id'             => $p->id,
                    'payment_method' => $p->payment_method->value,
                    'status'         => $p->status->value,
                    'amount'         => (string) $p->amount,
                    'paid_at'        => $p->paid_at?->toDateString(),
                ]),
            ],
            'organization' => [
                'id'   => $invoice->organization?->id,
                'name' => $invoice->organization?->name,
            ],
        ]);
    }

    /**
     * Affiche le formulaire d'édition pré-rempli pour une facture modifiable.
     */
    public function edit(
        int $id,
        InvoiceRepositoryInterface $invoiceRepository
    ): Response {
        $invoice = $invoiceRepository->findById($id);

        if (! $invoice) {
            abort(404, 'Facture introuvable.');
        }

        Gate::authorize('update', $invoice);

        $invoice->loadMissing(['items', 'organization']);

        return Inertia::render('Portal/Invoices/Edit', [
            'invoice' => [
                'id'                => $invoice->id,
                'invoice_number'    => $invoice->invoice_number,
                'client_name'       => $invoice->client_name ?? '',
                'client_email'      => $invoice->client_email ?? '',
                'client_address'    => $invoice->client_address ?? '',
                'client_tax_number' => $invoice->client_tax_number ?? '',
                'client_phone'      => $invoice->client_phone ?? '',
                'status'            => $invoice->status->value,
                'due_date'          => $invoice->due_date?->toDateString(),
                'notes'             => $invoice->notes ?? '',
                'items'             => $invoice->items->map(fn ($item) => [
                    'id'          => $item->id,
                    'description' => $item->description,
                    'quantity'    => (float) $item->quantity,
                    'unit_price'  => (string) $item->unit_price,
                ]),
            ],
            'organization' => [
                'id'   => $invoice->organization?->id,
                'name' => $invoice->organization?->name,
            ],
        ]);
    }

    /**
     * Traite la mise à jour de la facture (soit en brouillon, soit enregistrer et envoyer).
     */
    public function update(
        int $id,
        UpdateInvoiceRequest $request,
        InvoiceRepositoryInterface $invoiceRepository,
        UpdateInvoiceAction $updateInvoiceAction
    ): RedirectResponse {
        $invoice = $invoiceRepository->findById($id);

        if (! $invoice) {
            abort(404, 'Facture introuvable.');
        }

        Gate::authorize('update', $invoice);

        $dto = $request->toDTO();
        $updatedInvoice = $updateInvoiceAction->execute($invoice, $dto);

        $message = $dto->action === 'send'
            ? sprintf('La facture %s a été mise à jour et envoyée au client par email avec succès.', $updatedInvoice->invoice_number)
            : sprintf('La facture %s a été mise à jour avec succès.', $updatedInvoice->invoice_number);

        return redirect()->route('portal.invoices.show', $updatedInvoice->id)->with('success', $message);
    }
}
