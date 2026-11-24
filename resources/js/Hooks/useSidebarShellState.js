import { useCallback, useEffect, useRef, useState } from 'react';

const MOBILE_BREAKPOINT_QUERY = '(max-width: 960px)';
const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Gère l'état du shell latéral (repli desktop persisté, détection responsive,
 * tiroir mobile accessible avec piège à focus et restitution du focus).
 *
 * @param {string} storageKey Clé localStorage propre à l'espace (Admin ou Portail)
 * @param {string} currentUrl URL Inertia courante pour fermer le tiroir après navigation
 */
export default function useSidebarShellState(storageKey, currentUrl) {
    const sidebarRef = useRef(null);
    const mobileTriggerRef = useRef(null);
    const mobileCloseRef = useRef(null);

    const [isCollapsed, setIsCollapsed] = useState(() => {
        if (typeof window === 'undefined') {
            return false;
        }
        try {
            return window.localStorage.getItem(storageKey) === 'true';
        } catch {
            return false;
        }
    });

    const [isMobileViewport, setIsMobileViewport] = useState(() => {
        if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
            return false;
        }
        return window.matchMedia(MOBILE_BREAKPOINT_QUERY).matches;
    });

    const [isMobileOpen, setIsMobileOpen] = useState(false);

    const toggleCollapse = useCallback(() => {
        setIsCollapsed((prev) => {
            const next = !prev;
            if (typeof window !== 'undefined') {
                try {
                    window.localStorage.setItem(storageKey, String(next));
                } catch {
                    // Mode navigation privée ou accès localStorage restreint
                }
            }
            return next;
        });
    }, [storageKey]);

    const openMobileMenu = useCallback(() => {
        setIsMobileOpen(true);
    }, []);

    const closeMobileMenu = useCallback((restoreFocus = true) => {
        setIsMobileOpen(false);
        if (restoreFocus) {
            mobileTriggerRef.current?.focus();
        }
    }, []);

    // Synchronisation responsive : ferme le tiroir si passage vers desktop (> 960px)
    useEffect(() => {
        if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
            return undefined;
        }

        const mediaQuery = window.matchMedia(MOBILE_BREAKPOINT_QUERY);
        const handleChange = (event) => {
            setIsMobileViewport(event.matches);
            if (!event.matches) {
                setIsMobileOpen(false);
            }
        };

        setIsMobileViewport(mediaQuery.matches);
        mediaQuery.addEventListener('change', handleChange);
        return () => mediaQuery.removeEventListener('change', handleChange);
    }, []);

    // Fermeture automatique après navigation Inertia
    useEffect(() => {
        setIsMobileOpen(false);
    }, [currentUrl]);

    // Gestion clavier du tiroir mobile : focus initial, Escape et focus trap (Tab/Shift+Tab)
    useEffect(() => {
        if (!isMobileOpen) {
            return undefined;
        }

        mobileCloseRef.current?.focus();

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeMobileMenu(true);
                return;
            }

            if (event.key !== 'Tab' || !sidebarRef.current) {
                return;
            }

            const focusables = Array.from(
                sidebarRef.current.querySelectorAll(FOCUSABLE_SELECTOR)
            ).filter((el) => el.offsetParent !== null);

            if (focusables.length === 0) {
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [isMobileOpen, closeMobileMenu]);

    return {
        sidebarRef,
        mobileTriggerRef,
        mobileCloseRef,
        isCollapsed,
        isMobileViewport,
        isMobileOpen,
        effectiveCollapsed: isCollapsed && !isMobileOpen && !isMobileViewport,
        toggleCollapse,
        openMobileMenu,
        closeMobileMenu,
    };
}
