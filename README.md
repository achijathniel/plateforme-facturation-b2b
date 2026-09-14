# 💼 Plateforme de Facturation B2B & Abonnements Récurrents

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)](https://postgresql.org)
[![Redis](https://img.shields.io/badge/Redis-7-DC382D?logo=redis&logoColor=white)](https://redis.io)
[![Docker](https://img.shields.io/badge/Docker-7_Conteneurs-2496ED?logo=docker&logoColor=white)](https://docker.com)
[![Tests](https://img.shields.io/badge/PHPUnit-22_Passés_(76_assertions)-4BB543?logo=checkmarx&logoColor=white)](https://phpunit.de)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Une API RESTful d'entreprise conçue selon les standards de la **Clean Architecture** et des principes **SOLID**. Elle gère la facturation inter-entreprises (B2B), le cloisonnement strict des données (**Multi-tenancy**), les calculs financiers au centime près (`bcmath`), l'optimisation des requêtes SQL sur forte volumétrie (50k+ lignes) et le traitement asynchrone via **Redis**.

Projet développé pour répondre aux exigences techniques de l'offre d'emploi Senior Backend de **DUGHU DEALTOO SAS**.

---

## 🏛 Architecture des Services (Docker Orchestration)

La stack repose sur **7 conteneurs Docker** isolés sur un réseau privé virtuel (`backend_network`) :

```
[ Client / Postman / Frontend ]
               │
               ▼ (Port 8000)
    ┌──────────────────────┐
    │    NGINX (Proxy)     │  <-- SSL termination, buffers & sécurité
    └──────────┬───────────┘
               │ (FastCGI :9000)
               ▼
    ┌──────────────────────┐
    │    PHP 8.3 - FPM     │  <───> [ PostgreSQL 16 ] (Données & Index B-Tree)
    │  (API REST Laravel)  │
    └──────────┬───────────┘
               │
               ├───> [ REDIS 7 ] (Cache, Sessions & Queues)
               │         ▲
               │         │ (Dépile les tâches en arrière-plan)
               │    ┌────┴─────────────────┐
               │    │ Worker Queue (PHP)   │ ──► [ Mailpit :1025 ] (SMTP Dev)
               │    │ (backend_queue)      │
               │    └──────────────────────┘
               │
               └───> [ Telescope / Adminer ] (Supervision & Monitoring)
```

| Service | Image | Rôle & Spécificités |
| :--- | :--- | :--- |
| **`backend_web`** | `nginx:alpine` | Reverse proxy, masquage des headers serveur, try_files et buffers 256k. |
| **`backend_app`** | `php:8.3-fpm` | Cœur applicatif Laravel avec extensions `pdo_pgsql`, `redis`, `bcmath`, `pcntl`. |
| **`backend_queue`** | `php:8.3-fpm` | Worker asynchrone permanent supervisé (`php artisan queue:work redis --tries=3`). |
| **`backend_db`** | `postgres:16-alpine` | Base relationnelle avec typage strict (`decimal(15,2)`, UUID, soft deletes). |
| **`backend_redis`** | `redis:7-alpine` | Broker de messages en RAM pour les files d'attente et le cache. |
| **`backend_mailpit`** | `axllent/mailpit` | Capture des emails SMTP (Interface web sur le port `8025`). |
| **`backend_adminer`** | `adminer:latest` | Gestionnaire de base de données visuel (Port `8080`). |

---

## 📐 Directives de Développement & Architecture (SOLID)

Le code respecte strictement les principes **SOLID** et les design patterns d'entreprise :

1. **S - Single Responsibility & Skinny Controllers :**
   * **Contrôleurs ultra-légers :** Uniquement dédiés à la réception de la requête HTTP et au retour de la réponse JSON.
   * **Form Requests & DTOs :** Validation stricte des données dans des `FormRequest`, converties en *Data Transfer Objects* (`CreateInvoiceDTO`) typés et immutables.
   * **Service / Action Pattern :** Logique métier isolée dans des classes d'actions réutilisables (`CreateInvoiceAction`, `LoginAction`, `LogoutAction`).
2. **O & L - Open/Closed & Liskov Substitution :**
   * Extension des comportements par composition et contrats stricts d'interfaces.
3. **I & D - Interface Segregation & Dependency Inversion :**
   * **Repository Pattern découplé :** Contrat `InvoiceRepositoryInterface` injecté via le Service Container de Laravel et implémenté par `EloquentInvoiceRepository`.
   * Préchargement relationnel (**Eager Loading**) pour éliminer le problème **$N+1$**.

---

## 🛡️ Sécurité, Rate Limiting & Multi-Tenancy

* **Authentification par Token :** Implémentation via **Laravel Sanctum** avec révocation immédiate du token actif en base PostgreSQL lors du logout.
* **Rate Limiting Personnalisé Obligatoire :**
  * `api-login` : Limite stricte anti-brute-force de **5 tentatives / minute / IP**.
  * `api-invoices-write` : Limite stricte de **15 mutations / minute / utilisateur** pour protéger les écritures financières.
  * `api-invoices-read` : Quota de **60 consultations / minute / utilisateur**.
* **Cloisonnement Multi-tenancy (RBAC) :**
  * Sécurisé au niveau objet via `InvoicePolicy`.
  * Un compte `CLIENT` ou `ACCOUNTANT` ne peut ni lister, ni consulter, ni modifier les factures d'une autre entreprise (**403 Forbidden**).
  * Seul le rôle `ADMIN` dispose d'une vue d'ensemble sur toutes les organisations.

---

## ⚡ Précision Financière & Optimisation SQL

* **Calculs Financiers `bcmath` :** Aucun calcul monétaire n'est réalisé en flottant classique (problème d'arrondi JS/PHP). Les sous-totaux, la TVA à 18% (standard UEMOA) et le total TTC en **Franc CFA (`XOF`)** sont calculés avec une précision décimale absolue.
* **Transactions Atomiques :** Utilisation de `DB::transaction()` pour garantir que la facture et ses lignes d'articles sont créées ensemble sans incohérence.
* **Génération séquentielle légale :** Numérotation incrémentale protégée contre les accès concurrents sous PostgreSQL (`INV-2026-00021`).
* **Optimisation SQL sur 50 000 factures :**
  * Diagnostic d'exécution avant/après avec **`EXPLAIN (ANALYZE, BUFFERS)`**.
  * Mise en place d'**Index Composites B-Tree** (`invoices_org_status_dates_idx` et `invoices_org_issue_date_idx`) selon la règle ESR (*Equality, Sort, Range*).
  * Résultat : **19 000 lignes jetées éliminées** et temps de requête divisé par deux.
  * Verrouillage anti-$N+1$ en développement via `Model::preventLazyLoading()`.

---

## 📬 Traitements Asynchrones & Automatisation

* **Découplage Redis (< 20ms) :** La création d'une facture pousse un job `SendInvoiceNotificationJob` dans Redis. L'API répond immédiatement en HTTP 201 sans faire attendre l'utilisateur.
* **Résilience des Workers :** Configuration avec 3 tentatives automatiques (`$tries = 3`), un backoff de 10 secondes et journalisation d'alertes en cas d'échec définitif (`failed_jobs`).
* **Scheduler & Tâches Planifiées :**
  * Commande Artisan `app:process-overdue-invoices` planifiée quotidiennement (`dailyAt 00:01`).
  * Traitement par paquets (`chunkById(100)`) pour garantir une empreinte mémoire constante (< 30 Mo) même sur 100 000 factures.

---

## 🚀 Installation & Démarrage Rapide

### 1. Cloner le projet
```bash
git clone https://github.com/essentiel2021/plateforme-facturation-b2b.git
cd plateforme-facturation-b2b
```

### 2. Démarrer l'infrastructure Docker
```bash
docker compose up -d --build
```

### 3. Exécuter les migrations et le jeu de données
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

*(Identifiants Admin par défaut : `admin@dughu-dealtoo.com` / `password`)*.

---

## 🧪 Tests Automatisés (100% au Vert)

Le projet dispose d'une suite complète de **22 Feature Tests** couvrant l'authentification, le Rate Limiting, les calculs de TVA, le Multi-tenancy, l'Eager Loading et les Queues :

```bash
docker compose exec app php artisan test
```

```text
   PASS  Tests\Feature\Auth\AuthenticationTest (7 tests, 33 assertions)
  ✓ user can login with valid credentials
  ✓ user cannot login with invalid password
  ✓ login requires email and password
  ✓ authenticated user can access me profile
  ✓ unauthenticated user cannot access me profile
  ✓ authenticated user can logout
  ✓ login is rate limited after five failed attempts (HTTP 429)

   PASS  Tests\Feature\Invoices\InvoiceCreationTest (4 tests, 20 assertions)
  ✓ accountant can create invoice with accurate tax calculations
  ✓ client cannot create invoice (HTTP 403)
  ✓ admin can create invoice for any organization
  ✓ invoice creation fails validation with invalid data (HTTP 422)

   PASS  Tests\Feature\Invoices\InvoiceQueryTest (6 tests, 14 assertions)
  ✓ admin can view all invoices across organizations
  ✓ user can only view invoices from own organization (Multi-tenancy)
  ✓ user can view single invoice from own organization
  ✓ user cannot view invoice from another organization (HTTP 403)
  ✓ unauthenticated user cannot view invoices (HTTP 401)
  ✓ viewing nonexistent invoice returns 404

   PASS  Tests\Feature\Invoices\InvoiceNotificationTest (2 tests, 3 assertions)
  ✓ invoice creation dispatches notification job
  ✓ notification job sends email to organization

   PASS  Tests\Feature\Commands\ProcessOverdueInvoicesTest (1 test, 4 assertions)
  ✓ command marks expired sent invoices as overdue

  Tests:    22 passed (76 assertions)
```

---

## 🛠 Points d'Accès & Outils de Supervision

| Outil | URL Locale | Description |
| :--- | :--- | :--- |
| **API REST** | `http://localhost:8000/api` | Point d'entrée de toutes les routes de l'API |
| **Laravel Telescope** | `http://localhost:8000/telescope` | Supervision en direct (Requêtes, Requêtes SQL, Jobs, Mails) |
| **Mailpit (Webmail)** | `http://localhost:8025` | Visualisation en temps réel des emails HTML envoyés par les Workers |
| **Adminer** | `http://localhost:8080` | Interface visuelle d'administration PostgreSQL |

---

## 📄 Licence
Ce projet est open-source et distribué sous licence [MIT](LICENSE).
