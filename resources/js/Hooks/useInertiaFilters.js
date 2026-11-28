import { useState, useEffect, useRef, useCallback } from 'react';
import { router } from '@inertiajs/react';
import useDebounce from './useDebounce';

/**
 * Hook partagé de filtrage et recherche debouncée avec synchronisation Inertia.
 * Évite la duplication entre les tableaux de bord / listes Admin et Portail.
 *
 * @param {{
 *     routeUrl: string,
 *     initialFilters?: Record<string, any>,
 *     defaultValues?: Record<string, any>,
 *     searchKey?: string,
 *     debounceMs?: number
 * }} config
 */
export default function useInertiaFilters({
    routeUrl,
    initialFilters = {},
    defaultValues = {},
    searchKey = 'search',
    debounceMs = 350,
}) {
    const defaultsRef = useRef(defaultValues);

    const normalizeFromProps = useCallback((source) => {
        const next = {};
        Object.keys(defaultsRef.current).forEach((key) => {
            const raw = source?.[key];
            next[key] = raw === null || raw === undefined || raw === ''
                ? defaultsRef.current[key]
                : String(raw);
        });
        return next;
    }, []);

    const [values, setValues] = useState(() => normalizeFromProps(initialFilters));
    const valuesRef = useRef(values);
    const isFirstRender = useRef(true);
    const initialSearch = normalizeFromProps(initialFilters)[searchKey] ?? '';
    const lastSubmittedSearchRef = useRef(initialSearch);
    const debouncedSearch = useDebounce(values[searchKey] ?? '', debounceMs);

    const initialKey = JSON.stringify(
        Object.keys(defaultsRef.current).reduce((acc, k) => {
            acc[k] = initialFilters?.[k] ?? defaultsRef.current[k];
            return acc;
        }, {})
    );

    useEffect(() => {
        const synced = normalizeFromProps(initialFilters);
        valuesRef.current = synced;
        lastSubmittedSearchRef.current = synced[searchKey] ?? '';
        setValues(synced);
        // initialKey sérialise les valeurs scalaires de initialFilters pour éviter les boucles de rendu liées aux nouvelles références Inertia.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [initialKey, normalizeFromProps, searchKey]);

    const navigateWith = useCallback((nextValues) => {
        const query = {};
        Object.keys(defaultsRef.current).forEach((key) => {
            const val = nextValues[key];
            const def = defaultsRef.current[key];
            if (val !== undefined && val !== null && val !== '' && val !== def) {
                query[key] = val;
            }
        });

        router.get(routeUrl, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, [routeUrl]);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        if (debouncedSearch === lastSubmittedSearchRef.current) {
            return;
        }

        const currentInput = valuesRef.current[searchKey] ?? '';
        if (debouncedSearch !== currentInput) {
            return;
        }

        lastSubmittedSearchRef.current = debouncedSearch;
        navigateWith({
            ...valuesRef.current,
            [searchKey]: debouncedSearch,
        });
    }, [debouncedSearch, searchKey, navigateWith]);

    const setField = useCallback((key, value, immediate = true) => {
        const next = { ...valuesRef.current, [key]: value };
        valuesRef.current = next;
        setValues(next);

        if (immediate) {
            if (key === searchKey) {
                lastSubmittedSearchRef.current = value ?? '';
            }
            navigateWith(next);
        }
    }, [navigateWith, searchKey]);

    const submitFilters = useCallback((e) => {
        if (e) e.preventDefault();
        const current = valuesRef.current;
        lastSubmittedSearchRef.current = current[searchKey] ?? '';
        navigateWith(current);
    }, [navigateWith, searchKey]);

    const resetFilters = useCallback(() => {
        const resetState = { ...defaultsRef.current };
        valuesRef.current = resetState;
        lastSubmittedSearchRef.current = resetState[searchKey] ?? '';
        setValues(resetState);
        router.get(routeUrl, {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, [routeUrl, searchKey]);

    const hasActiveFilters = Object.keys(defaultsRef.current).some((key) => {
        const val = values[key];
        const def = defaultsRef.current[key];
        return val !== undefined && val !== null && val !== '' && val !== def;
    });

    return {
        values,
        setField,
        submitFilters,
        resetFilters,
        hasActiveFilters,
    };
}
