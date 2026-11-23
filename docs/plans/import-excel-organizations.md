# Spécification — Import d'entreprises par fichier Excel (`.xlsx` / `.csv`)

## 1. Format du fichier & Colonnes supportées

- **Formats acceptés** : `.xlsx` et `.csv` (séparateurs `,` ou `;` auto-détectés).
- **Taille maximale** : 5 Mo (`5120` Ko).
- **Nombre maximal de lignes** : 1 000 lignes de données par import synchrone.
- **Ligne d'en-tête** : ligne 1 obligatoire. Les noms de colonnes sont normalisés (minuscules, sans accents, espaces/tirets convertis en `_`).

| Champ interne | Obligatoire | Alias acceptés (FR / EN) | Contraintes |
|---|---|---|---|
| `name` | Oui | `name`, `nom`, `raison_sociale`, `entreprise`, `societe` | Chaîne ≤ 255 car. |
| `tax_number` | Oui | `tax_number`, `nif`, `rccm`, `numero_fiscal`, `compte_contribuable`, `ncc` | Chaîne ≤ 50 car., unique |
| `email` | Oui | `email`, `e_mail`, `mail`, `adresse_email`, `courriel` | Email valide ≤ 255 car., unique |
| `phone` | Non | `phone`, `telephone`, `tel`, `contact` | Chaîne ≤ 30 car. |
| `address` | Non | `address`, `adresse`, `siege`, `localisation` | Chaîne ≤ 500 car. |
| `is_active` | Non | `is_active`, `actif`, `active`, `statut`, `status` | Booléen (`1/true/oui/yes/actif/active` ou `0/false/non/no/inactif/suspendue`), défaut `true` |

## 2. Modes d'importation

- **`skip_errors` (défaut)** : valide toutes les lignes, ignore les lignes invalides ou en doublon (base de données et intra-fichier), insère les lignes valides en lot sous transaction atomique et retourne un rapport ligne par ligne.
- **`strict`** : valide toutes les lignes ; si une seule erreur ou un seul doublon est détecté, annule intégralement l'insertion (`aborted = true`, `createdCount = 0`) et affiche le rapport complet des erreurs.

## 3. Architecture SOLID

- **Contrats** : `OrganizationSpreadsheetReaderInterface`, `OrganizationTemplateGeneratorInterface`.
- **Adaptateurs OpenSpout** : `OpenSpoutOrganizationReader`, `OpenSpoutOrganizationTemplateGenerator`.
- **DTOs immutables** : `ImportOrganizationRowDTO`, `ImportOrganizationsResultDTO`.
- **Repository** : `findExistingIdentifiers()` (1 seule requête, 0 N+1) et `createMany()` (insertion par lots de 200).
- **Action** : `ImportOrganizationsAction` (normalisation, validation, détection doublons, transaction atomique, invalidation `admin.dashboard.stats`).
- **Sécurité** : middleware `['auth', 'admin']`, `ImportOrganizationsRequest`, Rate Limiter `web-admin-import` (5 requêtes / min / utilisateur).
