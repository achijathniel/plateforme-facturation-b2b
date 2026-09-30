/**
 * Composant de badge de statut pour les factures.
 * Gère les variantes sémantiques : payée (vert), en retard (rose/rouge), envoyée (bleu), brouillon (gris).
 *
 * @param {{ status: string }} props
 */
export default function StatusBadge({ status }) {
    switch (status) {
        case 'paid':
            return (
                <span className="badge badge-paid">
                    <span className="badge-dot" aria-hidden="true"></span>
                    Payée
                </span>
            );
        case 'overdue':
            return (
                <span className="badge badge-overdue">
                    <span className="badge-dot" aria-hidden="true"></span>
                    En retard
                </span>
            );
        case 'sent':
            return (
                <span className="badge badge-sent">
                    <span className="badge-dot" aria-hidden="true"></span>
                    Envoyée
                </span>
            );
        default:
            return (
                <span className="badge badge-draft">
                    <span className="badge-dot" aria-hidden="true"></span>
                    Brouillon
                </span>
            );
    }
}
