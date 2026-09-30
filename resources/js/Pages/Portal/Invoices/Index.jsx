import { Link } from '@inertiajs/react';
import PortalLayout from '../../../Layouts/PortalLayout';
import StatusBadge from '../../../Components/StatusBadge';
import Pagination from '../../../Components/Pagination';
import { formatCurrency } from '../../../Utils/formatters';

/**
 * Vue de consultation de l'ensemble des factures de l'entreprise cliente.
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
 *     }
 * }} props
 */
const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
});

export default function Index({ auth, organization, invoices }) {
    const invoiceList = invoices?.data || [];

    return (
        <PortalLayout auth={auth} organization={organization} title="Mes Factures">
            <div className="admin-page-header">
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px' }}>
                    <div>
                        <h2 className="portal-page-title">Mes Factures</h2>
                        <p className="portal-page-subtitle">
                            Historique et suivi des factures émises pour <strong>{organization?.name}</strong>
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

            {/* Tableau des factures */}
            <div className="data-card">
                <div className="data-card-header">
                    <div className="data-card-title-group">
                        <h3 className="data-card-title">Factures de l'organisation</h3>
                        <p className="data-card-subtitle">
                            {invoices?.total || 0} facture(s) enregistrée(s)
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
                            </tr>
                        </thead>
                        <tbody>
                            {invoiceList.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="table-empty-cell">
                                        <div className="table-empty-state">
                                            <span className="empty-state-icon" aria-hidden="true">📑</span>
                                            <p className="empty-state-title">Aucune facture enregistrée</p>
                                            <p className="empty-state-text">
                                                Vous n'avez pas encore créé de facture pour cette organisation.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                invoiceList.map((invoice) => (
                                    <tr key={invoice.id}>
                                        <td>
                                            <span className="table-code">{invoice.invoice_number}</span>
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
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
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
