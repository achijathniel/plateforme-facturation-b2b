import { useEffect, useId, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Building2, LogOut, Mail, UserRound } from 'lucide-react';

/**
 * Bouton profil circulaire avec panneau déroulant accessible affichant les informations
 * de l'utilisateur connecté (nom, rôle, email, entreprise) et le bouton de déconnexion.
 *
 * @param {{
 *     auth: {
 *         user?: {
 *             name: string,
 *             email?: string,
 *             role?: string,
 *             organization?: { id: number, name: string }|null
 *         }
 *     },
 *     logoutUrl: string
 * }} props
 */
export default function UserControls({ auth, logoutUrl }) {
    const { url } = usePage();
    const user = auth?.user;
    const popoverId = useId();
    const containerRef = useRef(null);
    const triggerRef = useRef(null);
    const [isOpen, setIsOpen] = useState(false);

    useEffect(() => {
        setIsOpen(false);
    }, [url]);

    useEffect(() => {
        if (!isOpen) {
            return undefined;
        }

        const handlePointerDown = (event) => {
            if (containerRef.current && !containerRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        const handleFocusIn = (event) => {
            if (containerRef.current && !containerRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                setIsOpen(false);
                triggerRef.current?.focus();
            }
        };

        document.addEventListener('pointerdown', handlePointerDown);
        document.addEventListener('focusin', handleFocusIn);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('pointerdown', handlePointerDown);
            document.removeEventListener('focusin', handleFocusIn);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen]);

    const displayName = user?.name || 'Utilisateur';
    const roleLabel = user?.role ? user.role.toUpperCase() : 'MEMBRE';
    const hasDetails = Boolean(user?.email || user?.organization?.name);

    return (
        <div className="user-menu" ref={containerRef}>
            <button
                ref={triggerRef}
                type="button"
                className="user-menu-trigger"
                aria-expanded={isOpen}
                aria-controls={isOpen ? popoverId : undefined}
                aria-label={isOpen ? 'Fermer les informations du profil' : 'Ouvrir les informations du profil'}
                title={displayName}
                onClick={() => setIsOpen((prev) => !prev)}
            >
                <UserRound size={18} aria-hidden="true" />
                <span className="user-menu-online-dot" aria-hidden="true" />
            </button>

            {isOpen && (
                <div
                    id={popoverId}
                    className="user-menu-popover"
                    role="region"
                    aria-label="Informations du profil connecté"
                >
                    <div className="user-menu-header">
                        <div className="user-menu-avatar" aria-hidden="true">
                            <UserRound size={20} aria-hidden="true" />
                        </div>
                        <div className="user-menu-identity">
                            <p className="user-menu-name">{displayName}</p>
                            <p className="user-menu-role">
                                <span className="user-menu-role-dot" aria-hidden="true" />
                                <span>{roleLabel}</span>
                            </p>
                        </div>
                    </div>

                    {hasDetails && (
                        <div className="user-menu-details">
                            {user?.email && (
                                <div className="user-menu-detail-row" title={user.email}>
                                    <Mail size={14} aria-hidden="true" />
                                    <span className="user-menu-detail-text">{user.email}</span>
                                </div>
                            )}
                            {user?.organization?.name && (
                                <div className="user-menu-detail-row" title={user.organization.name}>
                                    <Building2 size={14} aria-hidden="true" />
                                    <span className="user-menu-detail-text">{user.organization.name}</span>
                                </div>
                            )}
                        </div>
                    )}

                    <div className="user-menu-footer">
                        <Link
                            href={logoutUrl}
                            method="post"
                            as="button"
                            className="user-menu-logout"
                            onClick={() => setIsOpen(false)}
                        >
                            <LogOut size={15} aria-hidden="true" />
                            <span>Déconnexion</span>
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
