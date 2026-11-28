import { Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import MetricCard from '../MetricCard';
import StatusBadge from '../StatusBadge';
import DashboardFilterBar from './DashboardFilterBar';
import RevenueTrendChart from './RevenueTrendChart';
import StatusDonutChart from './StatusDonutChart';
import TopEntitiesChart from './TopEntitiesChart';
import useInertiaFilters from '../../Hooks/useInertiaFilters';
import { formatCurrency, formatDate, formatNumber } from '../../Utils/formatters';

/**
 * Vue analytique partagée (DRY) entre le tableau de bord Administration et le Portail Entreprise.
 * Orchestre les filtres, KPIs, graphiques Recharts, Top 5 et dernières factures filtrées.
 *
 * @param {{
 *     routeUrl: string,
 *     headingTitle: string,
 *     headingSubtitle: string,
 *     stats: Record<string, any>,
 *     filters?: Record<string, any>,
 *     statuses?: Array<{ value: string, label: string }>,
 *     organizations?: Array<{ id: number, name: string }>,
 *     showOrganizationFilter?: boolean,
 *     kpis: Array<{
 *         key: string,
 *         label: string,
 *         value: string|number,
 *         currency?: string,
 *         icon: React.ReactNode,
 *         subtext?: string,
 *         highlightBlue?: boolean,
 *         valueColor?: string
 *     }>,
 *     topEntitiesTitle: string,
 *     topEntitiesSubtitle: string,
 *     enableEntityFilter?: boolean,
 *     recentInvoicesSubtitle: string,
 *     recentInvoicesPartyLabel: string,
 *     recentInvoicesAllUrl: string,
 *     recentInvoicesAllLabel: string,
 *     recentInvoicesLinkInvoices?: boolean,
 *     currency?: string
 * }} props
 */
export default function DashboardPageView({
    routeUrl,
    headingTitle,
    headingSubtitle,
    stats,
    filters = {},
    statuses = [],
    organizations = [],
    showOrganizationFilter = false,
    kpis = [],
    topEntitiesTitle,
    topEntitiesSubtitle,
    enableEntityFilter = false,
    recentInvoicesSubtitle,
    recentInvoicesPartyLabel,
    recentInvoicesAllUrl,
    recentInvoicesAllLabel,
    recentInvoicesLinkInvoices = false,
    currency = 'XOF',
}) {
    const defaultValues = showOrganizationFilter
        ? { period: '12m', status: '', organization_id: '' }
        : { period: '12m', status: '' };

    const { values, setField, resetFilters, hasActiveFilters } = useInertiaFilters({
        routeUrl,
        initialFilters: filters,
        defaultValues,
    });

    const handleToggleStatus = (statusValue) => {
        setField('status', values.status === statusValue ? '' : statusValue);
    };

    const handleToggleOrganization = (entity) => {
        if (entity?.id == null) {
            return;
        }
        const nextId = String(values.organization_id) === String(entity.id) ? '' : String(entity.id);
        setField('organization_id', nextId);
    };

    const recentInvoices = stats?.recent_invoices || [];

    return (
        <>
            <div className="dashboard-hero-header">
                <div>
                    <h1 className="admin-page-title">{headingTitle}</h1>
                    <p className="dashboard-hero-sub">{headingSubtitle}</p>
                </div>
            </div>

            <p className="sr-only" role="status" aria-live="polite" aria-atomic="true">
                {formatNumber(stats?.total_invoices_count || 0)} facture(s) affichée(s), total facturé{' '}
                {formatCurrency(stats?.total_billed || 0, currency)}, taux de recouvrement{' '}
                {stats?.collection_rate ?? 0} pour cent.
            </p>

            <DashboardFilterBar
                values={values}
                statuses={statuses}
                organizations={organizations}
                showOrganizationFilter={showOrganizationFilter}
                hasActiveFilters={hasActiveFilters}
                onFieldChange={setField}
                onReset={resetFilters}
            />

            <section aria-label="Indicateurs financiers clés">
                <h2 className="sr-only">Indicateurs clés</h2>
                <div className="metrics-grid">
                    {kpis.map((kpi) => (
                        <MetricCard
                            key={kpi.key}
                            label={kpi.label}
                            value={kpi.value}
                            currency={kpi.currency}
                            icon={kpi.icon}
                            subtext={kpi.subtext}
                            highlightBlue={kpi.highlightBlue}
                            valueColor={kpi.valueColor}
                        />
                    ))}
                </div>
            </section>

            <div className="dashboard-charts-grid">
                <RevenueTrendChart
                    series={stats?.monthly_series || []}
                    period={values.period}
                    currency={currency}
                />
                <StatusDonutChart
                    breakdown={stats?.status_breakdown || []}
                    collectionRate={stats?.collection_rate ?? 0}
                    selectedStatus={values.status}
                    onSelectStatus={handleToggleStatus}
                    currency={currency}
                />
            </div>

            <TopEntitiesChart
                title={topEntitiesTitle}
                subtitle={topEntitiesSubtitle}
                entities={stats?.top_entities || []}
                selectedEntityId={enableEntityFilter ? values.organization_id : null}
                onSelectEntity={enableEntityFilter ? handleToggleOrganization : null}
                currency={currency}
            />

            <section className="data-card" aria-label="Dernières factures émises">
                <div className="data-card-header">
                    <div className="data-card-title-group">
                        <h2 className="data-card-title">Dernières Factures Émises</h2>
                        <p className="data-card-subtitle">{recentInvoicesSubtitle}</p>
                    </div>
                    <Link href={recentInvoicesAllUrl} className="dashboard-recent-link">
                        {recentInvoicesAllLabel}
                    </Link>
                </div>

                <div className="data-table-wrapper">
                    <table className="data-table" aria-label="Liste des dernières factures filtrées">
                        <thead>
                            <tr>
                                <th scope="col">Numéro</th>
                                <th scope="col">{recentInvoicesPartyLabel}</th>
                                <th scope="col">Date d&apos;Émission</th>
                                <th scope="col">Échéance</th>
                                <th scope="col">Montant Total</th>
                                <th scope="col">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentInvoices.length > 0 ? (
                                recentInvoices.map((invoice) => (
                                    <tr key={invoice.id || invoice.invoice_number}>
                                        <td>
                                            {recentInvoicesLinkInvoices && invoice.id ? (
                                                <Link
                                                    href={`/portal/invoices/${invoice.id}`}
                                                    className="link-invoice-num"
                                                    title={`Consulter la facture ${invoice.invoice_number}`}
                                                >
                                                    {invoice.invoice_number}
                                                </Link>
                                            ) : (
                                                <span className="table-code">{invoice.invoice_number}</span>
                                            )}
                                        </td>
                                        <td>
                                            <strong>
                                                {recentInvoicesLinkInvoices
                                                    ? invoice.client_name || '-'
                                                    : invoice.organization_name || 'Entreprise N/A'}
                                            </strong>
                                        </td>
                                        <td>{formatDate(invoice.issue_date)}</td>
                                        <td>{formatDate(invoice.due_date)}</td>
                                        <td>
                                            <span className="table-amount">{formatCurrency(invoice.total)}</span>
                                            <span className="table-currency">{invoice.currency || currency}</span>
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
                                                <FileText size={32} aria-hidden="true" />
                                            </span>
                                            <p className="empty-state-title">Aucune facture disponible</p>
                                            <p className="empty-state-text">
                                                Aucune facture ne correspond aux critères sélectionnés.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </section>
        </>
    );
}
