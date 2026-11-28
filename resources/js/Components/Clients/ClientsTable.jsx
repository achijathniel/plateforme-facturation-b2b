import { Link } from '@inertiajs/react';
import { Archive, Eye, FilePlus2, Pencil, Plus, Users } from 'lucide-react';
import { formatCurrency } from '../../Utils/formatters';

/**
 * Tableau réutilisable de l'annuaire des clients B2B d'une entreprise.
 *
 * @param {{
 *     clients?: Array<{
 *         id: number,
 *         name: string,
 *         tax_number?: string|null,
 *         email?: string|null,
 *         phone?: string|null,
 *         invoices_count?: number,
 *         total_billed?: number|string|null
 *     }>,
 *     canManage: boolean,
 *     hasActiveFilters?: boolean,
 *     onResetFilters?: () => void,
 *     onArchive: (client: { id: number, name: string }) => void
 * }} props
 */
export default function ClientsTable({
    clients = [],
    canManage,
    hasActiveFilters = false,
    onResetFilters,
    onArchive,
}) {
    return (
        <div className="data-table-wrapper">
            <table className="data-table clients-table" aria-label="Liste des clients de votre entreprise">
                <thead>
                    <tr>
                        <th scope="col" className="clients-col-name">Client</th>
                        <th scope="col" className="clients-col-contact">Contact</th>
                        <th
                            scope="col"
                            className="clients-col-invoices"
                            title="Nombre de factures"
                            aria-label="Nombre de factures"
                        >
                            Factures
                        </th>
                        <th scope="col" className="clients-col-total">Total Facturé</th>
                        <th scope="col" className="clients-col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {clients.length > 0 ? (
                        clients.map((client) => (
                            <tr key={client.id}>
                                <td className="clients-col-name">
                                    <div className="org-cell-stack">
                                        <Link
                                            href={`/portal/clients/${client.id}`}
                                            className="org-name-link"
                                            title={client.name}
                                        >
                                            {client.name}
                                        </Link>
                                        {client.tax_number && (
                                            <div className="org-cell-meta" title={`NIF : ${client.tax_number}`}>
                                                NIF : {client.tax_number}
                                            </div>
                                        )}
                                    </div>
                                </td>
                                <td className="clients-col-contact">
                                    <div className="org-cell-stack">
                                        {client.email ? (
                                            <a
                                                href={`mailto:${client.email}`}
                                                className="org-email-link"
                                                title={client.email}
                                            >
                                                {client.email}
                                            </a>
                                        ) : (
                                            <span className="org-cell-meta">Email non renseigné</span>
                                        )}
                                        {client.phone && (
                                            <div className="org-cell-meta" title={client.phone}>
                                                {client.phone}
                                            </div>
                                        )}
                                    </div>
                                </td>
                                <td className="clients-col-invoices">
                                    <span className="table-code">{client.invoices_count ?? 0}</span>
                                </td>
                                <td className="clients-col-total">
                                    <span className="table-amount">
                                        {formatCurrency(client.total_billed ?? 0)}
                                    </span>
                                    <span className="table-currency"> XOF</span>
                                </td>
                                <td className="clients-col-actions">
                                    <div className="clients-actions-group">
                                        <Link
                                            href={`/portal/clients/${client.id}`}
                                            className="btn-toggle-status"
                                            title="Voir la fiche client"
                                            aria-label={`Voir la fiche de ${client.name}`}
                                        >
                                            <Eye size={14} aria-hidden="true" />
                                            <span>Voir</span>
                                        </Link>
                                        {canManage && (
                                            <>
                                                <Link
                                                    href={`/portal/invoices/create?client_id=${client.id}`}
                                                    className="btn-toggle-status"
                                                    title="Créer une facture pour ce client"
                                                    aria-label={`Facturer ${client.name}`}
                                                >
                                                    <FilePlus2 size={14} aria-hidden="true" />
                                                    <span>Facturer</span>
                                                </Link>
                                                <Link
                                                    href={`/portal/clients/${client.id}/edit`}
                                                    className="btn-toggle-status"
                                                    title="Modifier les coordonnées"
                                                    aria-label={`Modifier ${client.name}`}
                                                >
                                                    <Pencil size={14} aria-hidden="true" />
                                                    <span>Modifier</span>
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => onArchive(client)}
                                                    className="btn-danger-outline"
                                                    title="Archiver ce client"
                                                    aria-label={`Archiver ${client.name}`}
                                                >
                                                    <Archive size={14} aria-hidden="true" />
                                                    <span>Archiver</span>
                                                </button>
                                            </>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td colSpan={5} className="table-empty-cell">
                                <div className="table-empty-state">
                                    <span className="empty-state-icon" aria-hidden="true">
                                        <Users size={32} aria-hidden="true" />
                                    </span>
                                    <p className="empty-state-title">
                                        {hasActiveFilters ? 'Aucun client trouvé' : 'Aucun client enregistré'}
                                    </p>
                                    <p className="empty-state-text">
                                        {hasActiveFilters
                                            ? 'Aucun client ne correspond à votre recherche.'
                                            : "Ajoutez votre premier client pour suivre son historique et pré-remplir vos factures."}
                                    </p>
                                    {hasActiveFilters && onResetFilters ? (
                                        <button
                                            type="button"
                                            onClick={onResetFilters}
                                            className="btn-secondary"
                                            style={{ marginTop: '12px' }}
                                        >
                                            Réinitialiser la recherche
                                        </button>
                                    ) : (
                                        canManage && (
                                            <Link
                                                href="/portal/clients/create"
                                                className="btn-primary"
                                                style={{ marginTop: '12px', textDecoration: 'none' }}
                                            >
                                                <Plus size={15} aria-hidden="true" />
                                                <span>Ajouter un client</span>
                                            </Link>
                                        )
                                    )}
                                </div>
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
