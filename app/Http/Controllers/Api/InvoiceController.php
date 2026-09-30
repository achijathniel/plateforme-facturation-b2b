<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Invoices\CreateInvoiceAction;
use App\Actions\Invoices\SendInvoiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    /**
     * Injection du contrat de données via le constructeur (SOLID D - Inversion de dépendances).
     */
    public function __construct(
        protected readonly InvoiceRepositoryInterface $invoiceRepository
    ) {}

    /**
     * Liste paginée des factures selon l'organisation de l'utilisateur connecté.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Invoice::class);

        $perPage = (int) $request->query('per_page', 15);
        $invoices = $this->invoiceRepository->paginateForUser($request->user(), $perPage);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Détail d'une facture protégée par la Policy (Multi-tenancy).
     */
    public function show(int $id): InvoiceResource
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice) {
            abort(404, 'Facture introuvable.');
        }

        Gate::authorize('view', $invoice);

        return new InvoiceResource($invoice);
    }

    /**
     * Création d'une nouvelle facture. Par défaut sur l'API, émet et envoie si send_now est true.
     */
    public function store(
        StoreInvoiceRequest $request,
        CreateInvoiceAction $createInvoiceAction,
        SendInvoiceAction $sendInvoiceAction
    ): JsonResponse {
        $invoice = $createInvoiceAction->execute($request->toDTO());

        if ($request->boolean('send_now', true)) {
            $invoice = $sendInvoiceAction->execute($invoice);
        }

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Émission et expédition par email d'un brouillon existant.
     */
    public function send(int $id, SendInvoiceAction $sendInvoiceAction): InvoiceResource
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice) {
            abort(404, 'Facture introuvable.');
        }

        Gate::authorize('update', $invoice);

        $sentInvoice = $sendInvoiceAction->execute($invoice);

        return new InvoiceResource($sentInvoice);
    }
}
