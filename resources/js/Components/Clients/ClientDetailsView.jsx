import { Link } from '@inertiajs/react';
import {
    Building2,
    CheckCircle2,
    Clock,
    Eye,
    FileText,
    Wallet,
} from 'lucide-react';
import MetricCard from '../MetricCard';
import StatusBadge from '../StatusBadge';
import { formatCurrency, formatDate } from '../../Utils/formatters';

/**
 * Vue 360° détaillée d'un client B2B : KPIs, coordonnées fiscales et historique des factures.
 *
 * @param {{
 *     client: {
 *         id: number,
 *         name: string,
 *         tax_number?: string|null,
 *         email?: string|null,
 *         phone?: string|null,
 *         address?: string|null,
 *         notes?: string|null,
 *         created_at?: string,
 *         invoices_count?: number,
 *         total_billed?: number|string|null,
 *         total_paid?: number|string|null,
 *         total_pending?: number|string|null,
 *         invoices?: Array<{
 *             id: number,
 *             invoice_number: string,
 *             issue_date?: string|null,
 *             due_date?: string|null,
 *             total: number|string,
 *             currency?: string,
 *             status: string
 *         }>
 *     }
 * }} props
 */
export default function ClientDetailsView({ client }) {
    const invoices = client.invoices || [];
    const totalCount = client.invoices_count ?? invoices.length;
    const historyTitle = totalCount > invoices.length
        ? `Dernières factures (${invoices.length} sur ${totalCount})`
        : `Historique des factures (${invoices.length})`;

    return (
        <>
            {/* Cartes d'indicateurs clés */}
            <div className="metrics-grid portal-client-section-spaced">
                <MetricCard
                    label="Total Facturé"
                    value={formatCurrency(client.total_billed || 0)}
                    currency="XOF"
                    icon={<Wallet size={18} aria-hidden="true" />}
                    subtext="Montant cumulé facturé"
                />
                <MetricCard
                    label="Total Encaissé"
                    value={formatCurrency(client.total_paid || 0)}
                    currency="XOF"
                    icon={<CheckCircle2 size={18} aria-hidden="true" />}
                    subtext="Factures réglées"
                    valueColor="var(--green-text)"
                />
                <MetricCard
                    label="En attente / Impayé"
                    value={formatCurrency(client.total_pending || 0)}
                    currency="XOF"
                    icon={<Clock size={18} aria-hidden="true" />}
                    subtext="Envoyées ou en retard"
                    valueColor="var(--amber-text)"
                />
                <MetricCard
                    label="Factures"
                    value={totalCount}
                    icon={<FileText size={18} aria-hidden="true" />}
                    subtext="Documents enregistrés"
                />
            </div>

            {/* Coordonnées & Notes */}
            <div className="org-info-card portal-client-section-spaced">
                <div className="org-info-card-header">
                    <h2 className="org-info-card-title">
                        <Building2 size={18} aria-hidden="true" />
                        <span>Coordonnées &amp; Informations fiscales</span>
                    </h2>
                </div>
                <dl className="org-overview-grid" style={{ margin: 0 }}>
                    <div className="org-detail-item">
                        <dt className="org-detail-label">Raison sociale</dt>
                        <dd className="org-detail-value" style={{ margin: 0 }}>{client.name}</dd>
                    </div>
                    <div className="org-detail-item">
                        <dt className="org-detail-label">N° Contribuable / NIF / RCCM</dt>
                        <dd className="org-detail-value" style={{ margin: 0 }}>
                            {client.tax_number || 'Non renseigné'}
                        </dd>
                    </div>
                    <div className="org-detail-item">
                        <dt className="org-detail-label">Email de facturation</dt>
                        <dd className="org-detail-value" style={{ margin: 0 }}>
                            {client.email ? (
                                <a href={`mailto:${client.email}`}>{client.email}</a>
                            ) : (
                                'Non renseigné'
                            )}
                        </dd>
                    </div>
                    <div className="org-detail-item">
                        <dt className="org-detail-label">Téléphone</dt>
                        <dd className="org-detail-value" style={{ margin: 0 }}>
                            {client.phone || 'Non renseigné'}
                        </dd>
                    </div>
                    <div className="org-detail-item">
                        <dt className="org-detail-label">Adresse</dt>
                        <dd className="org-detail-value" style={{ margin: 0 }}>
                            {client.address || 'Non renseignée'}
                        </dd>
                    </div>
                    <div className="org-detail-item">
                        <dt className="org-detail-label">Date d'ajout</dt>
                        <dd className="org-detail-value" style={{ margin: 0 }}>
                            {client.created_at ? formatDate(client.created_at) : 'N/A'}
                        </dd>
                    </div>
                </dl>
                {client.notes && (
                    <div className="portal-client-notes-block">
                        <span className="org-detail-label">Notes internes</span>
                        <p className="portal-client-notes-text">{client.notes}</p>
                    </div>
                )}
            </div>

            {/* Historique des factures du client */}
            <div className="data-card">
                <div className="org-info-card-header portal-client-card-header-padded">
                    <h2 className="org-info-card-title">
                        <FileText size={18} aria-hidden="true" />
                        <span>{historyTitle}</span>
                    </h2>
                </div>
                <div className="data-table-wrapper">
                    <table className="data-table" aria-label={`Historique des factures de ${client.name}`}>
                        <thead>
                            <tr>
                                <th scope="col">Numéro</th>
                                <th scope="col">Date d'émission</th>
                                <th scope="col">Échéance</th>
                                <th scope="col">Montant TTC</th>
                                <th scope="col">Statut</th>
                                <th scope="col" style={{ textAlign: 'right' }}>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {invoices.length > 0 ? (
                                invoices.map((inv) => (
                                    <tr key={inv.id}>
                                        <td>
                                            <Link
                                                href={`/portal/invoices/${inv.id}`}
                                                className="link-invoice-num"
                                            >
                                                {inv.invoice_number}
                                            </Link>
                                        </td>
                                        <td>{inv.issue_date ? formatDate(inv.issue_date) : '-'}</td>
                                        <td>{inv.due_date ? formatDate(inv.due_date) : '-'}</td>
                                        <td>
                                            <span className="table-amount">
                                                {formatCurrency(inv.total, inv.currency || 'XOF')}
                                            </span>
                                        </td>
                                        <td>
                                            <StatusBadge status={inv.status} />
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            <Link
                                                href={`/portal/invoices/${inv.id}`}
                                                className="btn-toggle-status"
                                                aria-label={`Consulter la facture ${inv.invoice_number}`}
                                            >
                                                <Eye size={14} aria-hidden="true" />
                                                <span>Consulter</span>
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={6} className="table-empty-cell portal-client-empty-cell">
                                        Aucune facture enregistrée pour ce client pour le moment.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
