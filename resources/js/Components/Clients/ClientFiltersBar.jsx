import { Search, X } from 'lucide-react';

/**
 * Barre de recherche et de tri partagée pour l'annuaire des clients B2B.
 *
 * @param {{
 *     search: string,
 *     sortValue: string,
 *     hasActiveFilters: boolean,
 *     onSearchChange: (val: string) => void,
 *     onSortChange: (val: string) => void,
 *     onSubmit: (e: React.FormEvent) => void,
 *     onReset: () => void
 * }} props
 */
export default function ClientFiltersBar({
    search,
    sortValue,
    hasActiveFilters,
    onSearchChange,
    onSortChange,
    onSubmit,
    onReset,
}) {
    return (
        <div className="filter-card">
            <form
                onSubmit={onSubmit}
                className="filter-form"
                role="search"
                aria-label="Filtrer les clients"
            >
                <div className="filter-search-group">
                    <label htmlFor="portal-clients-search" className="sr-only">
                        Rechercher un client par nom, email, NIF ou téléphone
                    </label>
                    <div className="search-input-wrapper">
                        <span className="search-icon" aria-hidden="true">
                            <Search size={15} aria-hidden="true" />
                        </span>
                        <input
                            id="portal-clients-search"
                            type="search"
                            name="search"
                            value={search}
                            onChange={(e) => onSearchChange(e.target.value)}
                            placeholder="Raison sociale, email, NIF, téléphone..."
                            className="filter-input"
                        />
                    </div>
                    <button type="submit" className="btn-filter-submit">
                        Rechercher
                    </button>
                </div>

                <div className="filter-select-group">
                    <label htmlFor="portal-clients-sort" className="filter-label">
                        Trier par :
                    </label>
                    <select
                        id="portal-clients-sort"
                        name="sort"
                        value={sortValue}
                        onChange={(e) => onSortChange(e.target.value)}
                        className="filter-select"
                    >
                        <option value="name:asc">Nom (A → Z)</option>
                        <option value="total_billed:desc">Plus facturés</option>
                        <option value="invoices_count:desc">Plus de factures</option>
                        <option value="created_at:desc">Plus récents</option>
                    </select>
                </div>

                {hasActiveFilters && (
                    <button
                        type="button"
                        onClick={onReset}
                        className="btn-reset-filter"
                        aria-label="Réinitialiser les filtres"
                    >
                        <X size={13} aria-hidden="true" />
                        <span>Réinitialiser</span>
                    </button>
                )}
            </form>
        </div>
    );
}
