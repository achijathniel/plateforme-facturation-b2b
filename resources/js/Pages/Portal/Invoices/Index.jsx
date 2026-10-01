import { useState, useEffect } from 'react';
import { Link, router } from '@inertiajs/react';
import PortalLayout from '../../../Layouts/PortalLayout';
import StatusBadge from '../../../Components/StatusBadge';
import Pagination from '../../../Components/Pagination';
import { formatCurrency } from '../../../Utils/formatters';

/**
 * Vue de consultation et de recherche des factures de l'espace comptable.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     invoices: {
 *         data: Array<any>,
 *         links: Array<any>,
 *         from: number,
 *         to: number,
 *         total: number
 *     },
 *     filters?: {
 *         search?: string,
 *         status?: string,
 *         per_page?: number,
 *         sort_by?: string,
 *         sort_direction?: string
 *     },
 *     statuses?: Array<{ value: string, label: string }>
 * }} props
 */
const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
});

export default function Index({ auth, organization, invoices, filters = {}, statuses = [] }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    // Synchronisation de l'état local avec les retours historiques ou props
    useEffect(() => {
        setSearch(filters.search || '');
        setStatus(filters.status || '');
    }, [filters.search, filters.status]);

    /**
     * Applique les filtres avec conservation d'état Inertia (sans rechargement complet).
     */
    const applyFilters = (override = {}) => {
        const query = {
            search: override.search !== undefined ? override.search : search,
            status: override.status !== undefined ? override.status : status,
        };

        // Éliminer les valeurs vides pour conserver une URL propre
        Object.keys(query).forEach((key) => {
            if (!query[key]) {
                delete query[key];
            }
        });

        router.get('/portal/invoices', query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        applyFilters();
    };

    const handleStatusChange = (e) => {
        const newStatus = e.target.value;
        setStatus(newStatus);
        applyFilters({ status: newStatus });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        router.get('/portal/invoices', {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const hasActiveFilters = Boolean(search || status);
    const invoiceList = invoices?.data || [];

    return (
        <PortalLayout auth={auth} organization={organization} title="Mes Factures">
            {/* En-tête de page */}
            <div className="admin-page-header">
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px' }}>
                    <div>
                        <h2 className="portal-page-title">Mes Factures</h2>
                        <p className="portal-page-subtitle">
                            Historique, recherche et suivi des factures de <strong>{organization?.name}</strong>
                        </p>
                    </div>

                    <Link
                        href="/portal/invoices/create"
                        className="btn-action-new-invoice"
                    >
                        <span aria-hidden="true">➕</span>
                        <span>Nouvelle Facture</span>
                    </Link>
                </div>
            </div>

            {/* Barre de recherche et de filtres */}
            <div className="filter-card">
                <form onSubmit={handleSearchSubmit} className="filter-form">
                    <div className="filter-search-group">
                        <div className="search-input-wrapper">
                            <span className="search-icon" aria-hidden="true">🔍</span>
                            <input
                                type="search"
                                name="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Rechercher par n° de facture..."
                                className="filter-input"
                                aria-label="Recherche par numéro de facture"
                            />
                        </div>
                        <button type="submit" className="btn-filter-submit">
                            Rechercher
                        </button>
                    </div>

                    <div className="filter-select-group">
                        <label htmlFor="status-select" className="filter-label">
                            Statut :
                        </label>
                        <select
                            id="status-select"
                            name="status"
                            value={status}
                            onChange={handleStatusChange}
                            className="filter-select"
                            aria-label="Filtrer par statut de facture"
                        >
                            <option value="">Tous les statuts</option>
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    {hasActiveFilters && (
                        <button
                            type="button"
                            onClick={handleReset}
                            className="btn-reset-filter"
                            aria-label="Réinitialiser les filtres"
                        >
                            ✕ Réinitialiser
                        </button>
                    )}
                </form>
            </div>

            {/* Tableau des factures */}
            <div className="data-card">
                <div className="data-card-header">
                    <div className="data-card-title-group">
                        <h3 className="data-card-title">Factures de l'organisation</h3>
                        <p className="data-card-subtitle">
                            {invoices?.total || 0} facture(s) trouvée(s)
                            {hasActiveFilters && ' (filtrées)'}
                        </p>
                    </div>
                </div>

                <div className="data-table-wrapper">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Numéro</th>
                                <th scope="col">Date d'émission</th>
                                <th scope="col">Échéance</th>
                                <th scope="col">Montant Total</th>
                                <th scope="col">Statut</th>
                                <th scope="col" style={{ textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {invoiceList.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="table-empty-cell">
                                        <div className="table-empty-state">
                                            <span className="empty-state-icon" aria-hidden="true">
                                                {hasActiveFilters ? '🔍' : '📑'}
                                            </span>
                                            <p className="empty-state-title">
                                                {hasActiveFilters
                                                    ? 'Aucune facture trouvée'
                                                    : 'Aucune facture enregistrée'}
                                            </p>
                                            <p className="empty-state-text">
                                                {hasActiveFilters
                                                    ? 'Aucune facture ne correspond à vos critères de recherche.'
                                                    : "Vous n'avez pas encore créé de facture pour cette organisation."}
                                            </p>
                                            {hasActiveFilters && (
                                                <button
                                                    type="button"
                                                    onClick={handleReset}
                                                    className="btn-reset-filter"
                                                    style={{ marginTop: '12px' }}
                                                >
                                                    Effacer les critères de recherche
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                invoiceList.map((invoice) => (
                                    <tr key={invoice.id}>
                                        <td>
                                            <Link
                                                href={`/portal/invoices/${invoice.id}`}
                                                className="link-invoice-num"
                                                title={`Consulter la facture ${invoice.invoice_number}`}
                                            >
                                                {invoice.invoice_number}
                                            </Link>
                                        </td>
                                        <td>
                                            {invoice.issue_date
                                                ? dateFormatter.format(new Date(invoice.issue_date))
                                                : '-'}
                                        </td>
                                        <td>
                                            {invoice.due_date
                                                ? dateFormatter.format(new Date(invoice.due_date))
                                                : '-'}
                                        </td>
                                        <td>
                                            <span className="table-amount">
                                                {formatCurrency(invoice.total, invoice.currency || 'XOF')}
                                            </span>
                                        </td>
                                        <td>
                                            <StatusBadge status={invoice.status} />
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            <Link
                                                href={`/portal/invoices/${invoice.id}`}
                                                className="btn-table-action-view"
                                                aria-label={`Consulter la facture ${invoice.invoice_number}`}
                                            >
                                                <span aria-hidden="true">👁️</span>
                                                <span>Consulter</span>
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination accessible */}
                {invoices && invoices.total > 0 && (
                    <div className="data-card-footer">
                        <Pagination
                            links={invoices.links}
                            from={invoices.from}
                            to={invoices.to}
                            total={invoices.total}
                        />
                    </div>
                )}
            </div>
        </PortalLayout>
    );
}
