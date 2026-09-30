import AdminLayout from '../../Layouts/AdminLayout';
import MetricCard from '../../Components/MetricCard';
import StatusBadge from '../../Components/StatusBadge';
import { formatCurrency, formatNumber } from '../../Utils/formatters';

export default function Dashboard({ auth, stats }) {
    return (
        <AdminLayout auth={auth} title="Tableau de bord - Administration">
            {/* Bannière d'accueil claire à liseré bleu */}
            <div className="admin-banner">
                <span className="admin-banner-tag">
                    Session Administrateur Active
                </span>
                <h2 className="admin-banner-heading">
                    Bienvenue, {auth?.user?.name} ! <span role="img" aria-hidden="true">👋</span>
                </h2>
                <p className="admin-banner-text">
                    Supervision globale de la facturation B2B. Données consolidées en temps réel sur PostgreSQL 16 avec calculs à précision arbitraire et mise en cache haute performance.
                </p>
            </div>

            {/* Grille des cartes d'indicateurs financiers réels */}
            <div className="metrics-grid">
                <MetricCard
                    label="Chiffre d'Affaires Global"
                    value={formatCurrency(stats?.total_billed)}
                    currency="XOF"
                    icon="💰"
                    subtext="Totalité des factures émises"
                    highlightBlue={true}
                />

                <MetricCard
                    label="Recouvrement Encaissé"
                    value={formatCurrency(stats?.total_paid)}
                    currency="XOF"
                    icon="✅"
                    subtext="Statut 'Payée' acquitté"
                    valueColor="var(--green-text)"
                />

                <MetricCard
                    label="Montant en Retard"
                    value={formatCurrency(stats?.total_overdue)}
                    currency="XOF"
                    icon="⏳"
                    subtext="Factures échues non soldées"
                    valueColor="var(--rose-text)"
                />

                <MetricCard
                    label="Volume d'Activité"
                    value={formatNumber(stats?.total_invoices_count)}
                    icon="📊"
                    subtext={`Réparties sur ${formatNumber(stats?.total_organizations_count)} entreprises clientes`}
                    highlightBlue={true}
                />
            </div>

            {/* Tableau Sobre des Dernières Factures Émises */}
            <div className="data-card">
                <div className="data-card-header">
                    <div className="data-card-title-group">
                        <h3 className="data-card-title">
                            Dernières Factures Émises
                        </h3>
                        <p className="data-card-subtitle">
                            Flux récent extrait de la base PostgreSQL
                        </p>
                    </div>
                </div>

                <div className="data-table-wrapper">
                    <table className="data-table">
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
                            {stats?.recent_invoices && stats.recent_invoices.length > 0 ? (
                                stats.recent_invoices.map((invoice) => (
                                    <tr key={invoice.id || invoice.invoice_number}>
                                        <td>
                                            <span className="table-code">
                                                {invoice.invoice_number}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{invoice.organization_name || 'Entreprise N/A'}</strong>
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
                                    <td
                                        colSpan="6"
                                        style={{
                                            textAlign: 'center',
                                            padding: '32px',
                                            color: 'var(--text-muted)',
                                        }}
                                    >
                                        Aucune facture enregistrée pour le moment.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
