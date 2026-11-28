import { usePage } from '@inertiajs/react';
import {
    Briefcase,
    Building2,
    FileText,
    LayoutDashboard,
    Plus,
} from 'lucide-react';
import AppSidebarLayout from '../Components/Layout/AppSidebarLayout';
import UserControls from '../Components/UserControls';
import { parseInertiaUrl } from '../Utils/navigationHelpers';

/**
 * Layout principal pour les vues de l'espace administration.
 * Délègue le menu latéral pleine hauteur au composant partagé AppSidebarLayout.
 *
 * @param {{
 *     auth: { user?: { name: string, email?: string, role?: string, organization?: { id: number, name: string }|null } },
 *     title?: string,
 *     children: React.ReactNode
 * }} props
 */
export default function AdminLayout({ auth, title, children }) {
    const { url } = usePage();
    const { pathname } = parseInertiaUrl(url);

    const primaryNav = [
        {
            key: 'admin-dashboard',
            label: 'Tableau de bord',
            href: '/admin/dashboard',
            icon: <LayoutDashboard size={17} aria-hidden="true" />,
            isActive: pathname === '/admin/dashboard',
        },
        {
            key: 'admin-organizations',
            label: 'Entreprises',
            href: '/admin/organizations',
            icon: <Building2 size={17} aria-hidden="true" />,
            isActive: pathname.startsWith('/admin/organizations'),
        },
        {
            key: 'admin-invoices',
            label: 'Factures',
            href: '/admin/invoices',
            icon: <FileText size={17} aria-hidden="true" />,
            isActive: pathname.startsWith('/admin/invoices'),
        },
    ];

    return (
        <AppSidebarLayout
            title={title}
            storageKey="dealtoo_admin_sidebar_collapsed"
            sidebarAriaLabel="Menu latéral d'administration"
            brand={{
                icon: <Briefcase size={16} aria-hidden="true" />,
                title: 'Dealtoo Billing',
                subtitle: 'Administration',
                href: '/admin/dashboard',
            }}
            primaryNav={primaryNav}
            topbarContext="Espace Administrateur"
            topbarAction={{
                href: '/admin/organizations/create',
                label: 'Nouvelle entreprise',
                icon: <Plus size={15} aria-hidden="true" />,
            }}
            topbarRight={<UserControls auth={auth} logoutUrl="/admin/logout" />}
        >
            {children}
        </AppSidebarLayout>
    );
}
