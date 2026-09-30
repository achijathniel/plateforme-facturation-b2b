import { Link } from '@inertiajs/react';

/**
 * Composant réutilisable et accessible de pagination pour les résultats Laravel / Inertia.
 *
 * @param {{
 *     links: Array<{ url: string|null, label: string, active: boolean }>,
 *     from?: number,
 *     to?: number,
 *     total?: number
 * }} props
 */
export default function Pagination({ links, from, to, total }) {
    if (!links || links.length <= 1) {
        return null;
    }

    /**
     * Traduit et nettoie les libellés de navigation par défaut de Laravel.
     */
    const cleanLabel = (label) => {
        if (label.includes('Previous') || label.includes('&laquo;')) {
            return '← Précédent';
        }
        if (label.includes('Next') || label.includes('&raquo;')) {
            return 'Suivant →';
        }
        return label;
    };

    return (
        <div className="pagination-wrapper" aria-label="Pagination des factures">
            {total !== undefined && (
                <p className="pagination-summary">
                    Affichage de <span className="pagination-number">{from || 0}</span> à{' '}
                    <span className="pagination-number">{to || 0}</span> sur{' '}
                    <span className="pagination-number">{total}</span> factures
                </p>
            )}

            <nav className="pagination-nav" aria-label="Navigation des pages">
                <ul className="pagination-list">
                    {links.map((link, index) => {
                        const isNavButton =
                            link.label.includes('Previous') ||
                            link.label.includes('Next') ||
                            link.label.includes('&laquo;') ||
                            link.label.includes('&raquo;');

                        return (
                            <li key={index} className="pagination-item">
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        preserveState
                                        className={`pagination-link ${link.active ? 'active' : ''} ${isNavButton ? 'nav-button' : ''}`}
                                        aria-current={link.active ? 'page' : undefined}
                                    >
                                        {cleanLabel(link.label)}
                                    </Link>
                                ) : (
                                    <span
                                        className={`pagination-link disabled ${isNavButton ? 'nav-button' : ''}`}
                                        aria-disabled="true"
                                    >
                                        {cleanLabel(link.label)}
                                    </span>
                                )}
                            </li>
                        );
                    })}
                </ul>
            </nav>
        </div>
    );
}
