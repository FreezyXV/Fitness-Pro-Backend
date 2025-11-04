# Backend FitnessPro

API Laravel 12 qui alimente la plateforme FitnessPro (SPA Angular). Ce document est une **visite guidée** du backend : raisons des choix techniques, circulation des requêtes, persistance des données, méthodes pour l’exécuter et l’étendre dans de bonnes conditions.

> Version anglaise disponible dans `README.md`.

---

## 📚 Table des matières

1. [Ce que couvre le backend](#ce-que-couvre-le-backend)
2. [Stack technique & motivations](#stack-technique--motivations)
3. [Modules métiers en un coup d’œil](#modules-métiers-en-un-coup-doeil)
4. [Architecture & flux des requêtes](#architecture--flux-des-requêtes)
5. [Persistance & schéma de données](#persistance--schéma-de-données)
6. [Services externes & intégrations](#services-externes--intégrations)
7. [Configuration & environnement](#configuration--environnement)
8. [Mise en place locale](#mise-en-place-locale)
9. [Peuplement de la base (dev & prod)](#peuplement-de-la-base-dev--prod)
10. [Authentification & sécurité](#authentification--sécurité)
11. [Contrat d’API & gestion des erreurs](#contrat-dapi--gestion-des-erreurs)
12. [Logs, monitoring, jobs](#logs-monitoring-jobs)
13. [Tests & qualité](#tests--qualité)
14. [Guide de contribution](#guide-de-contribution)
15. [Dépannage](#dépannage)

---

## Ce que couvre le backend

Le backend gère tous les besoins métiers de FitnessPro :

- **Utilisateurs** : inscription/connexion, profil, reset mot de passe, jetons Sanctum.
- **Workouts** : modèles, séances planifiées, sessions terminées, calcul de streaks.
- **Objectifs (Goals)** : objectifs SMART, progression, historique, complétions.
- **Nutrition** : base d’aliments, calculateur calorique, recommandations.
- **Calendrier & notifications** : tâches planifiées, rappels, notifications en base + mail.
- **Analytics** : statistiques dashboard, calculateur de streaks, synthèse pour le front.

Chaque domaine est encapsulé dans un service dédié afin de garder des contrôleurs minimalistes et d’assurer une testabilité maximale.

---

## Stack technique & motivations

| Couche | Technologie | Pourquoi |
| --- | --- | --- |
| Runtime | **PHP 8.2** | Typage moderne (readonly, enums), meilleures performances. |
| Framework | **Laravel 12** | Ensemble complet (routing, validation, ORM, queues, notifications, mail). |
| Auth | **Laravel Sanctum** | Authentification token pensée pour les SPA (Angular). |
| Base de données | **PostgreSQL (production)** | Robustesse, JSONB, index, fonctions analytiques. |
| BD locale | **SQLite** | Zero-config parfait pour le dev & les tests. |
| Queue | **Driver base de données** (modifiable) | Simplicité initiale ; extensible vers Redis/SQS. |
| Mail | **Notifications & Mailables** | Gestion des emails et notifications multi-canaux. |
| Conteneurisation | **Docker (optionnel)** | Compatible Sail, Render déploie PHP-FPM + Nginx. |

> Le mot d’ordre est la fiabilité : uniquement des briques pérennes et supportées par la communauté.

---

## Modules métiers en un coup d’œil

| Module | Fichiers clés | Rôle |
| --- | --- | --- |
| **Authentification** | `AuthController`, `AuthService`, `ForgotPasswordRequest`, `ResetPasswordNotification` | Login, register, tokens Sanctum, reset mot de passe (token & direct). |
| **Workouts** | `WorkoutController`, `WorkoutService`, `WorkoutRepository`, `Workout`, `WorkoutExercise` | Modèles d’entraînement, sessions, mise à jour des streaks, seed portfolio. |
| **Goals** | `GoalController`, `GoalService`, `GoalRepository`, `Goal` | CRUD des objectifs, calcul des progrès, complétions/activations. |
| **Calendrier** | `CalendarController`, `CalendarService`, `CalendarTask` | Tâches planifiées liées aux workouts/goals. |
| **Notifications** | `NotificationController`, `WorkoutNotificationService`, notifications Laravel | Notifications en base + email (workout, achievements, reset password). |
| **Nutrition** | `NutritionController`, `NutritionService` | Base d’aliments, calculs nutritionnels, intégrations externes possibles. |
| **Analytics** | `DashboardController`, `StatisticsService`, `StreakCalculatorService` | Données pour le dashboard, agrégations statistiques. |
| **Middleware** | `WorkoutApiLogger`, `WorkoutApiRateLimit`, `ValidateWorkoutOwnership` | Logs, limites de débit, vérification de l’ownership. |
| **Seeders** | `ProductionSeeder`, `ExerciseSeeder`, `WorkoutPlansSeeder`, routes dev-seed | Peuplement réaliste pour dev et prod. |

Tous les modules suivent le même pattern (Controller → Service → Repository/Model → Notifications/Jobs), ce qui facilite la prise en main.

---

## Architecture & flux des requêtes

### Organisation en couches

```
Requête HTTP
   │
   ▼
routes/api.php    →    Contrôleur    →    Service    →    Repository / Modèle    →    Base de données
           mappe          valide          orchestre            exécute requêtes             persiste
```

### Séquence détaillée (exemple : finaliser une séance)

```
Utilisateur = "Séance terminée" dans l’app Angular
       │
       ├─ POST /api/workouts/logs
       │
       ├─ WorkoutController@completeLog
       │       ├─ Valide la requête (WorkoutCompleteRequest)
       │       └─ Appelle WorkoutService::completeLog
       │
       ├─ WorkoutService
       │       ├─ Charge workout + exercices via repository
       │       ├─ Met à jour les stats (workout_exercises)
       │       ├─ Met à jour les goals via GoalsService
       │       ├─ Met à jour le streak via StreakCalculatorService
       │       └─ Envoie notifications / jobs si nécessaire
       │
       └─ Réponse JSON normalisée par ApiResponseTrait
```

### Mémo des dossiers

```
app/
  Http/Controllers/     # Points d’entrée HTTP
  Http/Middleware/      # Gardes, logs, throttling
  Http/Requests/        # Validation typée
  Models/               # Entités Eloquent
  Services/             # Logique métier
  Notifications/        # Emails & notifications
  Traits/               # Aides partagées (ApiResponseTrait…)
database/
  migrations/           # Historique du schéma
  seeders/              # Données de démo + prod
routes/api.php          # Déclaration des endpoints
config/                 # Auth, mail, sanctum, queue, etc.
tests/                  # Tests PHPUnit
```

---

## Persistance & schéma de données

### Tables principales

| Table | Description | Colonnes notables |
| --- | --- | --- |
| `users` | Profils & authentification | `name`, `email`, `password`, `height`, `weight`, `nutrition_profile` |
| `workouts` | Modèles & séances | `user_id`, `name`, `is_template`, `completed_at`, `notes` |
| `workout_exercises` | Pivot workout ↔ exercise | `workout_id`, `exercise_id`, `sets`, `reps`, `weight`, `tempo`, `rest` |
| `exercises` | Catalogue d’exercices | `name`, `equipment`, `body_part`, `difficulty`, `video_url` |
| `goals` | Objectifs SMART | `title`, `target_value`, `unit`, `status`, `progress_percentage`, `deadline` |
| `goal_histories` | Historique des mises à jour | `goal_id`, `previous_progress`, `new_progress`, `note` |
| `calendar_tasks` | Tâches calendrier | `user_id`, `related_type`, `related_id`, `scheduled_for`, `status` |
| `notifications` | Notifications internes | `type`, `data`, `read_at` |
| `password_reset_tokens` | Tokens reset | `email`, `token`, `created_at` |
| `personal_access_tokens` | Jetons Sanctum | `tokenable_type`, `tokenable_id`, `abilities`, `last_used_at` |

### Schéma simplifié

```
┌──────────────┐        ┌──────────────┐        ┌────────────────────┐
│    users     │ 1 ---->│   workouts   │ 1 ---->│ workout_exercises  │
└──────────────┘        └──────────────┘        └────────────────────┘
      │                        │                          │
      ▼                        │                          ▼
┌──────────────┐               │                ┌─────────────────┐
│    goals     │               │                │   exercises     │
└──────────────┘               │                └─────────────────┘
      │                        |
      ▼                        ▼
┌──────────────┐      ┌─────────────────┐      ┌──────────────────┐
│ notifications│      │ calendar_tasks  │      │ personal_tokens  │
└──────────────┘      └─────────────────┘      └──────────────────┘
```

### Cycle de vie des données

- **Exercices & workouts** sont seedés (dev + prod via `ProductionSeeder`).
- **Goals** conservent les progrès via `goal_histories`.
- **Reset password** stocke tokens dans `password_reset_tokens`, le hash est régénéré avec `remember_token`.
- **Notifications** existent en base + chaîne mail pour audit et affichage front.
- **Jobs/Events** peuvent être mis en file pour éviter de bloquer les requêtes.

---

## Services externes & intégrations

| Intégration | Emplacement | Utilité |
| --- | --- | --- |
| SMTP | `config/mail.php`, `.env` | Envoi des emails (reset, rappels workouts). |
| Sanctum | `config/sanctum.php`, middleware | Authentification stateful pour SPA. |
| Logger | `config/logging.php`, `WorkoutApiLogger` | Journaux des requêtes/erreurs. |
| Cache | `config/cache.php` | Mise en cache possible des stats (StatisticsService). |
| Déploiement | `Dockerfile`, `Procfile`, `fly.toml` | Render (prod) + Fly (optionnel). |
| Base de prod | `config/database.php` | Neon (PostgreSQL managé). |
| API tierce | `NutritionService` (optionnel) | Connexion à des bases nutritionnelles externes. |

---

## Configuration & environnement

Créer `.env` à partir de `.env.example`, puis compléter :

```env
APP_NAME=FitnessPro
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

FRONTEND_URL=http://localhost:4200
SANCTUM_STATEFUL_DOMAINS=localhost:4200
SESSION_DOMAIN=localhost

DB_CONNECTION=sqlite
DB_DATABASE=./database/database.sqlite

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@fitnesspro.app
MAIL_FROM_NAME="FitnessPro"

RUN_DB_SEEDERS=false
DB_SEEDER_CLASS=ProductionSeeder
```

Production : `APP_ENV=production`, `APP_DEBUG=false`, Postgres, domaines réels, SMTP prod.

---

## Mise en place locale

### Méthode native (PHP installé)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate

# Seed optionnel
php artisan db:seed

php artisan serve          # http://localhost:8000
```

### Docker (style Laravel Sail)

1. Installer Docker + Docker Compose.  
2. Configurer `.env` pour Postgres (`DB_CONNECTION=pgsql`).  
3. Lancer `./vendor/bin/sail up`.  
4. L’API répond sur `http://localhost`, Postgres/Redis sont disponibles.  

---

## Peuplement de la base (dev & prod)

### Routes dev

Actives uniquement si `APP_ENV=local`. Préfixe `/api/dev-seed`.

| Méthode | Endpoint | Description |
| --- | --- | --- |
| POST | `/api/dev-seed/portfolio` | Jeu complet de démo (users, workouts, goals, nutrition). |
| POST | `/api/dev-seed/run-migrations` | Lance `php artisan migrate`. |
| POST | `/api/dev-seed/clear-exercises` | Vide la table `exercises`. |
| POST | `/api/dev-seed/clear-workouts` | Vide `workouts` et la table pivot. |

### Seed production (Render + Neon)

1. Mettre `RUN_DB_SEEDERS=true` (garder `DB_SEEDER_CLASS=ProductionSeeder`).  
2. Redéployer ; vérifier le log `🌱 Running database seeders using ProductionSeeder...`.  
3. Repasser `RUN_DB_SEEDERS=false` et redéployer pour éviter le seed à chaque démarrage.  

Depuis un conteneur :

```bash
php artisan db:seed --force --no-interaction
```

En production, seule `ProductionSeeder` s’exécute (catalogue exercices, nutrition, workouts publics) sans toucher aux données utilisateurs.

---

## Authentification & sécurité

### Sanctum

- Requêtes SPA : cookie + en-tête `X-XSRF-TOKEN`.  
- Jetons API (mobiles) stockés dans `personal_access_tokens` avec scopes.  
- Middleware `auth:sanctum` sécurise les routes ; `ValidateWorkoutOwnership` vérifie l’accès aux ressources.  

### Flux reset mot de passe

1. Angular appelle `POST /api/auth/password/email`.  
2. `AuthService::sendPasswordResetLink` envoie un mail (ResetPasswordNotification).  
3. Lien reçu : `${FRONTEND_URL}/reset-password?token=...&email=...`.  
4. Le composant reset Angular détecte `token/email`, verrouille le champ email.  
5. En fonction du contexte :
   - `POST /api/auth/password/reset` (avec token).
   - `POST /api/auth/password/direct-reset` (fallback sans token).  
6. Le backend met à jour le hash + `remember_token`, logue l’opération.  

### Protections supplémentaires

- Throttling via `ThrottleRequests` et `WorkoutApiRateLimit`.  
- CORS permis uniquement pour le domaine Angular (config `cors.php`).  
- `APP_DEBUG=false` masque les détails sensibles en prod.  
- Sanctum gère CSRF pour les requêtes SPA.  

---

## Contrat d’API & gestion des erreurs

### Format standard

```jsonc
{
  "success": true,
  "data": { ... },
  "message": "Message lisible"
}

{
  "success": false,
  "message": "Erreur rencontrée",
  "errors": {
    "email": ["Le champ email est obligatoire."]
  }
}
```

- Validation : HTTP 422 avec détails par champ.  
- Authentification : 401/403 + message générique.  
- Exceptions inattendues : loguées, réponse 500 avec message générique (détails uniquement si `APP_DEBUG=true`).  
- Pagination standard Laravel (`data`, `links`, `meta`), tri/filtre via query params validées.  

---

## Logs, monitoring, jobs

| Sujet | Implémentation | Notes |
| --- | --- | --- |
| Logs HTTP | `WorkoutApiLogger`, channel `stack` | Méthode, URI, durée, user ID. |
| Événements métier | Services loguent changements clés (goals, reset). |
| Erreurs | `storage/logs/laravel.log` ; intégrer Sentry/Bugsnag si besoin. |
| Queue | Driver `database` (table `jobs`). Passez à Redis/SQS pour la prod lourde. |
| Scheduler | `app/Console/Kernel.php` pour les tâches planifiées. |
| Notifications | Canal mail + base ; mettre en queue pour ne pas bloquer la requête. |

Locaux : `tail -f storage/logs/laravel.log`. Production : logs Render ou solution externe (Papertrail, Datadog…).

---

## Tests & qualité

```bash
php artisan test                         # suite complète
php artisan test tests/Feature/Auth/PasswordResetTest.php
php artisan test --filter=GoalsServiceTest
```

Outils recommandés :

- **PHPUnit** – tests natifs Laravel.  
- **Larastan / PHPStan** – analyse statique (`./vendor/bin/phpstan`).  
- **Laravel Pint** – formatage PSR-12 (`./vendor/bin/pint`).  
- **Pest** (optionnel) – syntaxe de test alternative.  
- CI – exécuter tests + analyse à chaque PR.

Philosophie :

- Contrôleurs : tests smoke (statuts, contrats).  
- Services : unitaires sur la logique métier (progression, streak, reset).  
|- Repositories : testables via SQLite en mémoire.  
- Notifications : `Notification::fake()` pour vérifier l’envoi.  
- Seeders : tests snapshot pour garantir l’intégrité du catalogue.  

---

## Guide de contribution

1. Créer une branche depuis `main`.  
2. Lancer `php artisan test` avant chaque commit.  
3. Mettre à jour seeders/tests/docs si le comportement change.  
4. Respecter PSR-12 / Laravel Pint.  
5. Décrire les changements et les tests dans la PR.  
6. Revue de code : vérifier que la logique reste dans les services, contrôleurs fins.  
7. Merge lorsque CI + review OK.  

Bonnes pratiques :

- Les nouvelles routes doivent utiliser FormRequest + service + `ApiResponseTrait`.  
- Toute nouvelle table s’accompagne d’une migration, modèle, factory, seeder (si besoin) et tests.  
- Mettre à jour les deux README (EN/FR) si l’architecture ou les processus évoluent.  
- Ajouter des diagrammes/explications pour faciliter l’onboarding.  

---

## Dépannage

| Symptôme | Cause probable | Solution |
| --- | --- | --- |
| Lien reset ouvre Angular sans token | `FRONTEND_URL` incorrect | Vérifier `.env`, pas de slash final, domaine identique au front. |
| Reset renvoie “token invalide” | Token expiré (60 min) | Redemander un email, vérifier que les mails partent bien. |
| SPA reçoit 401 | Configuration Sanctum incomplète | Définir `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, vider les cookies. |
| Aucun mail | SMTP erroné ou port bloqué | Tester via `php artisan tinker`, vérifier identifiants/port. |
| Seed déclenché à chaque reboot | `RUN_DB_SEEDERS` resté à `true` | Repassez l’env à `false` après le premier seed. |
| Permissions storage | `file_put_contents` échoue | Rendre `storage/` et `bootstrap/cache` accessibles en écriture. |
| Jobs restent en file | Worker non lancé | `php artisan queue:work` ou configurer le scheduler Render. |
| Messages d’erreur peu détaillés en prod | `APP_DEBUG=false` | Consulter `storage/logs/laravel.log` ou activer une solution externe. |

---

## Ressources utiles

- Laravel – https://laravel.com/docs  
- Sanctum – https://laravel.com/docs/sanctum  
- PostgreSQL – https://www.postgresql.org/docs/  
- Frontend Angular – `../frontend/README.md`  
- Outils diagramme – https://mermaid.js.org, https://asciiflow.com  

Bon développement ! 🚀
