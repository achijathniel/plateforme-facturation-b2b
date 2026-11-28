const STATUS_META = {
    paid: { className: 'badge-paid', label: 'Payée' },
    overdue: { className: 'badge-overdue', label: 'En retard' },
    sent: { className: 'badge-sent', label: 'Envoyée' },
    cancelled: { className: 'badge-cancelled', label: 'Annulée' },
    draft: { className: 'badge-draft', label: 'Brouillon' },
};

/**
 * Composant de badge de statut pour les factures.
 * Gère les variantes sémantiques : payée, en retard, envoyée, annulée et brouillon.
 *
 * @param {{ status: string }} props
 */
export default function StatusBadge({ status }) {
    const meta = STATUS_META[status] || STATUS_META.draft;

    return (
        <span className={`badge ${meta.className}`}>
            <span className="badge-dot" aria-hidden="true" />
            <span>{meta.label}</span>
        </span>
    );
}
