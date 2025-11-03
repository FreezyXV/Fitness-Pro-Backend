# 🏋️ FitnessPro - Documentation Complète de l'API Backend

Ce document fournit une description détaillée de l'API backend pour l'application FitnessPro. Il est destiné aux développeurs qui souhaitent comprendre son architecture, contribuer à son développement ou l'utiliser.

## 1. Philosophie du Projet

L'objectif est de fournir une API performante, sécurisée et facile à maintenir. Pour cela, nous suivons les meilleures pratiques de l'écosystème Laravel, en mettant l'accent sur :
- **La Séparation des Responsabilités** : Chaque partie du code a un rôle unique (les contrôleurs gèrent les requêtes, les services la logique métier, etc.).
- **La Lisibilité du Code** : Un code clair est plus facile à maintenir et à faire évoluer.
- **La Testabilité** : L'architecture est conçue pour faciliter l'écriture de tests unitaires et fonctionnels.

---

## 2. Technologies et Architecture

### Technologies Principales

| Technologie | Version | Rôle et Justification |
| :--- | :--- | :--- |
| **PHP** | `^8.2` | Le langage de programmation principal. La version 8.2+ offre des améliorations de performance, une syntaxe moderne et un typage plus strict. |
| **Laravel** | `^12.0` | Le framework principal. Il fournit une structure solide, sécurisée et élégante pour le routing, l'ORM (Eloquent), la gestion des files d'attente, et plus encore. |
| **Laravel Sanctum**| `^4.2` | **Gestion de l'authentification.** Idéal pour les applications frontends (SPA) comme Angular, offrant une authentification simple et sécurisée par tokens, sans la complexité d'OAuth2. |
| **PostgreSQL** | `(Prod)` | **Base de données de production.** Choisi pour sa robustesse, sa fiabilité et ses fonctionnalités avancées (comme le support JSONB) à grande échelle. |
| **SQLite** | `(Dev)` | **Base de données de développement.** Utilisé pour sa simplicité extrême (un seul fichier, aucune configuration), ce qui rend le démarrage d'un nouvel environnement de dev quasi instantané. |

### Architecture : Le Flux d'une Requête

Nous utilisons une architecture en couches inspirée du Domain-Driven Design pour garantir une séparation claire des responsabilités. Le cycle de vie d'une requête est le suivant :

**Requête HTTP → Route → Contrôleur → Service → Repository → Modèle → Base de Données**

1.  **Route (`routes/api.php`)** : Le point d'entrée. Il intercepte la requête HTTP (ex: `GET /api/workouts`) et la dirige vers la méthode du contrôleur approprié.

2.  **Contrôleur (`app/Http/Controllers`)** : Le chef de gare. Son rôle est de valider les données de la requête (en utilisant les `FormRequest` de Laravel) et d'appeler la méthode du service correspondant. Il ne contient **aucune logique métier**.
    - *Exemple : `WorkoutController.php`*

3.  **Service (`app/Services`)** : Le cerveau de l'application. C'est ici que réside la logique métier complexe. Par exemple, `WorkoutService` pourrait contenir une méthode `completeWorkoutSession` qui calcule les calories brûlées, met à jour les objectifs de l'utilisateur et lui attribue des points d'expérience.
    - **Pourquoi ?** Isoler la logique ici la rend réutilisable (par une commande CLI, un job, etc.) et facile à tester unitairement.
    - *Exemple : `GoalsService.php`*

4.  **Repository (`app/Repositories`)** : La couche d'abstraction de la base de données. C'est le seul endroit où l'on formule des requêtes vers la base de données. Nous utilisons ce pattern avec `WorkoutRepository` et `GoalRepository`.
    - **Pourquoi ?** Cela découple totalement la logique métier de l'implémentation de la base de données (Eloquent). Si nous décidions de changer d'ORM ou de source de données, seul le code du repository serait à modifier.
    - *Exemple : `GoalRepository.php`*

