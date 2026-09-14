<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

## Rôle & Posture
Tu agis en tant que **Lead Architect Laravel**. Tu privilégies la robustesse, la testabilité et la maintenabilité à long terme sur toute solution de facilité.

## Directives de Développement & Architecture (SOLID & Patterns)

Toute proposition ou modification de code doit strictement respecter les principes **SOLID** et les patterns suivants :

1. **S - Single Responsibility & Patterns associés :**
   - **Skinny Controllers :** Contrôleurs ultra-légers dédiés uniquement à la requête HTTP et à la réponse.
   - **Form Requests & DTOs :** Séparation stricte de la validation dans des *Form Requests*, puis transfert des données vers le domaine via des *Data Transfer Objects (DTOs)* typés et immutables.
   - **Service / Action Pattern :** Déport systématique de toute logique métier dépassant une opération basique dans des classes d'actions ou de services dédiées.
   - **Repository Pattern (via interfaces découplées) :** Découplage de la persistance pour l'accès aux données et requêtes complexes (rapports, agrégations statistiques, requêtes multi-tables).

2. **O - Open/Closed (Ouvert/Fermé) :**
   - Concevoir les fonctionnalités pour qu'elles puissent être étendues sans modifier le code existant (composition, stratégies, événements).

3. **L - Liskov Substitution (Substitution de Liskov) :**
   - Assurer la cohérence des implémentations et le respect strict des contrats/interfaces.

4. **I - Interface Segregation (Ségrégation des Interfaces) :**
   - Préférer de petits contrats ciblés et spécialisés à de gros contrats généralistes.

5. **D - Dependency Inversion (Inversion des Dépendances) :**
   - Toujours dépendre d'abstractions (interfaces/contrats) plutôt que d'implémentations concrètes.
   - Injection de dépendances systématique via le constructeur et le *Service Container* (IoC) de Laravel (aucun couplage fort).

## Protocole de Collaboration avec l'Utilisateur
* **Cadrage architectural préalable :** Avant de générer du code pour une nouvelle fonctionnalité, exposer brièvement la structure des classes, interfaces et patterns prévus.
* **Validation préalable obligatoire :** Toujours lister les commandes ou fichiers à créer/modifier, expliquer le pourquoi, et attendre le "OK" explicite de l'utilisateur avant d'exécuter.
* **Style de communication :** Concis, direct, clair et pédagogique.

## Sécurité des APIs & Rate Limiting Obligatoire

Pour toute création ou modification d'endpoints d'API :
1. **Personnalisation systématique des Rate Limiters :** Ne jamais se contenter des valeurs par défaut globales. Toujours définir des règles de limitation de débit ciblées dans `app/Providers/AppServiceProvider.php` (via `RateLimiter::for()`).
2. **Protection stricte des routes sensibles :**
   - **Authentification (`/login`, `/register`, mot de passe) :** Limite stricte anti-brute-force (ex: maximum 5 tentatives par minute par IP).
   - **Actions critiques & mutations sensibles (paiements, soumission de formulaires lourds, envois d'emails) :** Quotas stricts pour éviter les abus ou les soumissions répétées accidentelles.
   - **Consultation générale (`/api/*`) :** Quotas par utilisateur authentifié (`$request->user()->id`) et par IP pour les invités.
3. **Application explicite sur les routes :** Attacher systématiquement le middleware `throttle:<nom-de-la-regle>` sur les groupes de routes correspondants dans `routes/api.php`.

