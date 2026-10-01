import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import PortalLayout from '../../../Layouts/PortalLayout';
import StatusBadge from '../../../Components/StatusBadge';
import { formatCurrency } from '../../../Utils/formatters';

/**
 * Vue détaillée, élégante et imprimable d'une facture.
 * Permet au comptable de consulter les informations complètes,
 * d'imprimer la facture au format A4 / PDF,
 * et d'accéder aux actions de modification ou d'envoi client.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     invoice: {
 *         id: number,
 *         invoice_number: string,
 *         status: string,
 *         issue_date: string|null,
 *         due_date: string|null,
 *         subtotal: string,
 *         tax_amount: string,
 *         total: string,
 *         currency: string,
 *         notes: string|null,
 *         can_edit: boolean,
 *         can_send: boolean,
 *         items: Array<{
 *             id: number,
 *             description: string,
 *             quantity: number,
 *             unit_price: string,
 *             total: string
 *         }>,
 *         payments: Array<{
 *             id: number,
 *             payment_method: string,
 *             status: string,
 *             amount: string,
 *             paid_at: string|null
 *         }>
 *     }
 * }} props
 */
const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

export default function Show({ auth, organization, invoice }) {
    const [isSending, setIsSending] = useState(false);

    const handlePrint = () => {
        window.print();
    };

    const handleSendToClient = () => {
        Swal.fire({
            title: 'Transmettre la facture au client ?',
            text: `La facture ${invoice.invoice_number} sera validée et transmise par email à l'adresse de contact du client.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui, envoyer par email',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                setIsSending(true);
                router.post(
                    `/portal/invoices/${invoice.id}/send`,
                    {},
                    {
                        onFinish: () => setIsSending(false),
                    }
                );
            }
        });
    };

    return (
        <PortalLayout
            auth={auth}
            organization={organization}
            title={`Facture ${invoice.invoice_number}`}
        >
            <div className="invoice-view-container">
                {/* Barre d'actions supérieure (masquée lors de l'impression) */}
                <div className="invoice-actions-bar">
                    <div className="breadcrumb-nav">
                        <Link href="/portal/invoices" className="breadcrumb-link">
                            ← Retour aux factures
                        </Link>
                    </div>

                    <div className="invoice-actions-right">
                        <button
                            type="button"
                            onClick={handlePrint}
                            className="btn-print-invoice"
                            title="Imprimer ou enregistrer au format PDF"
                        >
                            <span aria-hidden="true">🖨️</span>
                            <span>Imprimer la facture</span>
                        </button>

                        {invoice.can_edit && (
                            <Link
                                href={`/portal/invoices/${invoice.id}/edit`}
                                className="btn-edit-invoice"
                                title="Modifier cette facture"
                            >
                                <span aria-hidden="true">✏️</span>
                                <span>Modifier</span>
                            </Link>
                        )}

                        {invoice.can_send && (
                            <button
                                type="button"
                                onClick={handleSendToClient}
                                disabled={isSending}
                                className="btn-send-invoice"
                                title="Émettre et transmettre la facture au client"
                            >
                                <span aria-hidden="true">✉️</span>
                                <span>{isSending ? 'Envoi en cours...' : 'Envoyer au client'}</span>
                            </button>
                        )}
                    </div>
                </div>

                {/* Document Facture Paper-Style (conservé impeccablement à l'impression) */}
                <article className="invoice-document-card" aria-label={`Détails de la facture ${invoice.invoice_number}`}>
                    {/* En-tête du document */}
                    <header className="invoice-doc-header">
                        <div className="invoice-doc-brand">
                            <h2>{organization?.name || 'Entreprise B2B'}</h2>
                            <p>Plateforme de Facturation & Comptabilité B2B</p>
                        </div>

                        <div className="invoice-doc-meta">
                            <div className="invoice-doc-number">{invoice.invoice_number}</div>
                            <div>
                                <StatusBadge status={invoice.status} />
                            </div>
                        </div>
                    </header>

                    {/* Parties Émetteur & Destinataire B2B */}
                    <section className="invoice-doc-parties-grid" aria-label="Émetteur et Destinataire">
                        <div className="party-block">
                            <span className="party-type-label">Émetteur / Fournisseur</span>
                            <h3 className="party-name">{organization?.name || 'Entreprise B2B'}</h3>
                            <p className="party-detail">Plateforme de Facturation & Comptabilité B2B</p>
                        </div>

                        <div className="party-block">
                            <span className="party-type-label">Destinataire / Facturé à</span>
                            <h3 className="party-name">{invoice.client_name || 'Client Entreprise'}</h3>
                            {invoice.client_email && (
                                <p className="party-detail">
                                    <strong>Email :</strong> {invoice.client_email}
                                </p>
                            )}
                            {invoice.client_tax_number && (
                                <p className="party-detail">
                                    <strong>NIF / N° Fiscal :</strong> {invoice.client_tax_number}
                                </p>
                            )}
                            {invoice.client_phone && (
                                <p className="party-detail">
                                    <strong>Tél :</strong> {invoice.client_phone}
                                </p>
                            )}
                            {invoice.client_address && (
                                <p className="party-detail">
                                    <strong>Adresse :</strong> {invoice.client_address}
                                </p>
                            )}
                        </div>
                    </section>

                    {/* Grille des dates & métadonnées */}
                    <section className="invoice-doc-dates-grid" aria-label="Dates et informations générales">
                        <div className="doc-date-item">
                            <span className="doc-date-label">Date d'émission</span>
                            <span className="doc-date-val">
                                {invoice.issue_date
                                    ? dateFormatter.format(new Date(invoice.issue_date))
                                    : 'Non émise (Brouillon)'}
                            </span>
                        </div>

                        <div className="doc-date-item">
                            <span className="doc-date-label">Date d'échéance</span>
                            <span className="doc-date-val">
                                {invoice.due_date
                                    ? dateFormatter.format(new Date(invoice.due_date))
                                    : 'Non définie'}
                            </span>
                        </div>

                        <div className="doc-date-item">
                            <span className="doc-date-label">Devise de règlement</span>
                            <span className="doc-date-val">
                                {invoice.currency || 'XOF'} (Franc CFA)
                            </span>
                        </div>
                    </section>

                    {/* Tableau des lignes d'articles */}
                    <section className="invoice-doc-table-wrapper" aria-label="Lignes de facturation">
                        <table className="invoice-doc-table">
                            <thead>
                                <tr>
                                    <th scope="col" style={{ width: '50px' }}>#</th>
                                    <th scope="col">Description / Prestation</th>
                                    <th scope="col" className="text-right" style={{ width: '120px' }}>Quantité</th>
                                    <th scope="col" className="text-right" style={{ width: '160px' }}>Prix Unitaire HT</th>
                                    <th scope="col" className="text-right" style={{ width: '180px' }}>Total HT</th>
                                </tr>
                            </thead>
                            <tbody>
                                {invoice.items && invoice.items.length > 0 ? (
                                    invoice.items.map((item, index) => (
                                        <tr key={item.id || index}>
                                            <td style={{ color: 'var(--text-muted)' }}>{index + 1}</td>
                                            <td>
                                                <strong>{item.description}</strong>
                                            </td>
                                            <td className="text-right">{item.quantity}</td>
                                            <td className="text-right">
                                                {formatCurrency(item.unit_price, invoice.currency || 'XOF')}
                                            </td>
                                            <td className="text-right" style={{ fontWeight: 600 }}>
                                                {formatCurrency(item.total, invoice.currency || 'XOF')}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={5} style={{ textAlign: 'center', padding: '24px', color: 'var(--text-muted)' }}>
                                            Aucune ligne d'article dans cette facture.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </section>

                    {/* Bloc récapitulatif des totaux */}
                    <section className="invoice-doc-totals-section" aria-label="Totaux financiers">
                        <div className="invoice-doc-totals-box">
                            <div className="invoice-totals-line">
                                <span>Sous-total HT</span>
                                <strong>{formatCurrency(invoice.subtotal, invoice.currency || 'XOF')}</strong>
                            </div>
                            <div className="invoice-totals-line">
                                <span>TVA (18%)</span>
                                <strong>{formatCurrency(invoice.tax_amount, invoice.currency || 'XOF')}</strong>
                            </div>
                            <div className="invoice-totals-line highlight">
                                <span>Net à payer TTC</span>
                                <span>{formatCurrency(invoice.total, invoice.currency || 'XOF')}</span>
                            </div>
                        </div>
                    </section>

                    {/* Notes & Mentions Légales */}
                    <footer className="invoice-doc-notes">
                        <h4>Notes & Mentions</h4>
                        <p>
                            {invoice.notes && invoice.notes.trim() !== ''
                                ? invoice.notes
                                : 'Paiement à réception sous 30 jours nets. En cas de retard, une pénalité égale à 3 fois le taux d’intérêt légal sera exigible de plein droit.'}
                        </p>
                    </footer>
                </article>
            </div>
        </PortalLayout>
    );
}
