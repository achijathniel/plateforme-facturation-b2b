import { Link } from '@inertiajs/react';
import { Archive, Pencil, Plus } from 'lucide-react';
import PortalLayout from '../../../Layouts/PortalLayout';
import ClientDetailsView from '../../../Components/Clients/ClientDetailsView';
import { archiveClient } from '../../../Utils/archiveClient';

/**
 * Page de fiche 360° d'un client B2B dans le portail comptable.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     canManageClients?: boolean,
 *     client: Record<string, any>
 * }} props
 */
export default function Show({ auth, organization, client, canManageClients = false }) {
    return (
        <PortalLayout auth={auth} organization={organization} title={`Client : ${client.name}`}>
            <div className="clients-scope">
                <div className="admin-page-header portal-page-header-row">
                    <div>
                        <div className="breadcrumb-nav">
                            <Link href="/portal/clients" className="breadcrumb-link">
                                <span aria-hidden="true">← </span>Retour à mes clients
                            </Link>
                        </div>
                        <h1 className="portal-page-title portal-client-title-spaced">{client.name}</h1>
                        <p className="portal-page-subtitle">
                            Fiche client 360° et historique de facturation pour <strong>{organization?.name}</strong>
                        </p>
                    </div>

                    {canManageClients && (
                        <div className="portal-client-actions-row">
                            <Link
                                href={`/portal/invoices/create?client_id=${client.id}`}
                                className="btn-primary"
                                style={{ textDecoration: 'none' }}
                            >
                                <Plus size={15} aria-hidden="true" />
                                <span>Nouvelle facture</span>
                            </Link>
                            <Link
                                href={`/portal/clients/${client.id}/edit`}
                                className="btn-secondary"
                                style={{ textDecoration: 'none' }}
                                aria-label={`Modifier les informations de ${client.name}`}
                            >
                                <Pencil size={14} aria-hidden="true" />
                                <span>Modifier</span>
                            </Link>
                            <button
                                type="button"
                                onClick={() => archiveClient(client)}
                                className="btn-danger-outline"
                                aria-label={`Archiver ${client.name}`}
                            >
                                <Archive size={14} aria-hidden="true" />
                                <span>Archiver</span>
                            </button>
                        </div>
                    )}
                </div>

                <ClientDetailsView client={client} />
            </div>
        </PortalLayout>
    );
}
