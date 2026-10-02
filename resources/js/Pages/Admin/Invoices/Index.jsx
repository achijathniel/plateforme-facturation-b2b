import { useState, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatusBadge from '../../../Components/StatusBadge';
import Pagination from '../../../Components/Pagination';
import { formatCurrency } from '../../../Utils/formatters';
import useDebounce from '../../../Hooks/useDebounce';

/**
 * Vue principale de gestion et consultation des factures administratives.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string } },
 *     invoices: {
 *         data: Array<any>,
 *         links: Array<any>,
 *         from: number,
 *         to: number,
 *         total: number
 *     },
 *     filters: {
 *         search?: string,
 *         status?: string,
 *         organization_id?: number|string,
 *         per_page?: number,
 *         sort_by?: string,
 *         sort_direction?: string
 *     },
 *     statuses: Array<{ value: string, label: string }>,
 *     organizations: Array<{ id: number, name: string }>
 * }} props
 */
export default function InvoicesIndex({ auth, invoices, filters = {}, statuses = [], organizations = [] }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [organizationId, setOrganizationId] = useState(filters.organization_id || '');
    const isFirstRender = useRef(true);
    const debouncedSearch = useDebounce(search, 350);

    // Synchronisation de l'état local lors des retours en arrière/avant du navigateur
    useEffect(() => {
        setSearch(filters.search || '');
        setStatus(filters.status || '');
        setOrganizationId(filters.organization_id || '');
    }, [filters.search, filters.status, filters.organization_id]);

    // Filtrage réactif dès que la valeur de recherche temporisée change
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        if (debouncedSearch !== (filters.search || '')) {
            applyFilters({ search: debouncedSearch });
        }
    }, [debouncedSearch]);

    /**
     * Déclenche la recherche ou le filtrage avec conservation d'état Inertia.
     */
    const applyFilters = (override = {}) => {
        const query = {
            search: override.search !== undefined ? override.search : search,
            status: override.status !== undefined ? override.status : status,
            organization_id: override.organization_id !== undefined ? override.organization_id : organizationId,
        };

        // Supprime les clés vides pour une URL propre
        Object.keys(query).forEach((key) => {
            if (!query[key]) {
                delete query[key];
            }
        });

        router.get('/admin/invoices', query, {
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

    const handleOrganizationChange = (e) => {
        const newOrgId = e.target.value;
        setOrganizationId(newOrgId);
        applyFilters({ organization_id: newOrgId });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setOrganizationId('');
        router.get('/admin/invoices', {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const hasActiveFilters = Boolean(search || status || organizationId);

    return (
        <AdminLayout auth={auth} title="Gestion des Factures - Administration">
            {/* En-tête de la section */}
            <div className="admin-page-header">
                <div>
                    <h2 className="admin-page-title">Gestion des Factures</h2>
                    <p className="admin-page-subtitle">
                        Consultez, filtrez et supervisez l'intégralité des factures émises sur la plateforme.
                    </p>
                </div>
            </div>

            {/* Barre de filtres et recherche */}
            <div className="filter-card">
                <form onSubmit={handleSearchSubmit} className="filter-form">
                    {/* Recherche textuelle */}
                    <div className="filter-search-group">
                        <label htmlFor="search-input" className="sr-only">
                            Rechercher une facture
                        </label>
                        <div className="search-input-wrapper">
                            <span className="search-icon" aria-hidden="true">🔍</span>
                            <input
                                id="search-input"
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Numéro (INV-...), entreprise cliente..."
                                className="filter-input"
                            />
                        </div>
                        <button type="submit" className="btn-filter-submit">
                            Filtrer
                        </button>
                    </div>

                    {/* Filtre par entreprise */}
                    <div className="filter-select-group">
                        <label htmlFor="organization-select" className="filter-label">
                            Entreprise :
                        </label>
                        <select
                            id="organization-select"
                            value={organizationId}
                            onChange={handleOrganizationChange}
                            className="filter-select"
                        >
                            <option value="">Toutes les entreprises</option>
                            {organizations?.map((org) => (
                                <option key={org.id} value={org.id}>
                                    {org.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Filtre par statut */}
                    <div className="filter-select-group">
                        <label htmlFor="status-select" className="filter-label">
                            Statut :
                        </label>
                        <select
                            id="status-select"
                            value={status}
                            onChange={handleStatusChange}
                            className="filter-select"
                        >
                            <option value="">Tous les statuts</option>
                            {statuses?.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Bouton de réinitialisation si filtre actif */}
                    {hasActiveFilters && (
                        <button
                            type="button"
                            onClick={handleReset}
                            className="btn-reset-filter"
                            title="Réinitialiser tous les filtres"
                        >
                            ✕ Effacer les filtres
                        </button>
                    )}
                </form>
            </div>

            {/* Tableau des factures */}
            <div className="data-card">
                <div className="data-table-wrapper">
                    <table className="data-table" aria-label="Liste des factures de la plateforme">
                        <thead>
                            <tr>
                                <th scope="col">Numéro</th>
                                <th scope="col">Entreprise Cliente</th>
                                <th scope="col">Date d'Émission</th>
                                <th scope="col">Échéance</th>
                                <th scope="col">Montant Total</th>
                                <th scope="col">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            {invoices?.data && invoices.data.length > 0 ? (
                                invoices.data.map((invoice) => (
                                    <tr key={invoice.id || invoice.invoice_number}>
                                        <td>
                                            <span className="table-code">
                                                {invoice.invoice_number}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>
                                                {invoice.organization?.name || 'Entreprise N/A'}
                                            </strong>
                                        </td>
                                        <td>{invoice.issue_date}</td>
                                        <td>{invoice.due_date}</td>
                                        <td>
                                            <span className="table-amount">
                                                {formatCurrency(invoice.total)}
                                            </span>
                                            <span className="table-currency">
                                                {invoice.currency || 'XOF'}
                                            </span>
                                        </td>
                                        <td>
                                            <StatusBadge status={invoice.status} />
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={6} className="table-empty-cell">
                                        <div className="table-empty-state">
                                            <span className="empty-state-icon" aria-hidden="true">
                                                📄
                                            </span>
                                            <p className="empty-state-title">
                                                Aucune facture trouvée
                                            </p>
                                            <p className="empty-state-text">
                                                {hasActiveFilters
                                                    ? 'Aucun résultat ne correspond à vos critères de recherche. Essayez d’ajuster ou d’effacer vos filtres.'
                                                    : 'Aucune facture n’a encore été enregistrée sur la plateforme.'}
                                            </p>
                                            {hasActiveFilters && (
                                                <button
                                                    type="button"
                                                    onClick={handleReset}
                                                    className="btn-secondary"
                                                    style={{ marginTop: '12px' }}
                                                >
                                                    Réinitialiser les filtres
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {invoices?.links && (
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
        </AdminLayout>
    );
}
