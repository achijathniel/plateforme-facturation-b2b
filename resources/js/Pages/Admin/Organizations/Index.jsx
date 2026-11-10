import { useState, useEffect, useRef } from 'react';
import { Link, router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import AdminLayout from '../../../Layouts/AdminLayout';
import Pagination from '../../../Components/Pagination';
import { formatCurrency } from '../../../Utils/formatters';
import useDebounce from '../../../Hooks/useDebounce';

/**
 * Vue principale de gestion des entreprises pour l'administrateur.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string } },
 *     organizations: {
 *         data: Array<{
 *             id: number,
 *             name: string,
 *             tax_number?: string|null,
 *             email: string,
 *             phone?: string|null,
 *             is_active: boolean,
 *             created_at: string,
 *             users_count?: number,
 *             invoices_count?: number,
 *             total_billed?: number|string|null
 *         }>,
 *         links: Array<any>,
 *         from: number,
 *         to: number,
 *         total: number
 *     },
 *     filters: {
 *         search?: string,
 *         status?: string,
 *         per_page?: number,
 *         sort_by?: string,
 *         sort_direction?: string
 *     }
 * }} props
 */
export default function Index({ auth, organizations, filters = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const isFirstRender = useRef(true);
    const debouncedSearch = useDebounce(search, 350);

    // Synchronisation lors de l'historique du navigateur
    useEffect(() => {
        setSearch(filters.search || '');
        setStatus(filters.status || 'all');
    }, [filters.search, filters.status]);

    // Filtrage réactif dès la saisie temporisée
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        if (debouncedSearch !== (filters.search || '')) {
            applyFilters({ search: debouncedSearch });
        }
    }, [debouncedSearch, filters.search]);

    const applyFilters = (override = {}) => {
        const query = {
            search: override.search !== undefined ? override.search : search,
            status: override.status !== undefined ? override.status : status,
        };

        if (!query.search) delete query.search;
        if (!query.status || query.status === 'all') delete query.status;

        router.get('/admin/organizations', query, {
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
        setStatus('all');
        router.get('/admin/organizations', {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleToggleStatus = (org) => {
        const actionVerb = org.is_active ? 'suspendre' : 'activer';
        const confirmColor = org.is_active ? '#e11d48' : '#16a34a';

        Swal.fire({
            title: `${org.is_active ? 'Suspendre' : 'Activer'} l'accès ?`,
            text: org.is_active
                ? `L'entreprise « ${org.name} » ne pourra plus émettre de factures ni se connecter à la plateforme.`
                : `L'entreprise « ${org.name} » recouvrera immédiatement tous ses accès opérationnels.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `Oui, ${actionVerb}`,
            cancelButtonText: 'Annuler',
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#64748b',
        }).then((result) => {
            if (result.isConfirmed) {
                router.patch(`/admin/organizations/${org.id}/toggle-status`, {}, {
                    preserveScroll: true,
                });
            }
        });
    };

    const hasActiveFilters = Boolean(search || (status && status !== 'all'));

    return (
        <AdminLayout auth={auth} title="Gestion des Entreprises">
            {/* En-tête */}
            <div className="admin-page-header">
                <div>
                    <h2 className="admin-page-title">Gestion des Entreprises</h2>
                    <p className="admin-page-subtitle">
                        Supervisez, activez et gérez les entreprises clientes et émettrices de la plateforme.
                    </p>
                </div>
                <div>
                    <Link href="/admin/organizations/create" className="btn-primary" style={{ textDecoration: 'none' }}>
                        <span aria-hidden="true">➕</span>
                        <span>Nouvelle entreprise</span>
                    </Link>
                </div>
            </div>

            {/* Barre de filtres et recherche */}
            <div className="filter-card">
                <form onSubmit={handleSearchSubmit} className="filter-form">
                    <div className="filter-search-group">
                        <label htmlFor="search-org" className="sr-only">
                            Rechercher une entreprise
                        </label>
                        <div className="search-input-wrapper">
                            <span className="search-icon" aria-hidden="true">🔍</span>
                            <input
                                id="search-org"
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Raison sociale, NIF, email, téléphone..."
                                className="filter-input"
                            />
                        </div>
                        <button type="submit" className="btn-filter-submit">
                            Filtrer
                        </button>
                    </div>

                    <div className="filter-select-group">
                        <label htmlFor="status-filter" className="filter-label">
                            Statut :
                        </label>
                        <select
                            id="status-filter"
                            value={status}
                            onChange={handleStatusChange}
                            className="filter-select"
                        >
                            <option value="all">Tous les statuts</option>
                            <option value="active">Actives uniquement</option>
                            <option value="inactive">Suspendues uniquement</option>
                        </select>
                    </div>

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

            {/* Tableau des entreprises */}
            <div className="data-card">
                <div className="data-table-wrapper">
                    <table className="data-table" aria-label="Liste des entreprises enregistrées">
                        <thead>
                            <tr>
                                <th scope="col">Raison Sociale</th>
                                <th scope="col">Contact</th>
                                <th scope="col">Utilisateurs</th>
                                <th scope="col">Factures</th>
                                <th scope="col">Total Facturé</th>
                                <th scope="col">Statut</th>
                                <th scope="col" style={{ textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {organizations?.data && organizations.data.length > 0 ? (
                                organizations.data.map((org) => (
                                    <tr key={org.id}>
                                        <td>
                                            <div>
                                                <Link
                                                    href={`/admin/organizations/${org.id}`}
                                                    style={{ fontWeight: 700, color: 'var(--text-primary)', textDecoration: 'none' }}
                                                >
                                                    {org.name}
                                                </Link>
                                                {org.tax_number && (
                                                    <div style={{ fontSize: '12px', color: 'var(--text-muted)' }}>
                                                        NIF : {org.tax_number}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <a href={`mailto:${org.email}`} style={{ color: 'var(--blue-primary)', textDecoration: 'none' }}>
                                                    {org.email}
                                                </a>
                                                {org.phone && (
                                                    <div style={{ fontSize: '12px', color: 'var(--text-muted)' }}>
                                                        {org.phone}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td>
                                            <span className="table-code">{org.users_count ?? 0}</span>
                                        </td>
                                        <td>
                                            <span className="table-code">{org.invoices_count ?? 0}</span>
                                        </td>
                                        <td>
                                            <span className="table-amount">
                                                {formatCurrency(org.total_billed ?? 0)}
                                            </span>
                                            <span className="table-currency"> XOF</span>
                                        </td>
                                        <td>
                                            <span className={`status-badge ${org.is_active ? 'badge-active' : 'badge-inactive'}`}>
                                                {org.is_active ? 'Active' : 'Suspendue'}
                                            </span>
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            <div style={{ display: 'inline-flex', gap: '8px', alignItems: 'center' }}>
                                                <Link
                                                    href={`/admin/organizations/${org.id}`}
                                                    className="btn-toggle-status"
                                                    title="Consulter la fiche 360°"
                                                    aria-label={`Consulter la fiche 360° de ${org.name}`}
                                                    style={{ textDecoration: 'none' }}
                                                >
                                                    <span aria-hidden="true">👁️</span> Voir
                                                </Link>
                                                <Link
                                                    href={`/admin/organizations/${org.id}/edit`}
                                                    className="btn-toggle-status"
                                                    title="Modifier l'entreprise"
                                                    aria-label={`Modifier l'entreprise ${org.name}`}
                                                    style={{ textDecoration: 'none' }}
                                                >
                                                    <span aria-hidden="true">✏️</span> Modifier
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleStatus(org)}
                                                    className={`btn-toggle-status ${org.is_active ? 'is-active-btn' : ''}`}
                                                    title={org.is_active ? "Suspendre l'accès" : "Réactiver l'accès"}
                                                    aria-label={`${org.is_active ? 'Suspendre' : 'Activer'} l'entreprise ${org.name}`}
                                                >
                                                    <span aria-hidden="true">{org.is_active ? '⏸️' : '▶️'}</span>{' '}
                                                    {org.is_active ? 'Suspendre' : 'Activer'}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={7} className="table-empty-cell">
                                        <div className="table-empty-state">
                                            <span className="empty-state-icon" aria-hidden="true">🏢</span>
                                            <p className="empty-state-title">Aucune entreprise trouvée</p>
                                            <p className="empty-state-text">
                                                {hasActiveFilters
                                                    ? 'Aucune entreprise ne correspond à vos critères de recherche.'
                                                    : 'Aucune entreprise n’a encore été enregistrée sur la plateforme.'}
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
                {organizations?.links && (
                    <div className="data-card-footer">
                        <Pagination
                            links={organizations.links}
                            from={organizations.from}
                            to={organizations.to}
                            total={organizations.total}
                        />
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
