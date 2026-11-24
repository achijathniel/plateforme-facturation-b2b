import { usePage } from '@inertiajs/react';
import {
    Briefcase,
    Building2,
    FileSpreadsheet,
    FileText,
    LayoutDashboard,
    LogOut,
    Plus,
} from 'lucide-react';
import AppSidebarLayout from '../Components/Layout/AppSidebarLayout';
import UserControls from '../Components/UserControls';
import { buildInvoiceStatusQuickItems, parseInertiaUrl } from '../Utils/navigationHelpers';

/**
 * Layout principal pour les vues de l'espace administration.
 * Délègue le menu latéral pleine hauteur au composant partagé AppSidebarLayout.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string } },
 *     title?: string,
 *     children: React.ReactNode
 * }} props
 */
export default function AdminLayout({ auth, title, children }) {
    const { url } = usePage();
    const { pathname, currentStatus } = parseInertiaUrl(url);

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
            isActive:
                pathname.startsWith('/admin/organizations') &&
                !pathname.startsWith('/admin/organizations/import'),
        },
        {
            key: 'admin-invoices',
            label: 'Factures',
            href: '/admin/invoices',
            icon: <FileText size={17} aria-hidden="true" />,
            isActive: pathname.startsWith('/admin/invoices') && !currentStatus,
        },
    ];

    const quickSection = {
        title: 'Suivi factures',
        items: buildInvoiceStatusQuickItems({
            keyPrefix: 'admin-invoices',
            basePath: '/admin/invoices',
            pathname,
            currentStatus,
            statuses: ['paid', 'sent', 'overdue', 'draft'],
        }),
    };

    const footerItems = [
        {
            key: 'admin-import',
            label: 'Importer Excel',
            href: '/admin/organizations/import',
            icon: <FileSpreadsheet size={16} aria-hidden="true" />,
            isActive: pathname.startsWith('/admin/organizations/import'),
        },
        {
            key: 'admin-logout',
            label: 'Déconnexion',
            href: '/admin/logout',
            method: 'post',
            as: 'button',
            icon: <LogOut size={16} aria-hidden="true" />,
            danger: true,
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
            quickSection={quickSection}
            footerItems={footerItems}
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
