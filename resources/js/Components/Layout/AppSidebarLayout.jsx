import { Head, Link, usePage } from '@inertiajs/react';
import { Menu, PanelLeft, Plus, X } from 'lucide-react';
import FlashMessages from '../FlashMessages';
import useSidebarShellState from '../../Hooks/useSidebarShellState';

/**
 * Shell de mise en page unifié avec menu latéral gauche pleine hauteur (100vh),
 * repli mémorisé, tiroir mobile accessible (focus trap, Escape, inert) et topbar légère.
 *
 * @param {{
 *     title?: string,
 *     storageKey: string,
 *     sidebarAriaLabel: string,
 *     brand?: {
 *         icon: React.ReactNode,
 *         title: string,
 *         subtitle: string,
 *         href?: string
 *     },
 *     primaryNav: Array<{
 *         key: string,
 *         label: string,
 *         href?: string,
 *         icon: React.ReactNode,
 *         isActive?: boolean,
 *         disabled?: boolean,
 *         badge?: string,
 *         title?: string
 *     }>,
 *     quickSection?: {
 *         title: string,
 *         addAction?: { href: string, label: string },
 *         items: Array<{
 *             key: string,
 *             label: string,
 *             shortCode?: string,
 *             href: string,
 *             dotClass: string,
 *             isActive?: boolean
 *         }>
 *     },
 *     footerItems?: Array<{
 *         key: string,
 *         label: string,
 *         href: string,
 *         icon: React.ReactNode,
 *         method?: 'get' | 'post',
 *         as?: string,
 *         isActive?: boolean,
 *         danger?: boolean
 *     }>,
 *     topbarContext?: string,
 *     topbarAction?: {
 *         href: string,
 *         label: string,
 *         icon?: React.ReactNode
 *     },
 *     topbarRight?: React.ReactNode,
 *     children: React.ReactNode
 * }} props
 */
