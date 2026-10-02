import { useState, useEffect } from 'react';

/**
 * Hook personnalisé pour différer la prise en compte d'une valeur (debouncing).
 * Idéal pour les champs de recherche en temps réel afin d'éviter le martèlement du serveur.
 *
 * @template T
 * @param {T} value - Valeur observée
 * @param {number} delay - Délai d'attente en millisecondes (300 ms par défaut)
 * @returns {T} Valeur stabilisée après expiration du délai
 */
export default function useDebounce(value, delay = 300) {
    const [debouncedValue, setDebouncedValue] = useState(value);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);

    return debouncedValue;
}
