import { useForm, Link } from '@inertiajs/react';

/**
 * Composant formulaire unifié pour la création et l'édition d'une entreprise / organisation.
 * Respecte le principe DRY et les directives d'accessibilité WCAG 2.2.
 *
 * @param {{
 *     organization?: {
 *         id: number,
 *         name: string,
 *         tax_number?: string|null,
 *         email: string,
 *         phone?: string|null,
 *         address?: string|null,
 *         is_active?: boolean
 *     },
 *     submitUrl: string,
 *     method?: 'post' | 'put',
 *     backUrl: string,
 *     isEdit?: boolean
 * }} props
 */
export default function OrganizationForm({
    organization,
    submitUrl,
    method = 'post',
    backUrl,
    isEdit = false,
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: organization?.name || '',
        tax_number: organization?.tax_number || '',
        email: organization?.email || '',
        phone: organization?.phone || '',
        address: organization?.address || '',
        is_active: organization?.is_active ?? true,
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
        <div className="invoice-create-container">
            {/* Fil d'Ariane & Titre */}
            <div className="page-header-row">
                <div>
                    <div className="breadcrumb-nav">
                        <Link href={backUrl} className="breadcrumb-link">
                            ← Retour à la liste
                        </Link>
                    </div>
                    <h2 className="portal-page-title">
                        {isEdit ? `Modifier l'entreprise « ${organization?.name} »` : 'Créer une nouvelle entreprise'}
                    </h2>
                    <p className="portal-page-subtitle">
                        {isEdit
                            ? "Mettez à jour les coordonnées juridiques et administratives de l'entreprise."
                            : 'Enregistrez une nouvelle entité pour lui permettre de facturer sur la plateforme.'}
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="invoice-create-form" noValidate>
                {/* Alerte générale si erreurs de validation */}
                {Object.keys(errors).length > 0 && (
                    <div className="form-alert error" role="alert">
                        <strong>Erreurs de validation :</strong> Veuillez vérifier les informations saisies ci-dessous.
                    </div>
                )}

                {/* Section Informations générales */}
                <div className="form-section-card">
                    <div className="section-header-flex">
                        <div>
                            <h3 className="section-title">Informations Générales & Juridiques</h3>
                            <p className="section-description">
                                Renseignez la dénomination sociale et les identifiants fiscaux légaux de l'organisation.
                            </p>
                        </div>
                    </div>

                    <div className="form-row">
                        <div className="form-group">
                            <label htmlFor="name" className="form-label required">
                                Raison sociale / Nom de l'entreprise
                            </label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                placeholder="Ex: Société Ivoirienne de Négoce SA"
                                className={`form-input ${errors.name ? 'input-error' : ''}`}
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                aria-invalid={!!errors.name}
                                aria-describedby={errors.name ? 'name-error' : undefined}
                                required
                            />
                            {errors.name && (
                                <span id="name-error" className="field-error-text" role="alert">
                                    {errors.name}
                                </span>
                            )}
                        </div>

                        <div className="form-group">
                            <label htmlFor="tax_number" className="form-label">
                                N° Compte Contribuable / NIF (facultatif)
                            </label>
                            <input
                                id="tax_number"
                                name="tax_number"
                                type="text"
                                placeholder="Ex: CI-ABJ-2023-B-99887"
                                className={`form-input ${errors.tax_number ? 'input-error' : ''}`}
                                value={data.tax_number}
                                onChange={(e) => setData('tax_number', e.target.value)}
                                aria-invalid={!!errors.tax_number}
                                aria-describedby={errors.tax_number ? 'tax-number-error' : undefined}
                            />
                            {errors.tax_number && (
                                <span id="tax-number-error" className="field-error-text" role="alert">
                                    {errors.tax_number}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="form-row" style={{ marginTop: '16px' }}>
                        <div className="form-group">
                            <label htmlFor="email" className="form-label required">
                                Adresse email institutionnelle / facturation
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                placeholder="Ex: contact@entreprise.ci"
                                className={`form-input ${errors.email ? 'input-error' : ''}`}
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                aria-invalid={!!errors.email}
                                aria-describedby={errors.email ? 'email-error' : undefined}
                                required
                            />
                            {errors.email && (
                                <span id="email-error" className="field-error-text" role="alert">
                                    {errors.email}
                                </span>
                            )}
                        </div>

                        <div className="form-group">
                            <label htmlFor="phone" className="form-label">
                                Numéro de téléphone standard (facultatif)
                            </label>
                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                placeholder="Ex: +225 27 20 00 00 00"
                                className={`form-input ${errors.phone ? 'input-error' : ''}`}
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                aria-invalid={!!errors.phone}
                                aria-describedby={errors.phone ? 'phone-error' : undefined}
                            />
                            {errors.phone && (
                                <span id="phone-error" className="field-error-text" role="alert">
                                    {errors.phone}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="form-group" style={{ marginTop: '16px' }}>
                        <label htmlFor="address" className="form-label">
                            Adresse géographique du siège (facultatif)
                        </label>
                        <input
                            id="address"
                            name="address"
                            type="text"
                            placeholder="Ex: Boulevard de la République, Plateau, Abidjan"
                            className={`form-input ${errors.address ? 'input-error' : ''}`}
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            aria-invalid={!!errors.address}
                            aria-describedby={errors.address ? 'address-error' : undefined}
                        />
                        {errors.address && (
                            <span id="address-error" className="field-error-text" role="alert">
                                {errors.address}
                            </span>
                        )}
                    </div>

                    {/* Statut d'activation */}
                    <div className="form-group" style={{ marginTop: '20px' }}>
                        <label className="form-checkbox-label" htmlFor="is_active">
                            <input
                                id="is_active"
                                name="is_active"
                                type="checkbox"
                                className="form-checkbox-input"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                aria-invalid={!!errors.is_active}
                                aria-describedby={errors.is_active ? 'is-active-error' : undefined}
                            />
                            <span>Activer immédiatement cette entreprise (accès opérationnel à la plateforme)</span>
                        </label>
                        {errors.is_active && (
                            <span id="is-active-error" className="field-error-text" role="alert">
                                {errors.is_active}
                            </span>
                        )}
                    </div>
                </div>

                {/* Boutons d'action */}
                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '24px' }}>
                    <Link href={backUrl} className="btn-secondary" style={{ textDecoration: 'none' }}>
                        Annuler
                    </Link>
                    <button type="submit" disabled={processing} className="btn-primary">
                        <span aria-hidden="true">💾</span>
                        <span>
                            {processing
                                ? 'Enregistrement en cours...'
                                : isEdit
                                    ? 'Enregistrer les modifications'
                                    : "Créer l'entreprise"}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    );
}
