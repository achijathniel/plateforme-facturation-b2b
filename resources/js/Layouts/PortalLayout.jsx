import { Head, Link, usePage } from '@inertiajs/react';

/**
 * Layout principal pour l'espace portail de l'entreprise cliente.
 * Encapsule la barre supérieure sobre, l'identité de l'entreprise,
 * les informations de l'utilisateur connecté et le bouton de déconnexion.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string, organization?: { id: number, name: string } } },
 *     title?: string,
 *     organization?: { id: number, name: string },
 *     children: React.ReactNode
 * }} props
 */
export default function PortalLayout({ auth, title, organization, children }) {
    const { url } = usePage();
    const orgName = organization?.name || auth?.user?.organization?.name || 'Mon Entreprise';

    return (
        <>
            {title && <Head title={title} />}

            <div className="admin-layout">
                {/* Barre de navigation supérieure sobre en blanc */}
                <header className="admin-header">
                    <div className="admin-header-inner">
                        <div className="admin-brand">
                            <div className="admin-brand-icon">
                                <span role="img" aria-hidden="true">🏢</span>
                            </div>
                            <div>
                                <h1 className="admin-brand-title">
                                    {orgName}
                                </h1>
                                <p className="admin-brand-subtitle">Portail Facturation Client</p>
                            </div>
                        </div>

                        {/* Navigation principale du portail */}
                        <nav className="admin-nav" aria-label="Navigation portail">
                            <Link
                                href="/portal-dashboard"
                                className={`admin-nav-link ${url.startsWith('/portal-dashboard') || url.startsWith('/portal/dashboard') ? 'active' : ''}`}
                            >
                                <span role="img" aria-hidden="true">📊</span>
                                <span>Tableau de bord</span>
                            </Link>
                        </nav>

                        {/* Profil connecté & Déconnexion */}
                        <div className="admin-user-controls">
                            <div className="admin-profile">
                                <p className="admin-user-name">{auth?.user?.name}</p>
                                <p className="admin-user-badge">
                                    <span className="admin-status-dot">●</span> {auth?.user?.role?.toUpperCase()}
                                </p>
                            </div>

                            <Link
                                href="/portal/logout"
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
