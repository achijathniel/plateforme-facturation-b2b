import { BarChart3, CheckCircle2, Clock, Wallet } from 'lucide-react';
import AdminLayout from '../../Layouts/AdminLayout';
import DashboardPageView from '../../Components/Dashboard/DashboardPageView';
import { formatCurrency, formatNumber } from '../../Utils/formatters';

export default function Dashboard({ auth, stats, filters = {}, organizations = [], statuses = [] }) {
    const kpis = [
        {
            key: 'billed',
            label: "Chiffre d'Affaires Facturé",
            value: formatCurrency(stats?.total_billed),
            currency: 'XOF',
            icon: <Wallet size={18} aria-hidden="true" />,
            subtext: 'Total émis sur la sélection',
            highlightBlue: true,
        },
        {
            key: 'paid',
            label: 'Recouvrement Encaissé',
            value: formatCurrency(stats?.total_paid),
            currency: 'XOF',
            icon: <CheckCircle2 size={18} aria-hidden="true" />,
            subtext: `${stats?.collection_rate ?? 0} % du montant facturé`,
            valueColor: 'var(--green-text)',
        },
        {
            key: 'overdue',
            label: 'Montant en Retard',
            value: formatCurrency(stats?.total_overdue),
            currency: 'XOF',
            icon: <Clock size={18} aria-hidden="true" />,
            subtext: `${formatNumber(stats?.overdue_count)} facture(s) échue(s)`,
            valueColor: 'var(--rose-text)',
        },
        {
            key: 'volume',
            label: "Volume d'Activité",
            value: formatNumber(stats?.total_invoices_count),
            icon: <BarChart3 size={18} aria-hidden="true" />,
            subtext: `Moy. ${formatCurrency(stats?.average_invoice_amount, 'XOF')} · ${formatNumber(stats?.total_organizations_count)} org.`,
            highlightBlue: true,
        },
    ];

    return (
        <AdminLayout auth={auth} title="Tableau de bord - Administration">
            <DashboardPageView
                routeUrl="/admin/dashboard"
                headingTitle="Tableau de bord financier"
                headingSubtitle={`Bienvenue, ${auth?.user?.name}. Analysez le chiffre d'affaires, les encaissements et la performance par entreprise en temps réel.`}
                stats={stats}
                filters={filters}
                statuses={statuses}
                organizations={organizations}
                showOrganizationFilter={true}
                kpis={kpis}
                topEntitiesTitle="Top 5 des entreprises par chiffre d'affaires"
                topEntitiesSubtitle="Cliquez sur une entreprise pour filtrer l'ensemble du tableau de bord"
                enableEntityFilter={true}
                recentInvoicesSubtitle="Les 6 dernières factures correspondant aux filtres actifs"
                recentInvoicesPartyLabel="Entreprise Cliente"
                recentInvoicesAllUrl="/admin/invoices"
                recentInvoicesAllLabel="Voir toutes les factures →"
            />
        </AdminLayout>
    );
}
