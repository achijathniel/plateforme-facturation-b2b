import { Head, useForm } from '@inertiajs/react';

/**
 * Composant de formulaire d'authentification réutilisable pour l'administration et le portail entreprise.
 *
 * @param {{
 *     title: string,
 *     badgeIcon: string,
 *     headingTitle: string,
 *     headingSubtitle: string,
 *     emailLabel: string,
 *     emailPlaceholder: string,
 *     submitButtonText: string,
 *     actionUrl: string,
 *     defaultEmail?: string,
 *     defaultPassword?: string,
 *     helperText?: React.ReactNode
 * }} props
 */
export default function LoginForm({
    title,
    badgeIcon,
    headingTitle,
    headingSubtitle,
    emailLabel,
    emailPlaceholder,
    submitButtonText,
    actionUrl,
    defaultEmail = '',
    defaultPassword = '',
    helperText,
}) {
    const { data, setData, post, processing, errors } = useForm({
        email: defaultEmail,
        password: defaultPassword,
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(actionUrl);
    };

    return (
        <>
            <Head title={title} />

            <div className="auth-wrapper">
                <div className="auth-header">
                    <div className="auth-logo-badge" aria-hidden="true">
                        {badgeIcon}
                    </div>
                    <h1 className="auth-title">
                        {headingTitle}
                    </h1>
                    <p className="auth-subtitle">
                        {headingSubtitle}
                    </p>
                </div>

                <div className="auth-card">
                    <form className="auth-form" onSubmit={submit} noValidate>
                        {/* Champ Email */}
                        <div className="form-group">
                            <label htmlFor="email" className="form-label">
                                {emailLabel}
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autoComplete="email"
                                required
                                aria-invalid={!!errors.email}
                                aria-describedby={errors.email ? 'email-error' : undefined}
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className={`form-input ${errors.email ? 'form-input-error' : ''}`}
                                placeholder={emailPlaceholder}
                            />
                            {errors.email && (
                                <p id="email-error" className="form-error-text" role="alert">
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        {/* Champ Mot de passe */}
                        <div className="form-group">
                            <label htmlFor="password" className="form-label">
                                Mot de passe
                            </label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autoComplete="current-password"
                                required
                                aria-invalid={!!errors.password}
                                aria-describedby={errors.password ? 'password-error' : undefined}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className={`form-input ${errors.password ? 'form-input-error' : ''}`}
                                placeholder="••••••••"
                            />
                            {errors.password && (
                                <p id="password-error" className="form-error-text" role="alert">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        {/* Se souvenir de moi */}
                        <label className="form-checkbox-row">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                            />
                            <span>Se souvenir de moi</span>
                        </label>

                        {/* Bouton de Soumission */}
                        <button
                            type="submit"
                            disabled={processing}
                            className="btn-primary"
                        >
                            {processing ? 'Vérification des accès...' : submitButtonText}
                        </button>
                    </form>

                    {helperText && (
                        <div className="auth-footer-help">
                            {helperText}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
