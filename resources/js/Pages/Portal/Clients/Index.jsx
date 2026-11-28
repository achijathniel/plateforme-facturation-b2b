import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import PortalLayout from '../../../Layouts/PortalLayout';
import ClientFiltersBar from '../../../Components/Clients/ClientFiltersBar';
import ClientsTable from '../../../Components/Clients/ClientsTable';
import Pagination from '../../../Components/Pagination';
import useInertiaFilters from '../../../Hooks/useInertiaFilters';
import { archiveClient } from '../../../Utils/archiveClient';

/**
 * Annuaire des clients B2B gérés par l'entreprise dans le portail comptable.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     clients: { data: Array<any>, links: Array<any>, from: number, to: number, total: number },
 *     filters?: { search?: string, sort_by?: string, sort_direction?: string, per_page?: number },
 *     canManageClients?: boolean
 * }} props
 */
export default function Index({ auth, organization, clients, filters = {}, canManageClients = false }) {
    const { values, setField, setFields, submitFilters, resetFilters, hasActiveFilters } = useInertiaFilters({
        routeUrl: '/portal/clients',
        initialFilters: filters,
        defaultValues: { search: '', sort_by: 'name', sort_direction: 'asc' },
    });

    const handleSortChange = (nextSort) => {
        const [sortBy, sortDirection] = nextSort.split(':');
        setFields({ sort_by: sortBy, sort_direction: sortDirection });
    };

    return (
        <PortalLayout auth={auth} organization={organization} title="Mes Clients">
            <div className="clients-scope">
                <div className="admin-page-header portal-page-header-row">
                    <div>
                        <h1 className="portal-page-title">Mes Clients</h1>
                        <p className="portal-page-subtitle">
                            Carnet d'adresses B2B et suivi du chiffre d'affaires client pour{' '}
                            <strong>{organization?.name}</strong>
                        </p>
                    </div>
                    {canManageClients && (
                        <Link href="/portal/clients/create" className="btn-primary" style={{ textDecoration: 'none' }}>
                            <Plus size={16} aria-hidden="true" />
                            <span>Nouveau client</span>
                        </Link>
                    )}
                </div>

                <ClientFiltersBar
                    search={values.search}
                    sortValue={`${values.sort_by}:${values.sort_direction}`}
                    hasActiveFilters={hasActiveFilters}
                    onSearchChange={(val) => setField('search', val, false)}
                    onSortChange={handleSortChange}
                    onSubmit={submitFilters}
                    onReset={resetFilters}
                />

                <div className="data-card">
                    <div className="data-card-header">
                        <div className="data-card-title-group">
                            <h2 className="data-card-title">Annuaire des entreprises clientes</h2>
                            <p className="data-card-subtitle" role="status" aria-live="polite">
                                {clients?.total || 0} client(s) actif(s)
                                {hasActiveFilters && ' (filtrés)'}
                            </p>
                        </div>
                    </div>

                    <ClientsTable
                        clients={clients?.data || []}
                        canManage={canManageClients}
                        hasActiveFilters={hasActiveFilters}
                        onResetFilters={resetFilters}
                        onArchive={(client) => archiveClient(client, { preserveScroll: true })}
                    />

                    {clients?.total > 0 && clients?.links && (
                        <div className="data-card-footer">
                            <Pagination
                                links={clients.links}
                                from={clients.from}
                                to={clients.to}
                                total={clients.total}
                                itemLabel="clients"
                            />
                        </div>
                    )}
                </div>
            </div>
        </PortalLayout>
    );
}