5.  **Modèle (`app/Models`)** : La représentation des tables de la base de données. Les modèles Eloquent gèrent les relations entre les tables (ex: un `User` a plusieurs `Workout`) et peuvent contenir de la logique simple liée au modèle lui-même (mutators, accessors).
    - *Exemple : `User.php`, `Workout.php`*

---

## 3. Démarrage Rapide

Suivez ces étapes pour lancer le serveur en local.

```bash
# 1. Cloner le projet et naviguer dans le dossier backend
# git clone ...
cd backend

# 2. Installer les dépendances PHP via Composer
composer install

# 3. Configurer l'environnement
# Copie le fichier d'exemple. Ce fichier est ignoré par Git.
cp .env.example .env

# Génère la clé de chiffrement unique pour l'application
php artisan key:generate

# 4. Préparer la base de données locale (SQLite)
# Crée le fichier vide qui servira de base de données
touch database/database.sqlite

# 5. Lancer les migrations pour créer la structure de la base de données
php artisan migrate

# 6. Lancer le serveur de développement
# Le backend sera accessible sur http://localhost:8000
php artisan serve
```

### 🌱 Seeding the Neon Production Database

The production container can now run the seeders on demand. This is useful the first time you deploy to Neon (or after resetting the database).

1. In Render, open your **fitness-pro-backend** service and set the environment variable `RUN_DB_SEEDERS` to `true`.
2. Trigger a redeploy. During startup you should see `🌱 Running database seeders...` in the logs, and the `ProductionSeeder` will push the static exercise catalogue into Neon.
3. Once the seed finished, reset `RUN_DB_SEEDERS` to `false` (or delete it) and redeploy again. This prevents the exercises table from being truncated on every restart.

If you ever need to reseed manually without redeploying, exec into the running container and run:

```bash
php artisan db:seed --force --no-interaction
```

Because the app now defaults to `ProductionSeeder` when `APP_ENV=production`, the command above seeds only the static data that is safe for production.

---

## 4. Schéma de la Base de Données

Voici une description des tables principales et de leurs relations.

### `users`
Stocke les informations d'identification et de profil des utilisateurs.

| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | `bigint` | Clé primaire. |
| `name` | `varchar` | Nom complet de l'utilisateur. |
| `email` | `varchar` | Adresse e-mail unique, utilisée pour la connexion. |
| `password` | `varchar` | Mot de passe hashé avec Bcrypt. |

### `workouts`
Contient à la fois les modèles d'entraînement (`templates`) et les sessions effectuées.

| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | `bigint` | Clé primaire. |
| `user_id` | `bigint` | **Clé étrangère** vers `users.id`. |
| `name` | `varchar` | Nom de l'entraînement (ex: "Push Day"). |
| `is_template` | `boolean` | `true` si c'est un modèle réutilisable, `false` si c'est une session effectuée. |
| `completed_at`| `timestamp`| Date et heure de la fin de la session (pour les sessions). |

### `exercises`
La bibliothèque de tous les exercices disponibles.

| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | `bigint` | Clé primaire. |
| `name` | `varchar` | Nom de l'exercice (ex: "barbell bench press"). |
| `body_part` | `varchar` | Partie du corps ciblée (ex: "chest"). |
| `equipment` | `varchar` | Équipement requis (ex: "barbell"). |

### `workout_exercises` (Table Pivot)
C'est le lien entre un entraînement et ses exercices. C'est ici que la "magie" opère.

| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | `bigint` | Clé primaire. |
| `workout_id` | `bigint` | **Clé étrangère** vers `workouts.id`. |
| `exercise_id`| `bigint` | **Clé étrangère** vers `exercises.id`. |
| `sets` | `integer` | Nombre de séries à effectuer. |
| `reps` | `integer` | Nombre de répétitions par série. |
| `weight` | `decimal` | Poids utilisé pour cet exercice dans cet entraînement. |

**Relation Many-to-Many :** `workouts` ←→ `workout_exercises` ←→ `exercises`

---

## 5. Structure et Schémas du Backend

Cette section détaille l'organisation du code et des données.

### Structure des Dossiers

Le projet suit la structure standard de Laravel, qui est conçue pour la clarté et la maintenabilité.

