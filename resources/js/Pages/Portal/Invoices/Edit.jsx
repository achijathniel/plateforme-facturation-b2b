import { useMemo } from 'react';
import { useForm, Link } from '@inertiajs/react';
import Swal from 'sweetalert2';
import PortalLayout from '../../../Layouts/PortalLayout';
import { formatCurrency } from '../../../Utils/formatters';

/**
 * Page de modification d'une facture existante dans l'espace portail comptable.
 * Permet au comptable d'éditer les coordonnées de l'entreprise cliente,
 * la date d'échéance, les notes et les lignes d'articles,
 * de recalculer en direct les totaux financiers (HT, TVA 18%, TTC),
 * et d'enregistrer les modifications en tant que brouillon ou de les émettre directement vers le client.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     invoice: {
 *         id: number,
 *         invoice_number: string,
 *         client_name?: string,
 *         client_email?: string,
 *         client_address?: string,
 *         client_tax_number?: string,
 *         client_phone?: string,
 *         status: string,
 *         due_date: string|null,
 *         notes: string|null,
 *         items: Array<{
 *             id: number,
 *             description: string,
 *             quantity: number,
 *             unit_price: string
 *         }>
 *     }
 * }} props
 */
const createEmptyItem = () => ({
    id: typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `item-${Date.now()}-${Math.random().toString(36).substring(2, 7)}`,
    description: '',
    quantity: 1,
    unit_price: '',
});

