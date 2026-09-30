import { Head, useForm } from '@inertiajs/react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: 'comptable0@dealtoo.com',
        password: 'password',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/portal/login');
    };

    return (
        <>
            <Head title="Connexion Portail Entreprise" />

            <div className="auth-wrapper">
                <div className="auth-header">
                    <div className="auth-logo-badge">
                        🏢
                    </div>
                    <h1 className="auth-title">
                        Portail Entreprise B2B
                    </h1>
                    <p className="auth-subtitle">
                        Espace comptabilité et gestion des factures de votre entreprise
                    </p>
                </div>

                <div className="auth-card">
                    <form className="auth-form" onSubmit={submit}>
                        {/* Champ Email */}
                        <div className="form-group">
                            <label htmlFor="email" className="form-label">
                                Adresse Email Professionnelle
                            </label>
                            <input
                                id="email"
                                type="email"
                                autoComplete="email"
                                required
                                aria-invalid={!!errors.email}
                                aria-describedby={errors.email ? 'email-error' : undefined}
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className={`form-input ${errors.email ? 'form-input-error' : ''}`}
                                placeholder="comptable0@dealtoo.com"
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
                            {processing ? 'Vérification des accès...' : 'Accéder à mon espace entreprise'}
                        </button>
                    </form>

                    <div className="auth-footer-help">
                        Identifiants de test : <code>comptable0@dealtoo.com</code> / <code>password</code>
                    </div>
                </div>
            </div>
        </>
    );
}