```
backend/
│
├── app/  (Le cœur de votre application)
│   ├── Http/Controllers/  (Les contrôleurs)
│   │   └── WorkoutController.php -> Reçoit la requête HTTP, la valide, et appelle un service.
│   │
│   ├── Services/ (La logique métier)
│   │   └── GoalsService.php -> Contient la logique complexe (ex: calculer la progression d'un objectif).
│   │
│   ├── Repositories/ (L'accès aux données)
│   │   └── GoalRepository.php -> Centralise toutes les requêtes à la base de données pour les objectifs.
│   │
│   └── Models/ (La représentation des données)
│       └── Goal.php -> Objet qui représente une ligne dans la table 'goals'.
│
├── database/ (La base de données)
│   ├── migrations/ -> "Version control" pour votre schéma de base de données.
│   └── seeders/    -> Fichiers pour peupler la base de données avec des données de test.
│
├── routes/ (Les routes de l'API)
│   └── api.php -> La carte de tous les endpoints de votre API.
│
├── config/ (La configuration)
│   └── cors.php -> Configure quels domaines frontends peuvent accéder à l'API.
│
└── tests/ (Les tests automatisés)
    └── Feature/ -> Tests qui simulent une requête HTTP complète.

```

### Schéma de l'Architecture (Flux de Données)

Voici comment une requête traverse l'application, de l'utilisateur à la base de données, et retour.

```
[Requête HTTP du client Angular (ex: POST /api/goals)]
             |
             v
+--------------------------+
| Route (`routes/api.php`) |
+--------------------------+
             | (Dirige vers `GoalController@store`)
             v
+----------------------------------------------------+
| Contrôleur (`app/Http/Controllers/GoalController`) |
| 1. Valide les données de la requête (titre, etc.)  |
| 2. Appelle le service `GoalsService`.              |
+----------------------------------------------------+
             |
             v
+------------------------------------------+
| Service (`app/Services/GoalsService`)    |
| 1. Applique la logique métier.           |
| 2. Appelle le `GoalRepository` pour créer. |
+------------------------------------------+
             |
             v
+--------------------------------------------------+
| Repository (`app/Repositories/GoalRepository`)   |
| 1. Prépare et exécute la requête de création.    |
|    `Goal::create([...])`                         |
+--------------------------------------------------+
             |
             v
+--------------------------------------+
| Modèle (`app/Models/Goal`)           |
| 1. Eloquent ORM traduit en requête SQL. |
+--------------------------------------+
             |
             v
+----------------------------------+
| Base de Données (SQLite / PostgreSQL) |
| 1. Insère la nouvelle ligne.     |
+----------------------------------+
             |
             v
[Réponse JSON (201 Created)]
```

### Schéma de la Base de Données (Relations)

Ce schéma illustre comment les tables principales sont connectées entre elles.

```
+-----------+      +------------+      +-----------------------+      +-------------+
|   users   |─-─<--|  workouts  |─-─<--|  workout_exercises  |-->-─--|  exercises  |
+-----------+ (1)  +------------+ (1)  +-----------------------+ (M)  +-------------+
| id (PK)   |      | id (PK)    |      | id (PK)               |      | id (PK)     |
| name      |      | user_id(FK)|      | workout_id (FK)       |      | name        |
| email     |      | name       |      | exercise_id(FK)       |      | body_part   |
+-----------+      |is_template |      | sets, reps, weight    |      +-------------+
     |             +------------+      +-----------------------+
     |
     | (1)
     `─-─<--+-----------+
             |   goals   |
             +-----------+
             | id (PK)   |
             |user_id(FK)|
             | title     |
             +-----------+

