import { Link } from '@inertiajs/react';

/**
 * Bloc réutilisable d'affichage du profil connecté et du bouton de déconnexion.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string } },
 *     logoutUrl: string
 * }} props
 */
export default function UserControls({ auth, logoutUrl }) {
    return (
        <div className="admin-user-controls">
            <div className="admin-profile">
                <p className="admin-user-name">{auth?.user?.name}</p>
                <p className="admin-user-badge">
                    <span className="admin-status-dot" aria-hidden="true">●</span>{' '}
                    <span>{auth?.user?.role?.toUpperCase()}</span>
                </p>
            </div>

            <Link
                href={logoutUrl}
                method="post"
                as="button"
                className="btn-secondary"
            >
                Déconnexion
            </Link>
        </div>
    );
}
