import { useMemo, useState } from 'react';
import { useForm, Link } from '@inertiajs/react';
import { confirmAction } from '../../Utils/confirmAction';
import ClientAutocomplete from './ClientAutocomplete';
import InvoiceItemsTable from './InvoiceItemsTable';
import InvoiceSummaryCard from './InvoiceSummaryCard';

/**
 * Générateur d'une ligne d'article vide avec identifiant unique temporaire.
 */
const createEmptyItem = () => ({
    id: typeof crypto !== 'undefined' && crypto.randomUUID
        ? crypto.randomUUID()
        : `item-${Date.now()}-${Math.random().toString(36).substring(2, 7)}`,
    description: '',
    quantity: 1,
    unit_price: '',
});

/**
 * Composant unifié et réutilisable pour la création et la modification de factures B2B.
 * Orchestre l'état Inertia, les calculs de TVA (18%) et les sous-composants dédiés.
 *
 * @param {{
 *     organization: { id: number, name: string },
 *     invoice?: {
 *         id: number,
 *         client_id?: number|null,
 *         invoice_number: string,
 *         client_name?: string,
 *         client_email?: string,
 *         client_address?: string,
 *         client_tax_number?: string,
 *         client_phone?: string,
 *         status?: string,
 *         due_date?: string|null,
 *         notes?: string|null,
 *         items?: Array<{
 *             id?: number|string,
 *             description: string,
 *             quantity: number|string,
 *             unit_price: number|string
 *         }>
 *     },
 *     defaultDueDate?: string,
 *     submitUrl: string,
 *     method?: 'post' | 'put',
 *     backUrl: string,
 *     isEdit?: boolean
 * }} props
 */
