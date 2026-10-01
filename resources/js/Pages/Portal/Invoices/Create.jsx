import { useMemo } from 'react';
import { useForm, Link } from '@inertiajs/react';
import Swal from 'sweetalert2';
import PortalLayout from '../../../Layouts/PortalLayout';
import { formatCurrency } from '../../../Utils/formatters';

/**
 * Page de création d'une nouvelle facture dans l'espace portail comptable.
 * Permet au comptable de renseigner les informations de l'entreprise cliente,
 * les modalités d'échéance, les lignes de facturation détaillées,
 * et de choisir d'enregistrer en brouillon ou d'émettre et envoyer au client.
 *
 * @param {{
 *     auth: { user: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     defaultDueDate: string
 * }} props
 */
const createEmptyItem = () => ({
    id: typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `item-${Date.now()}-${Math.random().toString(36).substring(2, 7)}`,
    description: '',
    quantity: 1,
    unit_price: '',
});

export default function Create({ auth, organization, defaultDueDate }) {
    const { data, setData, post, processing, errors, transform } = useForm({
        client_name: '',
        client_email: '',
        client_address: '',
        client_tax_number: '',
        client_phone: '',
        due_date: defaultDueDate || '',
        notes: '',
        action: 'draft',
        items: [createEmptyItem()],
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

    // Soumission du formulaire
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
        post('/portal/invoices');
    };

    const handleSubmit = (actionType) => {
        if (actionType === 'send') {
            Swal.fire({
                title: 'Émettre et envoyer la facture au client ?',
                text: `La facture sera immédiatement transmise par notification email à ${data.client_email || "l'adresse du client"}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Oui, émettre et envoyer',
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
        <PortalLayout auth={auth} organization={organization} title="Créer une nouvelle facture">
            <div className="invoice-create-container">
                {/* En-tête de la page */}
                <div className="page-header-row">
                    <div>
                        <div className="breadcrumb-nav">
                            <Link href="/portal-dashboard" className="breadcrumb-link">
                                ← Retour au tableau de bord
                            </Link>
                        </div>
                        <h2 className="portal-page-title">Créer une nouvelle facture</h2>
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
                                    Renseignez les coordonnées de l'entreprise à laquelle cette facture est adressée.
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

                    {/* Section 2 : Modalités de règlement & Échéance */}
                    <div className="form-section-card" style={{ marginTop: '24px' }}>
                        <h3 className="section-title">2. Modalités de règlement & Échéance</h3>
                        <div className="form-row">
                            <div className="form-group">
                                <label htmlFor="due_date" className="form-label required">
                                    Date d'échéance de paiement
                                </label>
                                <input
                                    id="due_date"
                                    name="due_date"
                                    type="date"
                                    className={`form-input ${errors.due_date ? 'input-error' : ''}`}
                                    value={data.due_date}
                                    onChange={(e) => setData('due_date', e.target.value)}
                                    required
                                />
                                {errors.due_date && (
                                    <span className="field-error-text" role="alert">
                                        {errors.due_date}
                                    </span>
                                )}
                            </div>

                            <div className="form-group">
                                <label htmlFor="notes" className="form-label">
                                    Notes et conditions de paiement (facultatif)
                                </label>
                                <input
                                    id="notes"
                                    name="notes"
                                    type="text"
                                    placeholder="Ex: Règlement par virement sous 30 jours..."
                                    className={`form-input ${errors.notes ? 'input-error' : ''}`}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                                {errors.notes && (
                                    <span className="field-error-text" role="alert">
                                        {errors.notes}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Section 3 : Lignes de facturation */}
                    <div className="form-section-card" style={{ marginTop: '24px' }}>
                        <div className="section-header-flex">
                            <div>
                                <h3 className="section-title">3. Lignes de facturation (Articles & Prestations)</h3>
                                <p className="section-description">
                                    Détaillez les produits, prestations ou services faisant l'objet de cette facture.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={addItem}
                                className="btn-add-line"
                                aria-label="Ajouter une nouvelle ligne d'article"
                            >
                                <span aria-hidden="true">➕</span>
                                <span>Ajouter une ligne</span>
                            </button>
                        </div>

                        {errors.items && typeof errors.items === 'string' && (
                            <div className="form-alert error" role="alert">
                                {errors.items}
                            </div>
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

                    {/* Synthèse financière et Actions */}
                    <div className="invoice-bottom-grid" style={{ marginTop: '24px' }}>
                        <div className="form-section-card">
                            <h4 className="section-title">ℹ️ Cycle de vie de la facture</h4>
                            <p className="section-description" style={{ marginTop: '8px', lineHeight: '1.6' }}>
                                • <strong>Enregistrer en brouillon :</strong> La facture est enregistrée avec les coordonnées du client et les calculs exacts. <em>Aucun email n'est expédié</em>. Vous pourrez la consulter et l'émettre à tout moment.<br />
                                • <strong>Émettre et envoyer au client :</strong> La facture est validée et l'email de notification est expédié immédiatement à l'adresse du client avec le récapitulatif complet.
                            </p>
                        </div>

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
                                            : 'Enregistrer en brouillon'}
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
                                            : 'Émettre et envoyer au client'}
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
