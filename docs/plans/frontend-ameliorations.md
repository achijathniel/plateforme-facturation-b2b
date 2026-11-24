# Plan d'Amélioration Frontend (Natifs React 19 + CSS Modulaire)

## Objectif
Corriger les bugs CSS actifs, assurer la conformité **WCAG 2.2 AA**, découper le monolithe `InvoiceForm.jsx` et factoriser les duplications DRY dans le frontend sans ajouter de dépendance externe (100 % natif).

## Étapes

1. **Étape 1 — Correctifs CSS, variables fantômes & impression** :
   - Remplacement de `--blue-focus-ring`, `--bg-body`, `--bg-main` par les tokens officiels de `variables.css`.
   - Isolation de `.btn-primary { width: 100% }` sur `.auth-card` et du hover destructif rouge sur `.btn-logout` (`UserControls.jsx`).
   - Alignement des classes mortes de `InvoiceForm.jsx` et mise à jour des sélecteurs `@media print`.
2. **Étape 2 — Accessibilité WCAG 2.2 AA, focus & pagination** :
   - `:focus-visible` global, `scroll-padding-top: 80px`, `.skip-link`, `@media (prefers-reduced-motion: reduce)`.
   - `aria-current="page"` dans `AdminLayout.jsx`, initialisation sans double-render et correction du lien désactivé `Règlements` dans `PortalLayout.jsx`.
   - Suppression de `role="link"` sur `<tr>` dans les tableaux portail et ajout de `itemLabel` sur `Pagination.jsx`.
3. **Étape 3 — Découpage de `InvoiceForm.jsx` (774 → < 250 lignes)** :
   - Extraction de `ClientAutocomplete.jsx` (vérification `res.ok`, fermeture sur `Tab`, `aria-expanded` synchronisé, `aria-live="polite"`), `InvoiceItemsTable.jsx` et `InvoiceSummaryCard.jsx`.
4. **Étape 4 — Factorisation DRY** :
   - `Hooks/useInertiaFilters.js` pour les 3 pages `Index`.
   - `Utils/confirmAction.js` en import dynamique de `sweetalert2` (contrastes ≥ 4,5:1).
   - Unification de `formatDate` / `formatDateLong` dans `Utils/formatters.js`.
5. **Étape 5 — Tests d'architecture étendus & livraison Git** :
   - Ajout de tests automatisés dans `FrontendArchitectureTest.php` (variables CSS, Skinny Pages, 0 import statique `sweetalert2`), suite complète `php artisan test`, commit et push.
