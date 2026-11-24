import { formatCurrency } from '../../Utils/formatters';

/**
 * Tableau dynamique des lignes d'articles et prestations d'une facture.
 *
 * @param {{
 *     items: Array<{ id?: string|number, description: string, quantity: string|number, unit_price: string|number }>,
 *     errors: Record<string, string>,
 *     onItemChange: (index: number, field: string, value: string) => void,
 *     onAddItem: () => void,
 *     onRemoveItem: (index: number) => void
 * }} props
 */
export default function InvoiceItemsTable({
    items,
    errors,
    onItemChange,
    onAddItem,
    onRemoveItem,
}) {
    return (
        <div className="form-section-card" style={{ marginTop: '24px' }}>
            <div className="section-header-flex">
                <div>
                    <h3 className="section-title">2. Lignes de facturation (Articles &amp; Prestations)</h3>
                    <p className="section-description">
                        Détaillez les produits, prestations ou services faisant l'objet de cette facture.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={onAddItem}
                    className="btn-add-line"
                    aria-label="Ajouter une nouvelle ligne d'article"
                >
                    <span aria-hidden="true">➕</span>
                    <span>Ajouter une ligne</span>
                </button>
            </div>

            {errors.items && typeof errors.items === 'string' && (
                <div className="form-alert error" role="alert" style={{ marginBottom: '16px' }}>
                    {errors.items}
                </div>
            )}

            <div className="data-table-wrapper" style={{ overflowX: 'auto' }}>
                <table className="invoice-items-form-table" aria-label="Lignes d'articles de la facture">
                    <thead>
                        <tr>
                            <th scope="col" style={{ width: '45%' }}>Description / Prestation *</th>
                            <th scope="col" style={{ width: '15%' }}>Quantité *</th>
                            <th scope="col" style={{ width: '20%' }}>Prix unitaire HT (FCFA) *</th>
                            <th scope="col" style={{ width: '15%' }}>Total HT</th>
                            <th scope="col" style={{ width: '5%', textAlign: 'center' }}>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.map((item, index) => {
                            const qty = parseFloat(item.quantity) || 0;
                            const unitPrice = parseFloat(item.unit_price) || 0;
                            const lineTotal = Math.round(qty * unitPrice * 100) / 100;
                            const descError = errors[`items.${index}.description`];
                            const qtyError = errors[`items.${index}.quantity`];
                            const priceError = errors[`items.${index}.unit_price`];
                            const descErrorId = `item-desc-error-${index}`;
                            const qtyErrorId = `item-qty-error-${index}`;
                            const priceErrorId = `item-price-error-${index}`;

                            return (
                                <tr key={item.id || index}>
                                    <td>
                                        <input
                                            type="text"
                                            name={`items[${index}].description`}
                                            value={item.description}
                                            onChange={(e) => onItemChange(index, 'description', e.target.value)}
                                            placeholder="Ex: Prestation de maintenance informatique"
                                            className={`form-input table-input ${descError ? 'input-error' : ''}`}
                                            aria-label={`Description ligne ${index + 1}`}
                                            aria-invalid={!!descError}
                                            aria-describedby={descError ? descErrorId : undefined}
                                        />
                                        {descError && (
                                            <p id={descErrorId} className="field-error-text">
                                                {descError}
                                            </p>
                                        )}
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            name={`items[${index}].quantity`}
                                            min="0.01"
                                            step="any"
                                            value={item.quantity}
                                            onChange={(e) => onItemChange(index, 'quantity', e.target.value)}
                                            className={`form-input table-input ${qtyError ? 'input-error' : ''}`}
                                            aria-label={`Quantité ligne ${index + 1}`}
                                            aria-invalid={!!qtyError}
                                            aria-describedby={qtyError ? qtyErrorId : undefined}
                                        />
                                        {qtyError && (
                                            <p id={qtyErrorId} className="field-error-text">
                                                {qtyError}
                                            </p>
                                        )}
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            name={`items[${index}].unit_price`}
                                            min="0"
                                            step="any"
                                            placeholder="50000"
                                            value={item.unit_price}
                                            onChange={(e) => onItemChange(index, 'unit_price', e.target.value)}
                                            className={`form-input table-input ${priceError ? 'input-error' : ''}`}
                                            aria-label={`Prix unitaire ligne ${index + 1}`}
                                            aria-invalid={!!priceError}
                                            aria-describedby={priceError ? priceErrorId : undefined}
                                        />
                                        {priceError && (
                                            <p id={priceErrorId} className="field-error-text">
                                                {priceError}
                                            </p>
                                        )}
                                    </td>

                                    <td className="cell-line-total">
                                        <strong>{formatCurrency(lineTotal, 'XOF')}</strong>
                                    </td>

                                    <td className="cell-action">
                                        <button
                                            type="button"
                                            onClick={() => onRemoveItem(index)}
                                            disabled={items.length <= 1}
                                            className="btn-delete-row"
                                            title={
                                                items.length <= 1
                                                    ? 'Une facture doit comporter au moins un article'
                                                    : 'Supprimer cette ligne'
                                            }
                                            aria-label={`Supprimer la ligne ${index + 1}`}
                                        >
                                            <span aria-hidden="true">🗑️</span>
                                        </button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
