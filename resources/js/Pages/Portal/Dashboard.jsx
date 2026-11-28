import { AlertTriangle, CheckCircle2, CreditCard, FileText } from 'lucide-react';
import PortalLayout from '../../Layouts/PortalLayout';
import DashboardPageView from '../../Components/Dashboard/DashboardPageView';
import { formatCurrency, formatNumber } from '../../Utils/formatters';

export default function Dashboard({ auth, organization, stats, filters = {}, statuses = [] }) {
    const orgName = organization?.name || auth?.user?.organization?.name || 'Votre entreprise';

    const kpis = [
        {
            key: 'billed',
            label: 'Total Facturé',
            value: formatCurrency(stats?.total_billed),
            currency: 'XOF',
            icon: <CreditCard size={18} aria-hidden="true" />,
            subtext: 'Cumul sur la sélection',
        },
        {
            key: 'paid',
            label: 'Total Réglé',
            value: formatCurrency(stats?.total_paid),
            currency: 'XOF',
            icon: <CheckCircle2 size={18} aria-hidden="true" />,
            subtext: `${stats?.collection_rate ?? 0} % encaissé`,
            valueColor: 'var(--green-text)',
        },
        {
            key: 'overdue',
            label: 'Reste à Payer / Échu',
            value: formatCurrency(stats?.total_overdue),
            currency: 'XOF',
            icon: <AlertTriangle size={18} aria-hidden="true" />,
            subtext: `${formatNumber(stats?.overdue_count)} facture(s) échue(s)`,
            valueColor: 'var(--rose-text)',
        },
        {
            key: 'volume',
            label: 'Factures Enregistrées',
            value: formatNumber(stats?.total_invoices_count),
            icon: <FileText size={18} aria-hidden="true" />,
            subtext: `Panier moyen : ${formatCurrency(stats?.average_invoice_amount, 'XOF')}`,
        },
    ];

    return (
        <PortalLayout auth={auth} organization={organization} title={`Tableau de bord - ${orgName}`}>
            <DashboardPageView
                routeUrl="/portal-dashboard"
                headingTitle={`Tableau de bord — ${orgName}`}
                headingSubtitle="Suivez en temps réel votre chiffre d'affaires facturé, vos règlements encaissés et l'évolution de vos principaux clients B2B."
                stats={stats}
                filters={filters}
                statuses={statuses}
                kpis={kpis}
                topEntitiesTitle="Top 5 de vos clients B2B"
                topEntitiesSubtitle="Classement des clients selon le volume facturé sur la période"
                recentInvoicesSubtitle="Historique des 6 dernières factures correspondant aux filtres actifs."
                recentInvoicesPartyLabel="Client"
                recentInvoicesAllUrl="/portal/invoices"
                recentInvoicesAllLabel="Voir toutes mes factures →"
                recentInvoicesLinkInvoices={true}
            />
        </PortalLayout>
    );
}
