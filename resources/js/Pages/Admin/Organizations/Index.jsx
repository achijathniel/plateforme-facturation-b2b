import { Link, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Pagination from '../../../Components/Pagination';
import { confirmAction } from '../../../Utils/confirmAction';
import { formatCurrency } from '../../../Utils/formatters';
import useInertiaFilters from '../../../Hooks/useInertiaFilters';

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
    const {
        values,
        setField,
        submitFilters: handleSearchSubmit,
        resetFilters: handleReset,
        hasActiveFilters,
    } = useInertiaFilters({
        routeUrl: '/admin/organizations',
        initialFilters: filters,
        defaultValues: { search: '', status: 'all' },
    });

    const { search, status } = values;
    const setSearch = (val) => setField('search', val, false);
    const handleStatusChange = (e) => setField('status', e.target.value);

    const handleToggleStatus = async (org) => {
        const actionVerb = org.is_active ? 'suspendre' : 'activer';

        const confirmed = await confirmAction({
            title: `${org.is_active ? 'Suspendre' : 'Activer'} l'accès ?`,
            text: org.is_active
                ? `L'entreprise « ${org.name} » ne pourra plus émettre de factures ni se connecter à la plateforme.`
                : `L'entreprise « ${org.name} » recouvrera immédiatement tous ses accès opérationnels.`,
            confirmText: `Oui, ${actionVerb}`,
            icon: 'warning',
            tone: org.is_active ? 'danger' : 'success',
        });

        if (confirmed) {
            router.post(`/admin/organizations/${org.id}/toggle-status`, {}, {
                preserveScroll: true,
            });
        }
    };

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
                <div className="org-header-actions">
                    <Link
                        href="/admin/organizations/import"
                        className="btn-secondary"
                    >
                        <span aria-hidden="true">📥</span>
                        <span>Importer Excel</span>
                    </Link>
                    <Link
                        href="/admin/organizations/create"
                        className="btn-primary"
                    >
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
                    <table className="data-table org-table" aria-label="Liste des entreprises enregistrées">
                        <thead>
                            <tr>
                                <th scope="col" className="org-col-name">Entreprise</th>
                                <th scope="col" className="org-col-contact">Contact</th>
                                <th
                                    scope="col"
                                    className="org-col-users"
                                    title="Utilisateurs"
                                    aria-label="Utilisateurs"
                                >
                                    Util.
                                </th>
                                <th
                                    scope="col"
                                    className="org-col-invoices"
                                    title="Factures"
                                    aria-label="Factures"
                                >
                                    Fact.
                                </th>
                                <th scope="col" className="org-col-total">Total Facturé</th>
                                <th scope="col" className="org-col-status">Statut</th>
                                <th scope="col" className="org-col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {organizations?.data && organizations.data.length > 0 ? (
                                organizations.data.map((org) => (
                                    <tr key={org.id}>
                                        <td className="org-col-name">
                                            <div className="org-cell-stack">
                                                <Link
                                                    href={`/admin/organizations/${org.id}`}
                                                    className="org-name-link"
                                                    title={org.name}
                                                >
                                                    {org.name}
                                                </Link>
                                                {org.tax_number && (
                                                    <div
                                                        className="org-cell-meta"
                                                        title={`NIF : ${org.tax_number}`}
                                                    >
                                                        NIF : {org.tax_number}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="org-col-contact">
                                            <div className="org-cell-stack">
                                                <a
                                                    href={`mailto:${org.email}`}
                                                    className="org-email-link"
                                                    title={org.email}
                                                >
                                                    {org.email}
                                                </a>
                                                {org.phone && (
                                                    <div className="org-cell-meta" title={org.phone}>
                                                        {org.phone}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="org-col-users">
                                            <span className="table-code">{org.users_count ?? 0}</span>
                                        </td>
                                        <td className="org-col-invoices">
                                            <span className="table-code">{org.invoices_count ?? 0}</span>
                                        </td>
                                        <td className="org-col-total">
                                            <span className="table-amount">
                                                {formatCurrency(org.total_billed ?? 0)}
                                            </span>
                                            <span className="table-currency"> XOF</span>
                                        </td>
                                        <td className="org-col-status">
                                            <span className={`status-badge ${org.is_active ? 'badge-active' : 'badge-inactive'}`}>
                                                {org.is_active ? 'Active' : 'Suspendue'}
                                            </span>
                                        </td>
                                        <td className="org-col-actions">
                                            <div className="org-actions-group">
                                                <Link
                                                    href={`/admin/organizations/${org.id}`}
                                                    className="btn-toggle-status"
                                                    title="Consulter la fiche 360°"
                                                    aria-label={`Consulter la fiche 360° de ${org.name}`}
                                                >
                                                    <span aria-hidden="true">👁️</span> Voir
                                                </Link>
                                                <Link
                                                    href={`/admin/organizations/${org.id}/edit`}
                                                    className="btn-toggle-status"
                                                    title="Modifier l'entreprise"
                                                    aria-label={`Modifier l'entreprise ${org.name}`}
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
                            itemLabel="entreprises"
                        />
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