Légende:
(PK) = Primary Key (Clé primaire)
(FK) = Foreign Key (Clé étrangère)
-─<-- = Relation One-to-Many (Un `user` a plusieurs `workouts`)
-->-─- = Relation Many-to-One (Plusieurs `workout_exercises` pointent vers un `exercise`)
```

## 6. Configuration Essentielle (`.env`)

## 5. Endpoints de l'API

## 5. Endpoints de l'API

Voici une sélection des endpoints les plus importants. Toutes les requêtes et réponses sont en JSON.

### Authentification (`/api/auth`)

**`POST /api/auth/register`** : Crée un nouvel utilisateur.
- **Requête** : `{ "name": "John Doe", "email": "john@doe.com", "password": "password", "password_confirmation": "password" }`
- **Réponse (201)** : `{ "success": true, "data": { "user": {...}, "token": "..." } }`

**`POST /api/auth/login`** : Connecte un utilisateur.
- **Requête** : `{ "email": "john@doe.com", "password": "password" }`
- **Réponse (200)** : `{ "success": true, "data": { "user": {...}, "token": "..." } }`

**`GET /api/auth/me`** : (Authentification requise) Retourne l'utilisateur actuellement connecté.
- **Réponse (200)** : `{ "success": true, "data": { "id": 1, "name": "John Doe", ... } }`

**`POST /api/auth/logout`** : (Authentification requise) Déconnecte l'utilisateur en invalidant son token.
- **Réponse (200)** : `{ "success": true, "message": "Logged out successfully" }`

### Entraînements (`/api/workouts`)
*(Authentification requise pour tous les endpoints)*

**`GET /api/workouts/templates`** : Liste les modèles d'entraînements de l'utilisateur.
- **Réponse (200)** : `{ "success": true, "data": [ { "id": 1, "name": "Push Day", ... }, ... ] }`

**`POST /api/workouts/templates`** : Crée un nouveau modèle d'entraînement.
- **Requête** : `{ "name": "Leg Day", "description": "...", "exercises": [ { "exercise_id": 1, "sets": 4, "reps": 12 }, ... ] }`
- **Réponse (201)** : `{ "success": true, "data": { "id": 2, "name": "Leg Day", ... } }`

**`GET /api/workouts/logs`** : Liste les sessions d'entraînement effectuées par l'utilisateur.
- **Réponse (200)** : `{ "success": true, "data": [ { "id": 10, "name": "Push Day Session", "completed_at": "..." }, ... ] }`

### Objectifs (`/api/goals`)
*(Authentification requise pour tous les endpoints)*

**`GET /api/goals`** : Liste les objectifs de l'utilisateur.
- **Query Params** : `?status=active` pour filtrer par statut.
- **Réponse (200)** : `{ "success": true, "data": [ { "id": 1, "title": "Perdre 5kg", "progress_percentage": 40 }, ... ] }`

**`POST /api/goals`** : Crée un nouvel objectif.
- **Requête** : `{ "title": "Courir un 10km", "target_value": 10, "unit": "km", "target_date": "2025-12-31" }`
- **Réponse (201)** : `{ "success": true, "data": { "id": 2, "title": "Courir un 10km", ... } }`

---

## 6. Outils de Développement

Le projet inclut des routes spéciales pour peupler et réinitialiser la base de données en développement.

⚠️ **Ces routes ne sont actives que si `APP_ENV=local` dans votre `.env`. Elles sont inaccessibles en production.**

Toutes les routes de développement sont préfixées par `/api/dev-seed`.

| Méthode | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/dev-seed/portfolio` | **Le plus utile.** Peuple la base de données avec un jeu complet de données de démonstration (exercices, utilisateurs, objectifs). |
| `POST` | `/api/dev-seed/run-migrations` | Exécute les migrations de la base de données (`php artisan migrate`). |
| `POST` | `/api/dev-seed/clear-exercises` | Vide la table `exercises`. |
| `POST` | `/api/dev-seed/clear-workouts` | Vide la table `workouts`. |

**Exemple d'utilisation avec `curl`:**
```bash
curl -X POST http://localhost:8000/api/dev-seed/portfolio
```

---

## 7. Tests

Le projet utilise PHPUnit pour les tests automatisés. Les tests sont essentiels pour garantir la stabilité du code après chaque modification.

```bash
# Lancer toute la suite de tests
php artisan test

# Lancer un fichier de test spécifique
php artisan test tests/Feature/AuthTest.php
```