export default function Edit({ auth, organization, invoice }) {
    const initialItems = invoice.items && invoice.items.length > 0
        ? invoice.items.map((it) => ({
            id: it.id || createEmptyItem().id,
            description: it.description || '',
            quantity: it.quantity ?? 1,
            unit_price: it.unit_price ?? '',
        }))
        : [createEmptyItem()];

    const { data, setData, put, processing, errors, transform } = useForm({
        client_name: invoice.client_name || '',
        client_email: invoice.client_email || '',
        client_address: invoice.client_address || '',
        client_tax_number: invoice.client_tax_number || '',
        client_phone: invoice.client_phone || '',
        due_date: invoice.due_date || '',
        notes: invoice.notes || '',
        action: 'draft',
        items: initialItems,
    });

    // Gestion dynamique des lignes de facturation
    const handleItemChange = (index, field, value) => {
        const updatedItems = [...data.items];

        let sanitizedValue = value;
        if (field === 'unit_price' || field === 'quantity') {
            sanitizedValue = typeof value === 'string'
                ? value.replace(/\s+/g, '').replace(',', '.')
                : value;
        }

        updatedItems[index] = {
            ...updatedItems[index],
            [field]: sanitizedValue,
        };
        setData('items', updatedItems);
    };

    const addItem = () => {
        setData('items', [...data.items, createEmptyItem()]);
    };

    const removeItem = (index) => {
        if (data.items.length <= 1) return;
        const updatedItems = data.items.filter((_, i) => i !== index);
        setData('items', updatedItems);
    };

    // Calculs financiers réactifs en temps réel (HT, TVA 18%, TTC)
    const financials = useMemo(() => {
        let subtotal = 0;
        data.items.forEach((item) => {
            const qty = parseFloat(item.quantity) || 0;
            const price = parseFloat(item.unit_price) || 0;
            if (qty > 0 && price > 0) {
                subtotal += qty * price;
            }
        });

        const taxAmount = Math.round(subtotal * 0.18 * 100) / 100;
        const total = Math.round((subtotal + taxAmount) * 100) / 100;

        return { subtotal, taxAmount, total };
    }, [data.items]);

    // Soumission du formulaire selon l'action sélectionnée
    const executeSubmit = (actionType) => {
        transform((currentData) => ({
            ...currentData,
            action: actionType,
            items: currentData.items.map(({ id, ...item }) => ({
                ...item,
                quantity: parseFloat(item.quantity) || 0,
                unit_price: parseFloat(item.unit_price) || 0,
            })),
        }));
        put(`/portal/invoices/${invoice.id}`);
    };

    const handleSubmit = (actionType) => {
        if (actionType === 'send') {
            Swal.fire({
                title: 'Enregistrer et envoyer au client ?',
                text: `La facture ${invoice.invoice_number} sera mise à jour et expédiée immédiatement par email à ${data.client_email || "l'adresse du client"}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Oui, enregistrer et envoyer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    executeSubmit('send');
                }
            });
        } else {
            executeSubmit('draft');
        }
    };

    return (
        <PortalLayout
            auth={auth}
            organization={organization}
            title={`Modifier la facture ${invoice.invoice_number}`}
        >
            <div className="invoice-create-container">
                {/* En-tête de la page */}
                <div className="page-header-row">
                    <div>
                        <div className="breadcrumb-nav">
                            <Link
                                href={`/portal/invoices/${invoice.id}`}
                                className="breadcrumb-link"
                            >
                                ← Retour aux détails de la facture
                            </Link>
                        </div>
                        <h2 className="portal-page-title">
                            Modifier la facture {invoice.invoice_number}
                        </h2>
                        <p className="portal-page-subtitle">
                            Entreprise émettrice : <strong>{organization?.name}</strong>
                        </p>
                    </div>
                </div>

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        handleSubmit('draft');
                    }}
                    className="invoice-form"
                    noValidate
                >
                    {/* Alerte générale si erreurs de validation */}
                    {Object.keys(errors).length > 0 && (
                        <div className="form-alert error" role="alert">
                            <strong>Erreurs de validation :</strong> Veuillez vérifier les informations
                            saisies dans le formulaire ci-dessous.
                        </div>
                    )}

                    {/* Section 1 : Entreprise Cliente (Destinataire B2B) */}
                    <div className="form-section-card">
                        <div className="section-header-flex">
                            <div>
                                <h3 className="section-title">1. Entreprise Cliente (Destinataire B2B)</h3>
                                <p className="section-description">
                                    Modifiez les coordonnées de l'entreprise cliente destinataire de cette facture.
                                </p>
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group">
                                <label htmlFor="client_name" className="form-label required">
                                    Nom / Raison sociale de l'entreprise cliente
                                </label>
                                <input
                                    id="client_name"
                                    name="client_name"
                                    type="text"
                                    placeholder="Ex: Société Ivoirienne de Négoce (SIN)"
                                    className={`form-input ${errors.client_name ? 'input-error' : ''}`}
                                    value={data.client_name}
                                    onChange={(e) => setData('client_name', e.target.value)}
                                    required
                                />
                                {errors.client_name && (
                                    <span className="field-error-text" role="alert">
                                        {errors.client_name}
                                    </span>
                                )}
                            </div>

                            <div className="form-group">
                                <label htmlFor="client_email" className="form-label required">
                                    Email de facturation du client (Destinataire)
                                </label>
                                <input
                                    id="client_email"
                                    name="client_email"
                                    type="email"
                                    placeholder="Ex: facturation@client-negoce.ci"
                                    className={`form-input ${errors.client_email ? 'input-error' : ''}`}
                                    value={data.client_email}
                                    onChange={(e) => setData('client_email', e.target.value)}
                                    required
                                />
                                {errors.client_email && (
                                    <span className="field-error-text" role="alert">
                                        {errors.client_email}
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="form-row" style={{ marginTop: '16px' }}>
                            <div className="form-group">
                                <label htmlFor="client_tax_number" className="form-label">
                                    NIF / N° Registre de Commerce (facultatif)
                                </label>
                                <input
                                    id="client_tax_number"
                                    name="client_tax_number"
                                    type="text"
                                    placeholder="Ex: CI-ABJ-2023-B-12345"
                                    className={`form-input ${errors.client_tax_number ? 'input-error' : ''}`}
                                    value={data.client_tax_number}
                                    onChange={(e) => setData('client_tax_number', e.target.value)}
                                />
                                {errors.client_tax_number && (
                                    <span className="field-error-text" role="alert">
                                        {errors.client_tax_number}
                                    </span>
                                )}
                            </div>

                            <div className="form-group">
                                <label htmlFor="client_phone" className="form-label">
                                    Téléphone de contact (facultatif)
                                </label>
                                <input
                                    id="client_phone"
                                    name="client_phone"
                                    type="tel"
                                    placeholder="Ex: +225 07 00 00 00 00"
                                    className={`form-input ${errors.client_phone ? 'input-error' : ''}`}
                                    value={data.client_phone}
                                    onChange={(e) => setData('client_phone', e.target.value)}
                                />
                                {errors.client_phone && (
                                    <span className="field-error-text" role="alert">
                                        {errors.client_phone}
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="form-group" style={{ marginTop: '16px' }}>
                            <label htmlFor="client_address" className="form-label">
                                Adresse géographique du siège (facultatif)
                            </label>
                            <input
                                id="client_address"
                                name="client_address"
                                type="text"
                                placeholder="Ex: Rue des Jardins, Cocody Deux-Plateaux, Abidjan"
                                className={`form-input ${errors.client_address ? 'input-error' : ''}`}
                                value={data.client_address}
                                onChange={(e) => setData('client_address', e.target.value)}
                            />
                            {errors.client_address && (
                                <span className="field-error-text" role="alert">
                                    {errors.client_address}
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Section 2 : Prestations et Articles */}
                    <div className="form-section-card" style={{ marginTop: '24px' }}>
                        <div className="section-header-flex">
                            <div>
                                <h3 className="section-title">2. Prestations et Articles</h3>
                                <p className="section-description">
                                    Modifiez ou ajustez les désignations, quantités et prix unitaires HT.
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={addItem}
                                className="btn-add-line"
                                aria-label="Ajouter une ligne d'article"
                            >
                                <span aria-hidden="true">➕</span>
                                <span>Ajouter une ligne</span>
                            </button>
                        </div>

                        {errors.items && (
                            <p className="field-error-text" style={{ marginBottom: '12px' }}>
                                {errors.items}
                            </p>
                        )}

                        <div className="data-table-wrapper" style={{ overflowX: 'auto' }}>
                            <table className="invoice-items-form-table">
                                <thead>
                                    <tr>
                                        <th style={{ width: '45%' }}>Description / Prestation *</th>
                                        <th style={{ width: '15%' }}>Quantité *</th>
                                        <th style={{ width: '20%' }}>Prix unitaire HT (FCFA) *</th>
                                        <th style={{ width: '15%' }}>Total HT</th>
                                        <th style={{ width: '5%', textAlign: 'center' }}>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.items.map((item, index) => {
                                        const qty = parseFloat(item.quantity) || 0;
                                        const unitPrice = parseFloat(item.unit_price) || 0;
                                        const lineTotal = Math.round(qty * unitPrice * 100) / 100;
                                        const descError = errors[`items.${index}.description`];
                                        const qtyError = errors[`items.${index}.quantity`];
                                        const priceError = errors[`items.${index}.unit_price`];

                                        return (
                                            <tr key={item.id || index}>
                                                <td>
                                                    <input
                                                        type="text"
                                                        name={`items[${index}].description`}
                                                        value={item.description}
                                                        onChange={(e) =>
                                                            handleItemChange(index, 'description', e.target.value)
                                                        }
                                                        placeholder="Ex: Prestation de maintenance informatique"
                                                        className={`form-input table-input ${descError ? 'input-error' : ''}`}
                                                        aria-label={`Description ligne ${index + 1}`}
                                                    />
                                                    {descError && (
                                                        <p className="field-error-text">{descError}</p>
                                                    )}
                                                </td>

                                                <td>
                                                    <input
                                                        type="number"
                                                        name={`items[${index}].quantity`}
                                                        min="0.01"
                                                        step="any"
                                                        value={item.quantity}
                                                        onChange={(e) =>
                                                            handleItemChange(index, 'quantity', e.target.value)
                                                        }
                                                        className={`form-input table-input ${qtyError ? 'input-error' : ''}`}
                                                        aria-label={`Quantité ligne ${index + 1}`}
                                                    />
                                                    {qtyError && (
                                                        <p className="field-error-text">{qtyError}</p>
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
                                                        onChange={(e) =>
                                                            handleItemChange(index, 'unit_price', e.target.value)
                                                        }
                                                        className={`form-input table-input ${priceError ? 'input-error' : ''}`}
                                                        aria-label={`Prix unitaire ligne ${index + 1}`}
                                                    />
                                                    {priceError && (
                                                        <p className="field-error-text">{priceError}</p>
                                                    )}
                                                </td>

                                                <td className="cell-line-total">
                                                    <strong>{formatCurrency(lineTotal, 'XOF')}</strong>
                                                </td>

                                                <td className="cell-action">
                                                    <button
                                                        type="button"
                                                        onClick={() => removeItem(index)}
                                                        disabled={data.items.length <= 1}
                                                        className="btn-delete-row"
                                                        title={
                                                            data.items.length <= 1
                                                                ? 'Une facture doit comporter au moins un article'
                                                                : 'Supprimer cette ligne'
                                                        }
                                                        aria-label={`Supprimer la ligne ${index + 1}`}
                                                    >
                                                        🗑️
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Section 3 : Modalités & Synthèse financière */}
                    <div className="invoice-bottom-grid" style={{ marginTop: '24px' }}>
                        <div className="form-section-card">
                            <h3 className="section-title">3. Modalités & Règlement</h3>
                            <p className="section-description">
                                Date limite de règlement et mentions particulières.
                            </p>

                            <div className="form-group" style={{ marginTop: '16px' }}>
                                <label htmlFor="due_date" className="form-label required">
                                    Date d'échéance
                                </label>
                                <input
                                    type="date"
                                    id="due_date"
                                    name="due_date"
                                    value={data.due_date}
                                    onChange={(e) => setData('due_date', e.target.value)}
                                    className={`form-input ${errors.due_date ? 'input-error' : ''}`}
                                    required
                                />
                                {errors.due_date && (
                                    <p className="field-error-text">{errors.due_date}</p>
                                )}
                            </div>

                            <div className="form-group" style={{ marginTop: '16px' }}>
                                <label htmlFor="notes" className="form-label">
                                    Notes & Conditions de paiement
                                </label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="4"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="Ex: Règlement par virement bancaire sous 30 jours..."
                                    className={`form-input ${errors.notes ? 'input-error' : ''}`}
                                    style={{ resize: 'vertical' }}
                                />
                                {errors.notes && (
                                    <p className="field-error-text">{errors.notes}</p>
                                )}
                            </div>
                        </div>

                        {/* Bloc Récapitulatif & Actions de validation */}
                        <div className="summary-card">
                            <h3 className="summary-title">Récapitulatif Financier</h3>

                            <div className="summary-line">
                                <span className="summary-label">Sous-total Hors Taxes :</span>
                                <span className="summary-value">
                                    {formatCurrency(financials.subtotal, 'XOF')}
                                </span>
                            </div>

                            <div className="summary-line">
                                <span className="summary-label">TVA applicable (18%) :</span>
                                <span className="summary-value">
                                    {formatCurrency(financials.taxAmount, 'XOF')}
                                </span>
                            </div>

                            <div className="summary-divider" />

                            <div className="summary-line total">
                                <span className="summary-label-total">Total Net TTC :</span>
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
                                    <span>💾</span>
                                    <span>
                                        {processing && data.action === 'draft'
                                            ? 'Enregistrement...'
                                            : 'Enregistrer les modifications'}
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => handleSubmit('send')}
                                    disabled={processing}
                                    className="btn-emit-send"
                                >
                                    <span>🚀</span>
                                    <span>
                                        {processing && data.action === 'send'
                                            ? 'Transmission en cours...'
                                            : 'Enregistrer & Envoyer au client'}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </PortalLayout>
    );
}
