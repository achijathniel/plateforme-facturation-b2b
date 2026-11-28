import { Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

/**
 * Formulaire unifié pour la création et l'édition d'un client B2B dans le portail comptable.
 * Respecte le principe DRY et les directives d'accessibilité WCAG 2.2 AA.
 *
 * @param {{
 *     client?: {
 *         id: number,
 *         name: string,
 *         tax_number?: string|null,
 *         email?: string|null,
 *         phone?: string|null,
 *         address?: string|null,
 *         notes?: string|null
 *     },
 *     submitUrl: string,
 *     method?: 'post' | 'put',
 *     backUrl: string,
 *     isEdit?: boolean
 * }} props
 */
export default function ClientForm({
    client,
    submitUrl,
    method = 'post',
    backUrl,
    isEdit = false,
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: client?.name || '',
        tax_number: client?.tax_number || '',
        email: client?.email || '',
        phone: client?.phone || '',
        address: client?.address || '',
        notes: client?.notes || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (method === 'put') {
            put(submitUrl);
        } else {
            post(submitUrl);
        }
    };

    return (
        <div className="invoice-create-container clients-scope">
            {/* Fil d'Ariane & Titre */}
            <div className="portal-page-header-row">
                <div>
                    <div className="breadcrumb-nav">
                        <Link href={backUrl} className="breadcrumb-link">
                            <span aria-hidden="true">← </span>Retour
                        </Link>
                    </div>
                    <h1 className="portal-page-title">
                        {isEdit ? `Modifier le client « ${client?.name} »` : 'Ajouter un nouveau client'}
                    </h1>
                    <p className="portal-page-subtitle">
                        {isEdit
                            ? 'Mettez à jour la fiche de ce client sans modifier ses factures déjà émises.'
                            : 'Enregistrez une entreprise cliente pour accélérer vos futures facturations.'}
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="invoice-create-form" noValidate>
                {Object.keys(errors).length > 0 && (
                    <div className="form-alert error" role="alert">
                        <strong>Erreurs de validation :</strong> Veuillez vérifier les champs signalés ci-dessous.
                    </div>
                )}

                <div className="form-section-card">
                    <div className="section-header-flex">
                        <div>
                            <h2 className="section-title">Identité & Coordonnées du client</h2>
                            <p className="section-description">
                                Ces coordonnées pré-rempliront automatiquement vos prochaines factures. Les champs marqués d'un * sont obligatoires.
                            </p>
                        </div>
                    </div>

                    <div className="form-row">
                        <div className="form-group">
                            <label htmlFor="client-name" className="form-label required">
                                Raison sociale / Nom du client
                            </label>
                            <input
                                id="client-name"
                                name="name"
                                type="text"
                                placeholder="Ex: Société Générale de Distribution SARL"
                                className={`form-input ${errors.name ? 'input-error' : ''}`}
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                aria-invalid={!!errors.name}
                                aria-required="true"
                                aria-describedby={errors.name ? 'client-name-error' : undefined}
                                required
                            />
                            {errors.name && (
                                <span id="client-name-error" className="field-error-text">
                                    {errors.name}
                                </span>
                            )}
                        </div>

                        <div className="form-group">
                            <label htmlFor="client-tax-number" className="form-label">
                                N° Compte Contribuable / NIF / RCCM
                            </label>
                            <input
                                id="client-tax-number"
                                name="tax_number"
                                type="text"
                                placeholder="Ex: CI-ABJ-2026-B-10293"
                                className={`form-input ${errors.tax_number ? 'input-error' : ''}`}
                                value={data.tax_number}
                                onChange={(e) => setData('tax_number', e.target.value)}
                                aria-invalid={!!errors.tax_number}
                                aria-describedby={errors.tax_number ? 'client-tax-error' : undefined}
                            />
                            {errors.tax_number && (
                                <span id="client-tax-error" className="field-error-text" role="alert">
                                    {errors.tax_number}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="form-row">
                        <div className="form-group">
                            <label htmlFor="client-email" className="form-label">
                                Adresse email de facturation
                            </label>
                            <input
                                id="client-email"
                                name="email"
                                type="email"
                                placeholder="Ex: comptabilite@client.ci"
                                className={`form-input ${errors.email ? 'input-error' : ''}`}
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                aria-invalid={!!errors.email}
                                aria-describedby={errors.email ? 'client-email-error' : undefined}
                            />
                            {errors.email && (
                                <span id="client-email-error" className="field-error-text" role="alert">
                                    {errors.email}
                                </span>
                            )}
                        </div>

                        <div className="form-group">
                            <label htmlFor="client-phone" className="form-label">
                                Numéro de téléphone
                            </label>
                            <input
                                id="client-phone"
                                name="phone"
                                type="tel"
                                placeholder="Ex: +225 07 00 00 00 00"
                                className={`form-input ${errors.phone ? 'input-error' : ''}`}
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                aria-invalid={!!errors.phone}
                                aria-describedby={errors.phone ? 'client-phone-error' : undefined}
                            />
                            {errors.phone && (
                                <span id="client-phone-error" className="field-error-text" role="alert">
                                    {errors.phone}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="form-group" style={{ marginTop: '16px' }}>
                        <label htmlFor="client-address" className="form-label">
                            Adresse postale ou géographique
                        </label>
                        <input
                            id="client-address"
                            name="address"
                            type="text"
                            placeholder="Ex: Zone 4C, Rue du Dr Blanchard, Abidjan"
                            className={`form-input ${errors.address ? 'input-error' : ''}`}
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            aria-invalid={!!errors.address}
                            aria-describedby={errors.address ? 'client-address-error' : undefined}
                        />
                        {errors.address && (
                            <span id="client-address-error" className="field-error-text" role="alert">
                                {errors.address}
                            </span>
                        )}
                    </div>

                    <div className="form-group" style={{ marginTop: '16px' }}>
                        <label htmlFor="client-notes" className="form-label">
                            Notes internes (conditions de règlement, interlocuteur...)
                        </label>
                        <textarea
                            id="client-notes"
                            name="notes"
                            rows={3}
                            placeholder="Ex: Paiement par virement à 30 jours, contact M. Koné."
                            className={`form-input ${errors.notes ? 'input-error' : ''}`}
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            aria-invalid={!!errors.notes}
                            aria-describedby={errors.notes ? 'client-notes-error' : undefined}
                        />
                        {errors.notes && (
                            <span id="client-notes-error" className="field-error-text" role="alert">
                                {errors.notes}
                            </span>
                        )}
                    </div>
                </div>

                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px' }}>
                    <Link href={backUrl} className="btn-secondary" style={{ textDecoration: 'none' }}>
                        Annuler
                    </Link>
                    <button type="submit" disabled={processing} className="btn-primary">
                        <Save size={16} aria-hidden="true" />
                        <span>
                            {processing
                                ? 'Enregistrement en cours...'
                                : isEdit
                                    ? 'Enregistrer les modifications'
                                    : 'Enregistrer le client'}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    );
}
