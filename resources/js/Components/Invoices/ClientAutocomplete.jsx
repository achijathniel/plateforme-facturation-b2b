import { useState, useEffect, useRef } from 'react';
import { Check, Landmark, Mail, Phone } from 'lucide-react';
import useDebounce from '../../Hooks/useDebounce';

/**
 * Champ d'autocomplétion accessible (ARIA Combobox) pour rechercher et rattacher
 * un client B2B de l'annuaire ou en saisir un nouveau librement.
 *
 * @param {{
 *     clientId: number|null,
 *     clientName: string,
 *     error?: string,
 *     onChangeName: (value: string) => void,
 *     onSelectClient: (client: any) => void,
 *     onDetachClient: () => void
 * }} props
 */
export default function ClientAutocomplete({
    clientId,
    clientName,
    error,
    onChangeName,
    onSelectClient,
    onDetachClient,
}) {
    const [isSearchOpen, setIsSearchOpen] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const [highlightedIndex, setHighlightedIndex] = useState(-1);
    const [searchFailed, setSearchFailed] = useState(false);
    const dropdownRef = useRef(null);
    const inputRef = useRef(null);
    const optionRefs = useRef([]);
    const debouncedSearchTerm = useDebounce(clientName, 250);

    const showDropdown = isSearchOpen && Boolean(clientName && clientName.trim().length >= 2);

    useEffect(() => {
        if (!isSearchOpen || !debouncedSearchTerm || debouncedSearchTerm.trim().length < 2) {
            setSearchResults([]);
            setIsSearching(false);
            setHighlightedIndex(-1);
            setSearchFailed(false);
            return;
        }

        const controller = new AbortController();
        setIsSearching(true);
        setSearchFailed(false);

        fetch(`/portal/clients/search?q=${encodeURIComponent(debouncedSearchTerm.trim())}`, {
            signal: controller.signal,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((res) => {
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }
                return res.json();
            })
            .then((json) => {
                setSearchResults(json.clients || []);
                setIsSearching(false);
                setHighlightedIndex(-1);
            })
            .catch((err) => {
                if (err.name !== 'AbortError') {
                    setSearchResults([]);
                    setIsSearching(false);
                    setSearchFailed(true);
                }
            });

        return () => {
            controller.abort();
        };
    }, [debouncedSearchTerm, isSearchOpen]);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsSearchOpen(false);
                setHighlightedIndex(-1);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        if (highlightedIndex >= 0 && optionRefs.current[highlightedIndex]) {
            optionRefs.current[highlightedIndex].scrollIntoView({ block: 'nearest' });
        }
    }, [highlightedIndex]);

    const handleSelect = (client) => {
        onSelectClient(client);
        setIsSearchOpen(false);
        setSearchResults([]);
        setHighlightedIndex(-1);
    };

    const handleDetach = () => {
        onDetachClient();
        inputRef.current?.focus();
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Tab') {
            setIsSearchOpen(false);
            setHighlightedIndex(-1);
            return;
        }

        if (!showDropdown || searchResults.length === 0) {
            if (e.key === 'ArrowDown' && clientName?.trim().length >= 2) {
                setIsSearchOpen(true);
            } else if (e.key === 'Escape') {
                setIsSearchOpen(false);
                setHighlightedIndex(-1);
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlightedIndex((prev) => (prev < searchResults.length - 1 ? prev + 1 : 0));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlightedIndex((prev) => (prev > 0 ? prev - 1 : searchResults.length - 1));
        } else if (e.key === 'Home') {
            e.preventDefault();
            setHighlightedIndex(0);
        } else if (e.key === 'End') {
            e.preventDefault();
            setHighlightedIndex(searchResults.length - 1);
        } else if (e.key === 'Enter') {
            if (highlightedIndex >= 0 && searchResults[highlightedIndex]) {
                e.preventDefault();
                handleSelect(searchResults[highlightedIndex]);
            }
        } else if (e.key === 'Escape') {
            setIsSearchOpen(false);
            setHighlightedIndex(-1);
        }
    };

    const liveStatusMessage = !showDropdown
        ? ''
        : isSearching
            ? "Recherche dans l'annuaire..."
            : searchFailed
                ? "Impossible de charger l'annuaire client."
                : searchResults.length > 0
                    ? `${searchResults.length} client(s) trouvé(s). Utilisez les flèches pour naviguer.`
                    : `Aucun client trouvé pour ${clientName}.`;

    return (
        <div className="form-group">
            <label htmlFor="client_name" className="form-label required">
                Nom / Raison sociale de l'entreprise cliente
            </label>
            <div className="client-autocomplete-wrapper" ref={dropdownRef}>
                <input
                    ref={inputRef}
                    id="client_name"
                    name="client_name"
                    type="text"
                    placeholder="Ex: Société Ivoirienne de Négoce (tapez pour rechercher...)"
                    className={`form-input ${error ? 'input-error' : ''}`}
                    value={clientName}
                    onChange={(e) => {
                        onChangeName(e.target.value);
                        setIsSearchOpen(true);
                    }}
                    onFocus={() => {
                        if (clientName && clientName.trim().length >= 2) {
                            setIsSearchOpen(true);
                        }
                    }}
                    onKeyDown={handleKeyDown}
                    role="combobox"
                    aria-autocomplete="list"
                    aria-expanded={showDropdown}
                    aria-controls={showDropdown ? 'client-autocomplete-list' : undefined}
                    aria-activedescendant={
                        showDropdown && highlightedIndex >= 0 && searchResults[highlightedIndex]
                            ? `client-option-${searchResults[highlightedIndex].id}`
                            : undefined
                    }
                    autoComplete="off"
                    aria-invalid={!!error}
                    aria-describedby={error ? 'client-name-error' : undefined}
                    required
                />

                <div className="sr-only" aria-live="polite" aria-atomic="true">
                    {liveStatusMessage}
                </div>

                {clientId && (
                    <div className="client-badge-attached">
                        <span className="client-badge-attached-label">
                            <Check size={13} aria-hidden="true" />
                            <span>Client sélectionné dans l'annuaire</span>
                        </span>
                        <button
                            type="button"
                            onClick={handleDetach}
                            className="client-badge-detach"
                            title="Détacher le client pour modifier librement"
                        >
                            Détacher
                        </button>
                    </div>
                )}

                {showDropdown && (
                    <ul
                        id="client-autocomplete-list"
                        className="client-autocomplete-dropdown"
                        role="listbox"
                        aria-label="Suggestions de clients"
                    >
                        {isSearching ? (
                            <li className="client-autocomplete-empty" role="presentation">
                                Recherche dans l'annuaire...
                            </li>
                        ) : searchFailed ? (
                            <li className="client-autocomplete-empty" role="presentation">
                                Erreur lors de la recherche dans l'annuaire. Vous pouvez poursuivre la saisie libre.
                            </li>
                        ) : searchResults.length > 0 ? (
                            searchResults.map((client, idx) => (
                                <li
                                    ref={(el) => {
                                        optionRefs.current[idx] = el;
                                    }}
                                    id={`client-option-${client.id}`}
                                    key={client.id}
                                    role="option"
                                    aria-selected={highlightedIndex === idx}
                                    className={`client-autocomplete-item ${clientId === client.id ? 'selected' : ''} ${highlightedIndex === idx ? 'highlighted' : ''}`}
                                    onClick={() => handleSelect(client)}
                                    onMouseEnter={() => setHighlightedIndex(idx)}
                                >
                                    <div className="client-autocomplete-item-name">{client.name}</div>
                                    <div className="client-autocomplete-item-meta">
                                        {client.email && <span><Mail size={12} aria-hidden="true" />{client.email}</span>}
                                        {client.phone && <span><Phone size={12} aria-hidden="true" />{client.phone}</span>}
                                        {client.tax_number && <span><Landmark size={12} aria-hidden="true" />NIF: {client.tax_number}</span>}
                                    </div>
                                </li>
                            ))
                        ) : (
                            <li className="client-autocomplete-empty" role="presentation">
                                Aucun client trouvé pour « {clientName} ». Il sera automatiquement ajouté à votre annuaire lors de la création.
                            </li>
                        )}
                    </ul>
                )}
            </div>
            {error && (
                <span id="client-name-error" className="field-error-text" role="alert">
                    {error}
                </span>
            )}
        </div>
    );
}
