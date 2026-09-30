import { useMemo } from 'react';
import { useForm, Link } from '@inertiajs/react';
import PortalLayout from '../../../Layouts/PortalLayout';
import { formatCurrency } from '../../../Utils/formatters';

/**
 * Page de création d'une nouvelle facture dans l'espace portail comptable.
 * Permet au comptable de saisir les informations, ajouter dynamiquement
 * des lignes d'articles, visualiser les totaux en temps réel,
 * et choisir entre "Enregistrer en brouillon" ou "Émettre et envoyer".
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
        due_date: defaultDueDate || '',
        notes: '',
        action: 'draft',
        items: [createEmptyItem()],
    });

    // Gestion dynamique des lignes de facturation
    const handleItemChange = (index, field, value) => {
        const updatedItems = [...data.items];
        updatedItems[index] = {
            ...updatedItems[index],
            [field]: value,
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

    // Calculs financiers réactifs en temps réel (Hors Taxes, TVA 18%, Toutes Taxes Comprises)
    const financials = useMemo(() => {
        let subtotal = 0;
        data.items.forEach((item) => {
            const qty = parseFloat(item.quantity) || 0;
            const price = parseFloat(item.unit_price) || 0;
            if (qty > 0 && price > 0) {
                subtotal += qty * price;
            }
        });

        // Taux standard TVA : 18%
        const taxAmount = Math.round(subtotal * 0.18 * 100) / 100;
        const total = Math.round((subtotal + taxAmount) * 100) / 100;

        return { subtotal, taxAmount, total };
    }, [data.items]);

    // Soumission du formulaire selon l'action choisie
    const handleSubmit = (actionType) => {
        transform((currentData) => ({
            ...currentData,
            action: actionType,
            items: currentData.items.map(({ id, ...item }) => item),
        }));
        post('/portal/invoices');
    };

    return (
        <PortalLayout auth={auth} organization={organization} title="Créer une nouvelle facture">
            <div className="invoice-create-container">
                {/* En-tête de la page avec bouton retour */}
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
                    }}
                    className="invoice-create-form"
                    noValidate
                >
                    {/* 1. Informations générales */}
                    <div className="form-section-card">
                        <h3 className="section-title">1. Informations générales</h3>
                        <div className="form-row">
                            <div className="form-group">
                                <label htmlFor="due_date" className="form-label required">
                                    Date d'échéance de paiement
                                </label>
                                <input
                                    id="due_date"
                                    type="date"
                                    className={`form-input ${errors.due_date ? 'input-error' : ''}`}
                                    value={data.due_date}
                                    onChange={(e) => setData('due_date', e.target.value)}
                                    aria-describedby={errors.due_date ? 'due_date_error' : undefined}
                                    aria-invalid={!!errors.due_date}
                                    required
                                />
                                {errors.due_date && (
                                    <span id="due_date_error" className="field-error-text" role="alert">
                                        {errors.due_date}
                                    </span>
                                )}
                            </div>

                            <div className="form-group">
                                <label htmlFor="notes" className="form-label">
                                    Mentions complémentaires ou notes (facultatif)
                                </label>
                                <input
                                    id="notes"
                                    type="text"
                                    placeholder="Ex: Conditions de paiement à 30 jours, virement bancaire..."
                                    className={`form-input ${errors.notes ? 'input-error' : ''}`}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    aria-describedby={errors.notes ? 'notes_error' : undefined}
                                />
                                {errors.notes && (
                                    <span id="notes_error" className="field-error-text" role="alert">
                                        {errors.notes}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* 2. Lignes d'articles et prestations */}
                    <div className="form-section-card">
                        <div className="section-header-flex">
                            <div>
                                <h3 className="section-title">2. Lignes de facturation</h3>
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
                                ➕ Ajouter une ligne
                            </button>
                        </div>

                        {errors.items && typeof errors.items === 'string' && (
                            <div className="form-alert error" role="alert">
                                {errors.items}
                            </div>
                        )}

                        <div className="table-responsive">
                            <table className="invoice-items-form-table">
                                <thead>
                                    <tr>
                                        <th style={{ width: '45%' }}>Description du produit / prestation</th>
                                        <th style={{ width: '15%' }}>Quantité</th>
                                        <th style={{ width: '20%' }}>Prix unitaire HT (XOF)</th>
                                        <th style={{ width: '15%' }}>Total HT (XOF)</th>
                                        <th style={{ width: '5%' }}>
                                            <span className="sr-only">Actions</span>
                                        </th>
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
                                            <tr key={item.id}>
                                                <td>
                                                    <input
                                                        type="text"
                                                        placeholder="Ex: Prestation de conseil IT..."
                                                        className={`form-input table-input ${descError ? 'input-error' : ''}`}
                                                        value={item.description}
                                                        onChange={(e) => handleItemChange(index, 'description', e.target.value)}
                                                        aria-label={`Description ligne ${index + 1}`}
                                                        required
                                                    />
                                                    {descError && (
                                                        <span className="field-error-text" role="alert">
                                                            {descError}
                                                        </span>
                                                    )}
                                                </td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        min="0.01"
                                                        step="any"
                                                        placeholder="1"
                                                        className={`form-input table-input ${qtyError ? 'input-error' : ''}`}
                                                        value={item.quantity}
                                                        onChange={(e) => handleItemChange(index, 'quantity', e.target.value)}
                                                        aria-label={`Quantité ligne ${index + 1}`}
                                                        required
                                                    />
                                                    {qtyError && (
                                                        <span className="field-error-text" role="alert">
                                                            {qtyError}
                                                        </span>
                                                    )}
                                                </td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="any"
                                                        placeholder="50 000"
                                                        className={`form-input table-input ${priceError ? 'input-error' : ''}`}
                                                        value={item.unit_price}
                                                        onChange={(e) => handleItemChange(index, 'unit_price', e.target.value)}
                                                        aria-label={`Prix unitaire ligne ${index + 1}`}
                                                        required
                                                    />
                                                    {priceError && (
                                                        <span className="field-error-text" role="alert">
                                                            {priceError}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="cell-line-total">
                                                    <strong>{formatCurrency(lineTotal, 'XOF')}</strong>
                                                </td>
                                                <td className="cell-action">
                                                    {data.items.length > 1 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => removeItem(index)}
                                                            className="btn-delete-row"
                                                            title={`Supprimer la ligne ${index + 1}`}
                                                            aria-label={`Supprimer la ligne ${index + 1}`}
                                                        >
                                                            ✕
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* 3. Récapitulatif financier et Choix de validation */}
                    <div className="invoice-bottom-grid">
                        <div className="invoice-explanation-card">
                            <h4 className="explanation-title">ℹ️ Cycle de vie de la facture</h4>
                            <p className="explanation-text">
                                • <strong>Enregistrer en brouillon :</strong> La facture reçoit un numéro séquentiel unique, les montants et taxes sont calculés et enregistrés de manière sécurisée en base de données. <em>Aucun email n'est expédié au client</em>. Vous pourrez la relire et l'émettre plus tard.
                            </p>
                            <p className="explanation-text">
                                • <strong>Émettre et envoyer au client :</strong> La facture est validée immédiatement et l'avis d'émission avec la facture en pièce jointe est envoyé instantanément par notification email à l'adresse de contact du client.
                            </p>
                        </div>

                        <div className="invoice-financial-summary-card">
                            <h4 className="summary-title">Récapitulatif Financier</h4>
                            <div className="summary-row">
                                <span className="summary-label">Sous-total HT :</span>
                                <span className="summary-value">{formatCurrency(financials.subtotal, 'XOF')}</span>
                            </div>
                            <div className="summary-row">
                                <span className="summary-label">TVA (18%) :</span>
                                <span className="summary-value">{formatCurrency(financials.taxAmount, 'XOF')}</span>
                            </div>
                            <div className="summary-row total-highlight">
                                <span className="summary-label">Total TTC à payer :</span>
                                <span className="summary-value total-price">{formatCurrency(financials.total, 'XOF')}</span>
                            </div>

                            <div className="summary-actions-group">
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={() => handleSubmit('draft')}
                                    className="btn-save-draft"
                                >
                                    💾 Enregistrer en brouillon
                                </button>
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={() => handleSubmit('send')}
                                    className="btn-emit-send"
                                >
                                    🚀 Émettre et envoyer au client
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </PortalLayout>
    );
}
