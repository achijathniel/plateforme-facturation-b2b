import { formatCurrency } from '../../Utils/formatters';

/**
 * Bloc récapitulatif financier (HT, TVA 18%, TTC) et boutons de sauvegarde / émission.
 *
 * @param {{
 *     financials: { subtotal: number, taxAmount: number, total: number },
 *     processing: boolean,
 *     action: string,
 *     isEdit: boolean,
 *     onSendSubmit: () => void
 * }} props
 */
export default function InvoiceSummaryCard({
    financials,
    processing,
    action,
    isEdit,
    onSendSubmit,
}) {
    return (
        <div className="invoice-financial-summary-card">
            <h3 className="summary-title">Récapitulatif Financier</h3>

            <div className="summary-row">
                <span>Sous-total Hors Taxes :</span>
                <span className="summary-value">
                    {formatCurrency(financials.subtotal, 'XOF')}
                </span>
            </div>

            <div className="summary-row">
                <span>TVA applicable (18%) :</span>
                <span className="summary-value">
                    {formatCurrency(financials.taxAmount, 'XOF')}
                </span>
            </div>

            <div className="summary-row total-highlight" aria-live="polite" aria-atomic="true">
                <strong>Total Net TTC :</strong>
                <span className="total-price">
                    {formatCurrency(financials.total, 'XOF')}
                </span>
            </div>

            <div className="summary-actions-group">
                <button
                    type="submit"
                    disabled={processing}
                    className="btn-save-draft"
                >
                    <span aria-hidden="true">💾</span>
                    <span>
                        {processing && action === 'draft'
                            ? 'Enregistrement...'
                            : isEdit
                                ? 'Enregistrer les modifications'
                                : 'Enregistrer en brouillon'}
                    </span>
                </button>

                <button
                    type="button"
                    onClick={onSendSubmit}
                    disabled={processing}
                    className="btn-emit-send"
                >
                    <span aria-hidden="true">🚀</span>
                    <span>
                        {processing && action === 'send'
                            ? 'Transmission en cours...'
                            : isEdit
                                ? 'Enregistrer & Envoyer au client'
                                : 'Émettre et envoyer au client'}
                    </span>
                </button>
            </div>
        </div>
    );
}