export default function AppSidebarLayout({
    title,
    storageKey,
    sidebarAriaLabel,
    brand,
    primaryNav = [],
    quickSection,
    footerItems = [],
    topbarContext,
    topbarAction,
    topbarRight,
    children,
}) {
    const { url } = usePage();
    const {
        sidebarRef,
        mobileTriggerRef,
        mobileCloseRef,
        isCollapsed,
        isMobileViewport,
        isMobileOpen,
        effectiveCollapsed,
        toggleCollapse,
        openMobileMenu,
        closeMobileMenu,
    } = useSidebarShellState(storageKey, url);

    const toggleLabel = isCollapsed ? 'Déplier le menu latéral' : 'Replier le menu latéral';
    const isSidebarInert = isMobileViewport && !isMobileOpen;
    const isMainInert = isMobileViewport && isMobileOpen;

    const footerNavLinks = footerItems.filter((item) => item.method !== 'post');
    const footerActions = footerItems.filter((item) => item.method === 'post');

    const handleNavLinkClick = () => {
        if (isMobileOpen) {
            closeMobileMenu(false);
        }
    };

    const brandContent = brand && (
        <>
            <span className="app-sidebar-brand-icon" aria-hidden="true">
                {brand.icon}
            </span>
            <span className="app-sidebar-brand-text">
                <span className="app-sidebar-brand-title">{brand.title}</span>
                <span className="app-sidebar-brand-subtitle">{brand.subtitle}</span>
            </span>
        </>
    );

    return (
        <>
            {title && <Head title={title} />}
            <FlashMessages />

            <div className="app-shell">
                <a href="#main-content" className="skip-link">
                    Aller au contenu principal
                </a>

                {isMobileOpen && (
                    <div
                        className="app-sidebar-backdrop"
                        aria-hidden="true"
                        onClick={() => closeMobileMenu(true)}
                    />
                )}

                <div
                    ref={sidebarRef}
                    id="app-sidebar-navigation"
                    className={`app-sidebar ${effectiveCollapsed ? 'is-collapsed' : ''} ${isMobileOpen ? 'is-mobile-open' : ''}`}
                    role={isMobileViewport && isMobileOpen ? 'dialog' : undefined}
                    aria-modal={isMobileViewport && isMobileOpen ? 'true' : undefined}
                    aria-label={isMobileViewport && isMobileOpen ? sidebarAriaLabel : undefined}
                    inert={isSidebarInert ? true : undefined}
                >
                    {/* En-tête minimaliste du menu latéral + bouton de repli PanelLeft */}
                    <div className="app-sidebar-header">
                        {!effectiveCollapsed && brand && (
                            brand.href ? (
                                <Link
                                    href={brand.href}
                                    className="app-sidebar-brand"
                                    title={brand.title}
                                    onClick={handleNavLinkClick}
                                >
                                    {brandContent}
                                </Link>
                            ) : (
                                <div className="app-sidebar-brand">{brandContent}</div>
                            )
                        )}

                        <button
                            type="button"
                            onClick={toggleCollapse}
                            className="app-sidebar-toggle"
                            aria-pressed={isCollapsed}
                            title={toggleLabel}
                            aria-label={toggleLabel}
                        >
                            <PanelLeft size={17} aria-hidden="true" />
                        </button>

                        <button
                            ref={mobileCloseRef}
                            type="button"
                            onClick={() => closeMobileMenu(true)}
                            className="app-sidebar-mobile-close"
                            aria-label="Fermer le menu latéral"
                        >
                            <X size={17} aria-hidden="true" />
                        </button>
                    </div>

                    {/* Corps du menu latéral : Zone 1 (Principal) & Zone 2 (Raccourcis pastilles) */}
                    <div id="app-sidebar-body" className="app-sidebar-body">
                        <nav className="app-sidebar-nav-list" aria-label="Navigation principale">
                            {primaryNav.map((item) => {
                                const isDisabled = Boolean(item.disabled || !item.href);

                                if (isDisabled) {
                                    return (
                                        <a
                                            key={item.key}
                                            role="link"
                                            aria-disabled="true"
                                            className="app-sidebar-link is-disabled"
                                            title={item.title || item.label}
                                        >
                                            <span className="app-sidebar-link-icon" aria-hidden="true">
                                                {item.icon}
                                            </span>
                                            <span className={effectiveCollapsed ? 'sr-only' : 'app-sidebar-link-text'}>
                                                <span className="app-sidebar-link-label">
                                                    {item.label}
                                                    {effectiveCollapsed && item.badge ? ` (${item.badge})` : ''}
                                                </span>
                                                {!effectiveCollapsed && item.badge && (
                                                    <span className="badge-coming-soon">{item.badge}</span>
                                                )}
                                            </span>
                                        </a>
                                    );
                                }

                                return (
                                    <Link
                                        key={item.key}
                                        href={item.href}
                                        className={`app-sidebar-link ${item.isActive ? 'is-active' : ''}`}
                                        aria-current={item.isActive ? 'page' : undefined}
                                        title={item.title || item.label}
                                        onClick={handleNavLinkClick}
                                    >
                                        <span className="app-sidebar-link-icon" aria-hidden="true">
                                            {item.icon}
                                        </span>
                                        <span className={effectiveCollapsed ? 'sr-only' : 'app-sidebar-link-text'}>
                                            <span className="app-sidebar-link-label">{item.label}</span>
                                            {!effectiveCollapsed && item.badge && (
                                                <span className="badge-coming-soon">{item.badge}</span>
                                            )}
                                        </span>
                                    </Link>
                                );
                            })}
                        </nav>

                        {quickSection && quickSection.items?.length > 0 && (
                            <div className="app-sidebar-section">
                                {!effectiveCollapsed && (
                                    <div className="app-sidebar-section-header">
                                        <span className="app-sidebar-section-title">
                                            {quickSection.title}
                                        </span>
                                        {quickSection.addAction && (
                                            <Link
                                                href={quickSection.addAction.href}
                                                className="app-sidebar-section-add"
                                                title={quickSection.addAction.label}
                                                aria-label={quickSection.addAction.label}
                                                onClick={handleNavLinkClick}
                                            >
                                                <Plus size={14} aria-hidden="true" />
                                            </Link>
                                        )}
                                    </div>
                                )}

                                <nav className="app-sidebar-nav-list" aria-label={quickSection.title}>
                                    {quickSection.items.map((item) => (
                                        <Link
                                            key={item.key}
                                            href={item.href}
                                            className={`app-sidebar-link app-sidebar-quick-link ${item.isActive ? 'is-active' : ''}`}
                                            aria-current={item.isActive ? 'page' : undefined}
                                            title={item.label}
                                            onClick={handleNavLinkClick}
                                        >
                                            <span
                                                className={`app-sidebar-dot ${item.dotClass}`}
                                                aria-hidden="true"
                                            />
                                            {effectiveCollapsed && (
                                                <span className="app-sidebar-quick-code" aria-hidden="true">
                                                    {item.shortCode || item.label.charAt(0)}
                                                </span>
                                            )}
                                            <span className={effectiveCollapsed ? 'sr-only' : 'app-sidebar-link-label'}>
                                                {item.label}
                                            </span>
                                        </Link>
                                    ))}
                                </nav>
                            </div>
                        )}
                    </div>

                    {/* Zone 3 : Pied de menu ancré en bas */}
                    {footerItems.length > 0 && (
                        <div className="app-sidebar-footer">
                            {footerNavLinks.length > 0 && (
                                <nav className="app-sidebar-footer-nav" aria-label="Outils complémentaires">
                                    {footerNavLinks.map((item) => (
                                        <Link
                                            key={item.key}
                                            href={item.href}
                                            className={`app-sidebar-link ${item.isActive ? 'is-active' : ''} ${item.danger ? 'is-danger' : ''}`}
                                            aria-current={item.isActive ? 'page' : undefined}
                                            title={item.label}
                                            onClick={handleNavLinkClick}
                                        >
                                            <span className="app-sidebar-link-icon" aria-hidden="true">
                                                {item.icon}
                                            </span>
                                            <span className={effectiveCollapsed ? 'sr-only' : 'app-sidebar-link-label'}>
                                                {item.label}
                                            </span>
                                        </Link>
                                    ))}
                                </nav>
                            )}

                            {footerActions.map((item) => (
                                <Link
                                    key={item.key}
                                    href={item.href}
                                    method="post"
                                    as={item.as || 'button'}
                                    className={`app-sidebar-link app-sidebar-footer-btn ${item.danger ? 'is-danger' : ''}`}
                                    title={item.label}
                                >
                                    <span className="app-sidebar-link-icon" aria-hidden="true">
                                        {item.icon}
                                    </span>
                                    <span className={effectiveCollapsed ? 'sr-only' : 'app-sidebar-link-label'}>
                                        {item.label}
                                    </span>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

                {/* Colonne de droite : Barre supérieure légère + Contenu principal */}
                <div className="app-main-column" inert={isMainInert ? true : undefined}>
                    <header className="app-topbar">
                        <div className="app-topbar-left">
                            <button
                                ref={mobileTriggerRef}
                                type="button"
                                onClick={openMobileMenu}
                                className="app-mobile-menu-btn"
                                aria-expanded={isMobileOpen}
                                aria-controls="app-sidebar-navigation"
                                aria-label="Ouvrir le menu de navigation"
                            >
                                <Menu size={18} aria-hidden="true" />
                            </button>

                            {topbarContext && (
                                <span className="app-topbar-context">{topbarContext}</span>
                            )}
                        </div>

                        <div className="app-topbar-right">
                            {topbarAction && (
                                <Link
                                    href={topbarAction.href}
                                    className="app-topbar-cta"
                                    title={topbarAction.label}
                                    aria-label={topbarAction.label}
                                >
                                    {topbarAction.icon && (
                                        <span className="app-sidebar-link-icon" aria-hidden="true">
                                            {topbarAction.icon}
                                        </span>
                                    )}
                                    <span className="app-topbar-cta-label">{topbarAction.label}</span>
                                </Link>
                            )}

                            {topbarRight}
                        </div>
                    </header>

                    <main id="main-content" tabIndex={-1} className="app-main-content">
                        {children}
                    </main>
                </div>
            </div>
        </>
    );
}
