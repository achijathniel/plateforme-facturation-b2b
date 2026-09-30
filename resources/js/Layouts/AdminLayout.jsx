import { Head, Link } from '@inertiajs/react';

/**
 * Layout principal pour les vues de l'espace administration.
 * Encapsule la barre supérieure, l'identité de marque, les informations du profil connecté et le bouton de déconnexion.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string } },
 *     title?: string,
 *     children: React.ReactNode
 * }} props
 */
export default function AdminLayout({ auth, title, children }) {
    return (
        <>
            {title && <Head title={title} />}

            <div className="admin-layout">
                {/* Barre de navigation supérieure sobre en blanc */}
                <header className="admin-header">
                    <div className="admin-header-inner">
                        <div className="admin-brand">
                            <div className="admin-brand-icon">
                                <span role="img" aria-hidden="true">💼</span>
                            </div>
                            <div>
                                <h1 className="admin-brand-title">
                                    Dealtoo Billing B2B
                                </h1>
                                <p className="admin-brand-subtitle">Espace Administrateur</p>
                            </div>
                        </div>

                        {/* Profil connecté & Déconnexion */}
                        <div className="admin-user-controls">
                            <div className="admin-profile">
                                <p className="admin-user-name">{auth?.user?.name}</p>
                                <p className="admin-user-badge">
                                    <span className="admin-status-dot">●</span> {auth?.user?.role?.toUpperCase()}
                                </p>
                            </div>

                            <Link
                                href="/admin/logout"
                                method="post"
                                as="button"
                                className="btn-secondary"
                            >
                                Déconnexion
                            </Link>
                        </div>
                    </div>
                </header>

                {/* Contenu principal injecté */}
                <main className="admin-main">
                    {children}
                </main>
            </div>
        </>
    );
}
