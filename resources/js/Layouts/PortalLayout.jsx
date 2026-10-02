import { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import FlashMessages from '../Components/FlashMessages';
import UserControls from '../Components/UserControls';

/**
 * Layout principal pour l'espace portail de l'entreprise cliente.
 * Conserve l'en-tête supérieur avec le profil et la déconnexion,
 * et intègre une barre latérale gauche pliable / dépliable.
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

    // État mémorisé pour plier / déplier la barre latérale (sécurisé contre les erreurs d'hydratation SSR)
    const [isCollapsed, setIsCollapsed] = useState(false);

    useEffect(() => {
        try {
            const saved = localStorage.getItem('dealtoo_portal_sidebar_collapsed');
            if (saved !== null) {
                setIsCollapsed(saved === 'true');
            }
        } catch {
            // Mode incognito ou restrictions d'accès au stockage local
        }
    }, []);

    const toggleSidebar = () => {
        setIsCollapsed((prev) => {
            const next = !prev;
            try {
                localStorage.setItem('dealtoo_portal_sidebar_collapsed', String(next));
            } catch {
                // Ignore
            }
            return next;
        });
    };

    return (
        <>
            {title && <Head title={title} />}
            <FlashMessages />

            <div className="portal-app">
                {/* 1. EN-TÊTE SUPÉRIEUR FIXE (Logo, Nom, Rôle vert & Déconnexion) */}
                <header className="admin-header portal-header-top">
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

                        {/* Profil connecté & Déconnexion */}
                        <UserControls auth={auth} logoutUrl="/portal/logout" />
                    </div>
                </header>

                {/* 2. CORPS PRINCIPAL AVEC MENU LATÉRAL GAUCHE RÉTRACTABLE */}
                <div className="portal-layout-body">
                    {/* Menu latéral gauche */}
                    <aside
                        className={`portal-sidebar ${isCollapsed ? 'collapsed' : ''}`}
                        aria-label="Menu latéral de comptabilité"
                    >
                        {/* Bouton de bascule Plier / Déplier */}
                        <div className="portal-sidebar-toggle-row">
                            <button
                                type="button"
                                onClick={toggleSidebar}
                                className="btn-portal-toggle"
                                aria-expanded={!isCollapsed}
                                aria-controls="portal-sidebar-nav"
                                title={isCollapsed ? 'Déplier le menu latéral' : 'Replier le menu latéral'}
                                aria-label={isCollapsed ? 'Déplier le menu latéral' : 'Replier le menu latéral'}
                            >
                                <span className="toggle-icon" aria-hidden="true">
                                    {isCollapsed ? '▶' : '◀'}
                                </span>
                                {!isCollapsed && <span className="toggle-label">Replier</span>}
                            </button>
                        </div>

                        {/* Bouton d'action proéminent : Nouvelle Facture */}
                        <div className="portal-sidebar-action-box">
                            <Link
                                href="/portal/invoices/create"
                                className="btn-action-new-invoice"
                                title="Créer une nouvelle facture"
                                aria-label="Créer une nouvelle facture"
                            >
                                <span className="action-icon" aria-hidden="true">➕</span>
                                {!isCollapsed && <span className="action-label">Nouvelle Facture</span>}
                            </Link>
                        </div>

                        {/* Liens de navigation extensibles */}
                        <nav id="portal-sidebar-nav" className="portal-sidebar-nav" aria-label="Navigation principale">
                            <div className="portal-nav-group">
                                {!isCollapsed && <span className="portal-nav-group-title">Navigation</span>}

                                <Link
                                    href="/portal-dashboard"
                                    className={`portal-nav-link ${url.startsWith('/portal-dashboard') || url.startsWith('/portal/dashboard') ? 'active' : ''}`}
                                    aria-current={url.startsWith('/portal-dashboard') || url.startsWith('/portal/dashboard') ? 'page' : undefined}
                                    title="Tableau de bord"
                                >
                                    <span className="portal-nav-icon" aria-hidden="true">📊</span>
                                    <span className={isCollapsed ? 'sr-only' : 'portal-nav-text'}>
                                        Tableau de bord
                                    </span>
                                </Link>

                                <Link
                                    href="/portal/invoices"
                                    className={`portal-nav-link ${url.startsWith('/portal/invoices') && !url.includes('/create') ? 'active' : ''}`}
                                    aria-current={url.startsWith('/portal/invoices') && !url.includes('/create') ? 'page' : undefined}
                                    title="Mes Factures"
                                >
                                    <span className="portal-nav-icon" aria-hidden="true">📑</span>
                                    <span className={isCollapsed ? 'sr-only' : 'portal-nav-text'}>
                                        Mes Factures
                                    </span>
                                </Link>
                            </div>

                            {/* Section extensible pour les futures fonctionnalités */}
                            <div className="portal-nav-group">
                                {!isCollapsed && <span className="portal-nav-group-title">Comptabilité</span>}

                                <Link
                                    href="/portal/payments"
                                    className="portal-nav-link disabled"
                                    title="Règlements & Paiements (Bientôt disponible)"
                                    onClick={(e) => e.preventDefault()}
                                >
                                    <span className="portal-nav-icon" aria-hidden="true">💳</span>
                                    {!isCollapsed && (
                                        <span className="portal-nav-text">
                                            Règlements
                                            <span className="badge-coming-soon">Bientôt</span>
                                        </span>
                                    )}
                                </Link>
                            </div>
                        </nav>
                    </aside>

                    {/* Zone de contenu principale à droite */}
                    <main className="portal-content-main">
                        {children}
                    </main>
                </div>
            </div>
        </>
    );
}