export default function InvoiceForm({
    organization,
    invoice,
    defaultDueDate = '',
    submitUrl,
    method = 'post',
    backUrl,
    isEdit = false,
}) {
    const initialItems = invoice?.items && invoice.items.length > 0
        ? invoice.items.map((it) => ({
            id: it.id || createEmptyItem().id,
            description: it.description || '',
            quantity: it.quantity ?? 1,
            unit_price: it.unit_price ?? '',
        }))
        : [createEmptyItem()];

    const [submittingAction, setSubmittingAction] = useState('draft');

    const { data, setData, post, put, processing, errors, transform } = useForm({
        client_id: invoice?.client_id || null,
        client_name: invoice?.client_name || '',
        client_email: invoice?.client_email || '',
        client_address: invoice?.client_address || '',
        client_tax_number: invoice?.client_tax_number || '',
        client_phone: invoice?.client_phone || '',
        due_date: invoice?.due_date || defaultDueDate || '',
        notes: invoice?.notes || '',
        action: 'draft',
        items: initialItems,
    });

    const handleClientNameChange = (val) => {
        setData((prev) => ({
            ...prev,
            client_name: val,
            client_id: prev.client_name === val ? prev.client_id : null,
        }));
    };

    const handleSelectClient = (client) => {
        setData((prev) => ({
            ...prev,
            client_id: client.id,
            client_name: client.name,
            client_email: client.email || '',
            client_address: client.address || '',
            client_tax_number: client.tax_number || '',
            client_phone: client.phone || '',
        }));
    };

    const handleDetachClient = () => {
        setData('client_id', null);
    };

    const handleItemChange = (index, field, value) => {
        const updatedItems = [...data.items];
        const sanitizedValue = (field === 'unit_price' || field === 'quantity') && typeof value === 'string'
            ? value.replace(/\s+/g, '').replace(',', '.')
            : value;

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
        setData('items', data.items.filter((_, i) => i !== index));
    };

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

    const executeSubmit = (actionType) => {
        setSubmittingAction(actionType);
        transform((currentData) => ({
            ...currentData,
            action: actionType,
            items: currentData.items.map(({ id, ...item }) => ({
                ...item,
                quantity: parseFloat(item.quantity) || 0,
                unit_price: parseFloat(item.unit_price) || 0,
            })),
        }));

        if (method === 'put') {
            put(submitUrl);
        } else {
            post(submitUrl);
        }
    };

    const handleSubmit = async (actionType) => {
        if (actionType !== 'send') {
            executeSubmit('draft');
            return;
        }

        const confirmed = await confirmAction({
            title: isEdit ? 'Enregistrer et envoyer au client ?' : 'Émettre et envoyer la facture au client ?',
            text: isEdit
                ? `La facture ${invoice?.invoice_number || ''} sera mise à jour et expédiée immédiatement par notification email à ${data.client_email || "l'adresse du client"}.`
                : `La facture sera immédiatement transmise par notification email à ${data.client_email || "l'adresse du client"}.`,
            confirmText: isEdit ? 'Oui, enregistrer et envoyer' : 'Oui, émettre et envoyer',
            icon: 'question',
            tone: 'primary',
            reverseButtons: true,
        });

        if (confirmed) {
            executeSubmit('send');
        }
    };

    return (
        <div className="invoice-create-container">
            <div className="page-header-row">
                <div>
                    <div className="breadcrumb-nav">
                        <Link href={backUrl} className="breadcrumb-link">
                            {isEdit ? '← Retour aux détails de la facture' : '← Retour au tableau de bord'}
                        </Link>
                    </div>
                    <h2 className="portal-page-title">
                        {isEdit ? `Modifier la facture ${invoice?.invoice_number}` : 'Créer une nouvelle facture'}
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
                className="invoice-create-form"
                noValidate
            >
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
                                {isEdit
                                    ? "Modifiez les coordonnées de l'entreprise cliente destinataire de cette facture."
                                    : "Renseignez les coordonnées de l'entreprise à laquelle cette facture est adressée."}
                            </p>
                        </div>
                    </div>

                    <div className="form-row">
                        <ClientAutocomplete
                            clientId={data.client_id}
                            clientName={data.client_name}
                            error={errors.client_name}
                            onChangeName={handleClientNameChange}
                            onSelectClient={handleSelectClient}
                            onDetachClient={handleDetachClient}
                        />

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
                                aria-invalid={!!errors.client_email}
                                aria-describedby={errors.client_email ? 'client-email-error' : undefined}
                                required
                            />
                            {errors.client_email && (
                                <span id="client-email-error" className="field-error-text" role="alert">
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
                                aria-invalid={!!errors.client_tax_number}
                                aria-describedby={errors.client_tax_number ? 'client-tax-error' : undefined}
                            />
                            {errors.client_tax_number && (
                                <span id="client-tax-error" className="field-error-text" role="alert">
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
                                aria-invalid={!!errors.client_phone}
                                aria-describedby={errors.client_phone ? 'client-phone-error' : undefined}
                            />
                            {errors.client_phone && (
                                <span id="client-phone-error" className="field-error-text" role="alert">
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
                            aria-invalid={!!errors.client_address}
                            aria-describedby={errors.client_address ? 'client-address-error' : undefined}
                        />
                        {errors.client_address && (
                            <span id="client-address-error" className="field-error-text" role="alert">
                                {errors.client_address}
                            </span>
                        )}
                    </div>
                </div>

                {/* Section 2 : Lignes de facturation */}
                <InvoiceItemsTable
                    items={data.items}
                    errors={errors}
                    onItemChange={handleItemChange}
                    onAddItem={addItem}
                    onRemoveItem={removeItem}
                />

                {/* Section 3 : Modalités de règlement & Récapitulatif */}
                <div className="invoice-bottom-grid" style={{ marginTop: '24px' }}>
                    <div className="form-section-card">
                        <h3 className="section-title">3. Modalités &amp; Règlement</h3>
                        <p className="section-description">
                            Date limite de règlement et mentions particulières.
                        </p>

                        <div className="form-group" style={{ marginTop: '16px' }}>
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
                                aria-invalid={!!errors.due_date}
                                aria-describedby={errors.due_date ? 'due-date-error' : undefined}
                                required
                            />
                            {errors.due_date && (
                                <span id="due-date-error" className="field-error-text" role="alert">
                                    {errors.due_date}
                                </span>
                            )}
                        </div>

                        <div className="form-group" style={{ marginTop: '16px' }}>
                            <label htmlFor="notes" className="form-label">
                                Notes et conditions de paiement (facultatif)
                            </label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                placeholder="Ex: Règlement par virement bancaire sous 30 jours..."
                                className={`form-input ${errors.notes ? 'input-error' : ''}`}
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                style={{ resize: 'vertical' }}
                                aria-invalid={!!errors.notes}
                                aria-describedby={errors.notes ? 'notes-error' : undefined}
                            />
                            {errors.notes && (
                                <span id="notes-error" className="field-error-text" role="alert">
                                    {errors.notes}
                                </span>
                            )}
                        </div>
                    </div>

                    <InvoiceSummaryCard
                        financials={financials}
                        processing={processing}
                        action={submittingAction}
                        isEdit={isEdit}
                        onSendSubmit={() => handleSubmit('send')}
                    />
                </div>
            </form>
        </div>
    );
}
