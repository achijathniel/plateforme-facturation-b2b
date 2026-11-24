import { Link, router } from '@inertiajs/react';
import { Eye, FileText, Plus, Search, X } from 'lucide-react';
import PortalLayout from '../../../Layouts/PortalLayout';
import StatusBadge from '../../../Components/StatusBadge';
import Pagination from '../../../Components/Pagination';
import { formatCurrency, formatDate } from '../../../Utils/formatters';
import useInertiaFilters from '../../../Hooks/useInertiaFilters';

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
export default function Index({ auth, organization, invoices, filters = {}, statuses = [] }) {
    const {
        values,
        setField,
        submitFilters: handleSearchSubmit,
        resetFilters: handleReset,
        hasActiveFilters,
    } = useInertiaFilters({
        routeUrl: '/portal/invoices',
        initialFilters: filters,
        defaultValues: { search: '', status: '' },
    });

    const { search, status } = values;
    const setSearch = (val) => setField('search', val, false);
    const handleStatusChange = (e) => setField('status', e.target.value);
    const invoiceList = invoices?.data || [];

    return (
        <PortalLayout auth={auth} organization={organization} title="Mes Factures">
            {/* En-tête de page */}
            <div className="admin-page-header">
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px' }}>
                    <div>
                        <h1 className="portal-page-title">Mes Factures</h1>
                        <p className="portal-page-subtitle">
                            Historique, recherche et suivi des factures de <strong>{organization?.name}</strong>
                        </p>
                    </div>

                    <Link
                        href="/portal/invoices/create"
                        className="btn-action-new-invoice"
                    >
                        <Plus size={16} aria-hidden="true" />
                        <span>Nouvelle Facture</span>
                    </Link>
                </div>
            </div>

            {/* Barre de recherche et de filtres */}
            <div className="filter-card">
                <form onSubmit={handleSearchSubmit} className="filter-form">
                    <div className="filter-search-group">
                        <label htmlFor="portal-search-input" className="sr-only">
                            Rechercher par numéro de facture
                        </label>
                        <div className="search-input-wrapper">
                            <span className="search-icon" aria-hidden="true">
                                <Search size={15} aria-hidden="true" />
                            </span>
                            <input
                                id="portal-search-input"
                                type="search"
                                name="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Rechercher par n° de facture..."
                                className="filter-input"
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
                            <X size={13} aria-hidden="true" />
                            <span>Réinitialiser</span>
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
                                <th scope="col">Client</th>
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
                                    <td colSpan="7" className="table-empty-cell">
                                        <div className="table-empty-state">
                                            <span className="empty-state-icon" aria-hidden="true">
                                                {hasActiveFilters
                                                    ? <Search size={32} aria-hidden="true" />
                                                    : <FileText size={32} aria-hidden="true" />}
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
                                    <tr
                                        key={invoice.id}
                                        className="clickable-row"
                                        onClick={(e) => {
                                            const selection = window.getSelection();
                                            if (selection && selection.toString().length > 0) {
                                                return;
                                            }

                                            if (e.target.closest('a, button')) {
                                                return;
                                            }

                                            router.visit(`/portal/invoices/${invoice.id}`);
                                        }}
                                    >
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
                                            <div style={{ display: 'flex', flexDirection: 'column', gap: '2px' }}>
                                                <strong style={{ color: 'var(--text-primary)' }}>
                                                    {invoice.client_name || '-'}
                                                </strong>
                                                {invoice.client_email && (
                                                    <span style={{ fontSize: '11px', color: 'var(--text-secondary)' }}>
                                                        {invoice.client_email}
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                        <td>
                                            {invoice.issue_date
                                                ? formatDate(invoice.issue_date)
                                                : '-'}
                                        </td>
                                        <td>
                                            {invoice.due_date
                                                ? formatDate(invoice.due_date)
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
                                                <Eye size={14} aria-hidden="true" />
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
                            itemLabel="factures"
                        />
                    </div>
                )}
            </div>
        </PortalLayout>
    );
}
