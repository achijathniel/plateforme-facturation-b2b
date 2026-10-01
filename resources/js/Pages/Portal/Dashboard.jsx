import { Link } from '@inertiajs/react';
import PortalLayout from '../../Layouts/PortalLayout';
import MetricCard from '../../Components/MetricCard';
import StatusBadge from '../../Components/StatusBadge';
import { formatCurrency } from '../../Utils/formatters';

/**
 * Tableau de bord du portail entreprise pour le rôle Comptable / Client.
 * Affiche les métriques financières de l'entreprise connectée et la liste de ses factures récentes.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     stats: {
 *         total_billed: string,
 *         total_paid: string,
 *         total_overdue: string,
 *         total_invoices_count: number,
 *         recent_invoices: Array<{
 *             id: number,
 *             invoice_number: string,
 *             total: string,
 *             currency: string,
 *             status: string,
 *             issue_date: string,
 *             due_date: string,
 *             organization_name?: string
 *         }>
 *     }
 * }} props
 */
export default function Dashboard({ auth, organization, stats }) {
    const orgName = organization?.name || auth?.user?.organization?.name || 'Votre entreprise';

    return (
        <PortalLayout auth={auth} organization={organization} title={`Tableau de bord - ${orgName}`}>
            {/* Bannière de Bienvenue */}
            <div className="admin-banner">
                <span className="admin-banner-tag">Espace Entreprise</span>
                <h2 className="admin-banner-heading">
                    Bienvenue sur le portail de {orgName}
                </h2>
                <p className="admin-banner-text">
                    Supervisez en temps réel vos factures, consultez l'historique des règlements et suivez vos échéances financières.
                </p>
            </div>

            {/* Grille des 4 Métriques Financières */}
            <div className="metrics-grid">
                <MetricCard
                    label="Total Facturé"
                    value={formatCurrency(stats?.total_billed)}
                    currency="XOF"
                    icon="💳"
                    subtext="Cumul des factures émises"
                />

                <MetricCard
                    label="Total Réglé"
                    value={formatCurrency(stats?.total_paid)}
                    currency="XOF"
                    icon="✅"
                    subtext="Paiements confirmés"
                    isPositive={true}
                />

                <MetricCard
                    label="Reste à Payer / Échu"
                    value={formatCurrency(stats?.total_overdue)}
                    currency="XOF"
                    icon="⚠️"
                    subtext="Montant en attente de règlement"
                    isOverdue={true}
                />

                <MetricCard
                    label="Factures Enregistrées"
                    value={stats?.total_invoices_count || 0}
                    icon="📄"
                    subtext="Volume global de factures"
                />
            </div>

            {/* Tableau des Dernières Factures */}
            <div className="data-card">
                <div className="data-card-header">
                    <div className="data-card-title-group">
                        <h3 className="data-card-title">Dernières Factures Émises</h3>
                        <p className="data-card-subtitle">
                            Historique des 6 dernières factures établies pour votre entreprise.
                        </p>
                    </div>
                </div>

                <div className="data-table-wrapper">
                    <table className="data-table" aria-label="Liste des dernières factures de l'entreprise">
                        <thead>
                            <tr>
                                <th scope="col">Numéro</th>
                                <th scope="col">Date d'Émission</th>
                                <th scope="col">Échéance</th>
                                <th scope="col">Montant Total</th>
                                <th scope="col">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            {stats?.recent_invoices && stats.recent_invoices.length > 0 ? (
                                stats.recent_invoices.map((invoice) => (
                                    <tr key={invoice.id || invoice.invoice_number}>
                                        <td>
                                            <Link
                                                href={`/portal/invoices/${invoice.id}`}
                                                className="link-invoice-num"
                                                title={`Consulter la facture ${invoice.invoice_number}`}
                                            >
                                                {invoice.invoice_number}
                                            </Link>
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
                                    <td colSpan={5} className="table-empty-cell">
                                        <div className="table-empty-state">
                                            <span className="empty-state-icon" aria-hidden="true">
                                                📄
                                            </span>
                                            <p className="empty-state-title">
                                                Aucune facture disponible
                                            </p>
                                            <p className="empty-state-text">
                                                Aucune facture n'a encore été émise pour votre entreprise.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </PortalLayout>
    );
}
