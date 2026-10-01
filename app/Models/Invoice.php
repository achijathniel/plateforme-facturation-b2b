<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'invoice_number',
        'client_name',
        'client_email',
        'client_address',
        'client_tax_number',
        'client_phone',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total',
        'currency',
        'notes',
    ];

    /**
     * Typage automatique des colonnes.
     */
    protected function casts(): array
    {
        return [
            'status'     => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date'   => 'date',
            'subtotal'   => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total'      => 'decimal:2',
        ];
    }

    /**
     * Relation : Une facture appartient à une entreprise cliente.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Relation : Une facture possède plusieurs lignes détaillées.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Relation : Une facture possède un historique de paiements (complets ou partiels).
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope : Filtrer les factures en attente de paiement.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::OVERDUE]);
    }

    /**
     * Scope : Filtrer les factures en retard.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::OVERDUE)
                     ->orWhere(function (Builder $q) {
                         $q->where('status', InvoiceStatus::SENT)
                           ->where('due_date', '<', now());
                     });
    }
}
