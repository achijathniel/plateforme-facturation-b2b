import { Head, Link, usePage } from '@inertiajs/react';
import FlashMessages from '../Components/FlashMessages';
import UserControls from '../Components/UserControls';

/**
 * Layout principal pour les vues de l'espace administration.
 * Encapsule la barre supérieure, la navigation principale, l'identité de marque,
 * les informations du profil connecté et le bouton de déconnexion.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string } },
 *     title?: string,
 *     children: React.ReactNode
 * }} props
 */
export default function AdminLayout({ auth, title, children }) {
    const { url } = usePage();

    return (
        <>
            {title && <Head title={title} />}
            <FlashMessages />

            <div className="admin-layout">
                <a href="#main-content" className="skip-link">
                    Aller au contenu principal
                </a>

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

                        {/* Navigation principale */}
                        <nav className="admin-nav" aria-label="Navigation principale">
                            <Link
                                href="/admin/dashboard"
                                className={`admin-nav-link ${url === '/admin/dashboard' ? 'active' : ''}`}
                                aria-current={url === '/admin/dashboard' ? 'page' : undefined}
                            >
                                <span role="img" aria-hidden="true">📊</span>
                                <span>Tableau de bord</span>
                            </Link>
                            <Link
                                href="/admin/organizations"
                                className={`admin-nav-link ${url.startsWith('/admin/organizations') ? 'active' : ''}`}
                                aria-current={url.startsWith('/admin/organizations') ? 'page' : undefined}
                            >
                                <span role="img" aria-hidden="true">🏢</span>
                                <span>Entreprises</span>
                            </Link>
                            <Link
                                href="/admin/invoices"
                                className={`admin-nav-link ${url.startsWith('/admin/invoices') ? 'active' : ''}`}
                                aria-current={url.startsWith('/admin/invoices') ? 'page' : undefined}
                            >
                                <span role="img" aria-hidden="true">📑</span>
                                <span>Factures</span>
                            </Link>
                        </nav>

                        {/* Profil connecté & Déconnexion */}
                        <UserControls auth={auth} logoutUrl="/admin/logout" />
                    </div>
                </header>

                {/* Contenu principal injecté */}
                <main id="main-content" tabIndex={-1} className="admin-main">
                    {children}
                </main>
            </div>
        </>
    );
}
