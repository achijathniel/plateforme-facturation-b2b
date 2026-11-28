import { usePage } from '@inertiajs/react';
import {
    Building2,
    CreditCard,
    FileText,
    LayoutDashboard,
    Plus,
    Users,
} from 'lucide-react';
import AppSidebarLayout from '../Components/Layout/AppSidebarLayout';
import UserControls from '../Components/UserControls';
import { buildInvoiceStatusQuickItems, parseInertiaUrl } from '../Utils/navigationHelpers';

/**
 * Layout principal pour l'espace portail de l'entreprise cliente.
 * Délègue le menu latéral pleine hauteur au composant partagé AppSidebarLayout.
 *
 * @param {{
 *     auth: { user?: { name: string, email?: string, role?: string, organization?: { id: number, name: string }|null } },
 *     title?: string,
 *     organization?: { id: number, name: string },
 *     children: React.ReactNode
 * }} props
 */
export default function PortalLayout({ auth, title, organization, children }) {
    const { url } = usePage();
    const orgName = organization?.name || auth?.user?.organization?.name || 'Mon Entreprise';
    const { pathname, currentStatus } = parseInertiaUrl(url);

    const primaryNav = [
        {
            key: 'portal-dashboard',
            label: 'Tableau de bord',
            href: '/portal-dashboard',
            icon: <LayoutDashboard size={17} aria-hidden="true" />,
            isActive:
                pathname.startsWith('/portal-dashboard') ||
                pathname.startsWith('/portal/dashboard'),
        },
        {
            key: 'portal-invoices',
            label: 'Mes Factures',
            href: '/portal/invoices',
            icon: <FileText size={17} aria-hidden="true" />,
            isActive:
                pathname.startsWith('/portal/invoices') &&
                !pathname.includes('/create') &&
                !currentStatus,
        },
        {
            key: 'portal-clients',
            label: 'Mes Clients',
            href: '/portal/clients',
            icon: <Users size={17} aria-hidden="true" />,
            isActive: pathname.startsWith('/portal/clients'),
        },
        {
            key: 'portal-payments',
            label: 'Règlements',
            icon: <CreditCard size={17} aria-hidden="true" />,
            disabled: true,
            badge: 'Bientôt',
            title: 'Règlements & Paiements (Bientôt disponible)',
        },
    ];

    const quickSection = {
        title: 'Vues rapides',
        addAction: {
            href: '/portal/invoices/create',
            label: 'Créer une nouvelle facture',
        },
        items: buildInvoiceStatusQuickItems({
            keyPrefix: 'portal-invoices',
            basePath: '/portal/invoices',
            pathname,
            currentStatus,
            statuses: ['draft', 'sent', 'paid', 'overdue'],
        }),
    };

    return (
        <AppSidebarLayout
            title={title}
            storageKey="dealtoo_portal_sidebar_collapsed"
            sidebarAriaLabel="Menu latéral de comptabilité"
            brand={{
                icon: <Building2 size={16} aria-hidden="true" />,
                title: orgName,
                subtitle: 'Portail Entreprise',
                href: '/portal-dashboard',
            }}
            primaryNav={primaryNav}
            quickSection={quickSection}
            topbarContext={orgName}
            topbarAction={{
                href: '/portal/invoices/create',
                label: 'Nouvelle facture',
                icon: <Plus size={15} aria-hidden="true" />,
            }}
            topbarRight={<UserControls auth={auth} logoutUrl="/portal/logout" />}
        >
            {children}
        </AppSidebarLayout>
    );
}
