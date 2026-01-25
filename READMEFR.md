# 🏋️ Backend FitnessPro - Documentation Complète & Pédagogique

> **Guide complet pour comprendre l'architecture, le fonctionnement et le développement du backend Laravel de FitnessPro**
>
> Cette documentation est conçue pour être **accessible à tous**, des débutants aux développeurs expérimentés.

**English version available:** [README.md](./README.md)

---

## 📚 Table des matières

1. [Introduction - Qu'est-ce qu'un Backend?](#1-introduction)
2. [Architecture Globale](#2-architecture)
3. [Technologies Utilisées et Pourquoi](#3-technologies)
4. [Installation et Configuration](#4-installation)
5. [Structure Complète du Projet](#5-structure)
6. [Flux de Requête - De l'Appel API à la Réponse](#6-request-flow)
7. [Système d'Authentification](#7-authentication)
8. [Base de Données & Modèles](#8-database)
9. [Services & Logique Métier](#9-services)
10. [Contrôleurs & Routes](#10-controllers)
11. [Middleware & Sécurité](#11-middleware)
12. [Notifications & Jobs](#12-notifications)
13. [Tests](#13-testing)
14. [Déploiement](#14-deployment)
15. [Bonnes Pratiques](#15-best-practices)
16. [Dépannage & FAQ](#16-troubleshooting)

---

<a name="1-introduction"></a>
## 1. Introduction - Qu'est-ce qu'un Backend?

### 🎯 Analogie Simple : Le Restaurant

Imaginez une application web comme **un restaurant** :

```
┌───────────────────────────────────────────────────────────────┐
│                    🍽️ RESTAURANT                              │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  🧑‍💼 SALLE (Frontend)           👨‍🍳 CUISINE (Backend)         │
│  ├─ Prend les commandes         ├─ Prépare les plats         │
│  ├─ Présente le menu            ├─ Gère les recettes         │
│  ├─ Affiche les plats           ├─ Stocke les ingrédients    │
│  └─ Interaction client          └─ Contrôle qualité          │
│                                                               │
│  👤 CLIENT                       📊 STOCKAGE                  │
│  └─ Fait des demandes           └─ Base de données           │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

**Le Backend est la cuisine :**
- Reçoit les commandes du frontend (requêtes HTTP)
- Traite la logique métier (recettes)
- Accède à la base de données (ingrédients/stockage)
- Retourne les données préparées (plats)

### 📊 Que fait le Backend FitnessPro?

```
┌────────────────────────────────────────────────────────────┐
│                 BACKEND FITNESSPRO                         │
└────────────────────────────────────────────────────────────┘

📱 FRONTEND (Angular)          🖥️ BACKEND (Laravel)          📊 BASE DE DONNÉES (PostgreSQL)
      │                              │                              │
      ├─ Utilisateur clique "Login"  │                              │
      │                              │                              │
      ├──POST /api/auth/login───────>│                              │
      │  { email, password }          │                              │
      │                              │                              │
      │                              ├─ Valide les identifiants     │
      │                              │                              │
      │                              ├──SELECT * FROM users────────>│
      │                              │   WHERE email = ?            │
      │                              │                              │
      │                              │<─────Données user───────────┤
      │                              │                              │
      │                              ├─ Vérifie le mot de passe    │
      │                              │                              │
      │                              ├─ Génère un jeton JWT        │
      │                              │                              │
      │<─────{ token, user }─────────┤                              │
      │                              │                              │
      ├─ Stocke le token             │                              │
      │                              │                              │
      ├─ Navigue vers le Dashboard   │                              │
```

**Le backend gère :**
- ✅ Authentification utilisateur (login, register, réinitialisation mot de passe)
- ✅ Gestion des entraînements (créer, modifier, terminer des sessions)
- ✅ Suivi des objectifs (progression, réalisations)
- ✅ Calculs nutritionnels (calories, macros)
- ✅ Calendrier & notifications
- ✅ Analyses & statistiques

---

<a name="2-architecture"></a>
## 2. Architecture Globale

### 🏗️ Architecture en Couches

FitnessPro backend suit une **architecture en couches propre** :

```
┌────────────────────────────────────────────────────────────┐
│                   COUCHES D'ARCHITECTURE                   │
└────────────────────────────────────────────────────────────┘

📱 CLIENT (Frontend Angular)
      │
      │ Requête HTTP (JSON)
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 🛣️  ROUTES (routes/api.php)                                 │
│     Mappe les URLs aux Contrôleurs                          │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 🔌 MIDDLEWARE                                               │
│     ├─ Authentification (Sanctum)                           │
│     ├─ Limitation de débit                                  │
│     ├─ Journalisation                                       │
│     └─ CORS                                                 │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 🎮 CONTRÔLEUR (HTTP/Controllers)                            │
│     ├─ Reçoit la requête                                    │
│     ├─ Valide les données (Form Requests)                   │
│     ├─ Appelle le Service                                   │
│     └─ Retourne la réponse JSON                             │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 🧠 SERVICE (Services/)                                      │
│     ├─ Logique métier                                       │
│     ├─ Orchestre plusieurs opérations                       │
│     ├─ Appelle les Repositories                             │
│     └─ Déclenche Notifications/Jobs                         │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 📦 REPOSITORY (Repositories/)                               │
│     ├─ Couche d'accès aux données                           │
│     ├─ Construction de requêtes                             │
│     └─ Abstraction de la base de données                    │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 🗄️  MODÈLE (Modèles Eloquent)                               │
│     ├─ Représente une table de base de données             │
│     ├─ Relations                                            │
│     └─ Attributs/Casts                                      │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 💾 BASE DE DONNÉES (PostgreSQL/SQLite)                      │
│     └─ Persistance des données                              │
└─────────────────────────────────────────────────────────────┘
```

### 🔄 Flux de Démarrage de l'Application

```
┌────────────────────────────────────────────────────────────┐
│           DÉMARRAGE DE L'APPLICATION                       │
└────────────────────────────────────────────────────────────┘

1️⃣ DÉMARRAGE SERVEUR
   PHP-FPM / Nginx démarre
        ↓
   Bootstrap Laravel
        ↓

2️⃣ CHARGEMENT CONFIGURATION
   📄 Charge le fichier .env
        ↓
   🔧 Charge les fichiers de config (app, database, auth, etc.)
        ↓
   🔑 Validation APP_KEY
        ↓

3️⃣ ENREGISTREMENT SERVICE PROVIDERS
   Enregistre les providers :
      ├─ AppServiceProvider
      ├─ AuthServiceProvider
      ├─ RouteServiceProvider
      ├─ RepositoryServiceProvider
      └─ WorkoutServiceProvider
        ↓

4️⃣ ENREGISTREMENT MIDDLEWARE
   Empile les middleware :
      ├─ CORS
      ├─ Authentification
      ├─ Limitation de débit
      └─ Middleware personnalisés
        ↓

5️⃣ CHARGEMENT ROUTES
   Charge routes/api.php
        ↓
   Mappe les endpoints aux contrôleurs
        ↓

6️⃣ CONNEXION BASE DE DONNÉES
   Connexion à PostgreSQL/SQLite
        ↓
   Vérification de la connexion
        ↓

7️⃣ APPLICATION PRÊTE
   ✅ API en écoute sur le port 8000
   ✅ Prête à traiter les requêtes
```

---

<a name="3-technologies"></a>
## 3. Technologies Utilisées et Pourquoi

### 🛠️ Stack Technique Complète

```
┌────────────────────────────────────────────────────────────┐
│                     STACK TECHNIQUE                        │
└────────────────────────────────────────────────────────────┘

🐘 PHP 8.2
    ├─ Pourquoi PHP 8.2?
    │  ├─ Système de typage moderne (readonly, enums)
    │  ├─ Améliorations de performance (compilateur JIT)
    │  ├─ Meilleure gestion des erreurs
    │  ├─ Arguments nommés
    │  └─ Support long terme (LTS)
    │
    └─ Alternatives considérées
       ├─ Node.js (moins mature pour l'entreprise)
       ├─ Python (Django/Flask - écosystème différent)
       └─ Java/Spring (plus complexe, développement plus lent)

🔥 LARAVEL 12
    ├─ Pourquoi Laravel?
    │  ├─ Framework complet (batteries incluses)
    │  ├─ ORM Eloquent (requêtes base de données intuitives)
    │  ├─ Authentification intégrée (Sanctum)
    │  ├─ Système de files d'attente pour jobs en arrière-plan
    │  ├─ Système email/notification
    │  ├─ Documentation excellente
    │  └─ Grande communauté & écosystème
    │
    └─ Exemple d'avantage
       // SQL traditionnel
       $users = DB::select('SELECT * FROM users WHERE active = 1');

       // ✅ Laravel Eloquent - lisible & sûr
       $users = User::where('active', true)->get();

🔐 LARAVEL SANCTUM
    ├─ Pourquoi Sanctum?
    │  ├─ Authentification par jeton simple
    │  ├─ Parfait pour SPA (Angular)
    │  ├─ Pas de complexité OAuth
    │  ├─ Session basée sur cookies pour web
    │  └─ Jetons API pour mobile
    │
    └─ Exemple
       // Authentification automatique par jeton
       Route::middleware('auth:sanctum')->group(function () {
           Route::get('/user', fn() => auth()->user());
       });

🐘 POSTGRESQL (Production)
    ├─ Pourquoi PostgreSQL?
    │  ├─ Base de données relationnelle robuste
    │  ├─ Support JSONB (données flexibles)
    │  ├─ Indexation avancée
    │  ├─ Fonctions de fenêtre pour analyses
    │  ├─ Excellentes performances
    │  └─ Standard de l'industrie
    │
    └─ Alternatives
       ├─ MySQL (similaire, mais moins de fonctionnalités)
       ├─ MongoDB (NoSQL, moins de structure)
       └─ SQLite (dev uniquement, pas scalable)

💾 SQLITE (Développement)
    ├─ Pourquoi SQLite pour le dev?
    │  ├─ Configuration zéro
    │  ├─ Basé sur fichier (facile à réinitialiser)
    │  ├─ Parfait pour les tests
    │  ├─ Rapide pour le développement
    │  └─ Même syntaxe SQL que PostgreSQL
    │
    └─ Utilisation
       // .env pour le développement
       DB_CONNECTION=sqlite
       DB_DATABASE=./database/database.sqlite

📧 LARAVEL NOTIFICATIONS
    ├─ Pourquoi Laravel Notifications?
    │  ├─ Multi-canal (email, SMS, base de données)
    │  ├─ Support des files d'attente (envoi asynchrone)
    │  ├─ Templates faciles
    │  └─ Helpers de test intégrés
    │
    └─ Exemple
       // Envoyer un email de réinitialisation de mot de passe
       $user->notify(new ResetPasswordNotification($token));

🐳 DOCKER (Optionnel)
    ├─ Pourquoi Docker?
    │  ├─ Environnement cohérent
    │  ├─ Intégration facile de l'équipe
    │  ├─ Intégration Laravel Sail
    │  └─ Conteneurs prêts pour la production
    │
    └─ Options de déploiement
       ├─ Render.com (recommandé)
       ├─ Fly.io
       ├─ AWS ECS
       └─ DigitalOcean App Platform
```

### 🔄 Flux de Compilation

Laravel est **interprété**, pas compilé, mais voici le flux de traitement des requêtes :

```
┌────────────────────────────────────────────────────────────┐
│             FLUX DE TRAITEMENT DES REQUÊTES                │
└────────────────────────────────────────────────────────────┘

1️⃣ ARRIVÉE DE LA REQUÊTE
   Requête HTTP → Nginx/Apache → PHP-FPM
          ↓

2️⃣ BOOTSTRAP LARAVEL
   ├─ Charge l'autoloader (Composer)
   ├─ Crée l'instance d'application
   ├─ Charge la configuration
   └─ Enregistre les service providers
          ↓

3️⃣ PILE MIDDLEWARE
   ├─ Vérification CORS
   ├─ Vérification authentification
   ├─ Limitation de débit
   └─ Journalisation
          ↓

4️⃣ ROUTAGE
   ├─ Correspondance URL vers route
   ├─ Application des middleware de route
   └─ Résolution du contrôleur
          ↓

5️⃣ EXÉCUTION CONTRÔLEUR
   ├─ Validation requête (FormRequest)
   ├─ Appel méthode service
   └─ Formatage réponse
          ↓

6️⃣ ENVOI RÉPONSE
   Réponse JSON → PHP-FPM → Nginx → Client

⏱️ Temps total : ~50-200ms selon les requêtes base de données
```

---

<a name="4-installation"></a>
## 4. Installation et Configuration

### 📋 Prérequis

```bash
# Versions requises
PHP:         8.2 ou supérieur
Composer:    2.x
PostgreSQL:  14+ (production)
SQLite:      3.x (développement)

# Vérifier les versions installées
php --version       # doit afficher PHP 8.2.x
composer --version  # doit afficher Composer 2.x.x
psql --version      # doit afficher PostgreSQL 14.x
```

### 🚀 Installation Pas à Pas

```bash
# 1️⃣ Cloner le dépôt
git clone https://github.com/your-username/fitness-pro.git
cd fitness-pro/backend

# 2️⃣ Installer les dépendances PHP
composer install
# Ceci va :
# - Télécharger tous les packages (~50MB vendor/)
# - Installer Laravel, Sanctum, PHPUnit, etc.
# - Configurer l'autoloading
# Durée : 1-3 minutes selon votre connexion

# 3️⃣ Configuration de l'environnement
cp .env.example .env

# 4️⃣ Générer la clé d'application
php artisan key:generate

# 5️⃣ Configuration base de données (SQLite pour le développement)
touch database/database.sqlite

# 6️⃣ Exécuter les migrations
php artisan migrate

# 7️⃣ Peupler la base avec des données de démo
php artisan db:seed

# 8️⃣ Démarrer le serveur de développement
php artisan serve
# L'API sera accessible à :
# 🌐 http://localhost:8000
```

### ⚙️ Configuration de l'Environnement

**`.env` pour le Développement :**
```env
APP_NAME=FitnessPro
APP_ENV=local
APP_KEY=base64:... # Généré par php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# URL Frontend (pour CORS et liens de réinitialisation de mot de passe)
FRONTEND_URL=http://localhost:4200
SANCTUM_STATEFUL_DOMAINS=localhost:4200
SESSION_DOMAIN=localhost

# Base de données (SQLite pour le développement)
DB_CONNECTION=sqlite
DB_DATABASE=./database/database.sqlite

# File d'attente (pilote database pour la simplicité)
QUEUE_CONNECTION=database

# Mail (Mailtrap pour les tests)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username_mailtrap
MAIL_PASSWORD=votre_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@fitnesspro.app
MAIL_FROM_NAME="FitnessPro"

# Configuration du peuplement
RUN_DB_SEEDERS=false
DB_SEEDER_CLASS=ProductionSeeder
```

**`.env` pour la Production :**
```env
APP_NAME=FitnessPro
APP_ENV=production
APP_KEY=base64:... # Clé différente pour la production!
APP_DEBUG=false
APP_URL=https://api.fitnesspro.com

FRONTEND_URL=https://fitnesspro.com
SANCTUM_STATEFUL_DOMAINS=fitnesspro.com
SESSION_DOMAIN=.fitnesspro.com

# Base de données (PostgreSQL sur Neon)
DB_CONNECTION=pgsql
DB_HOST=votre-hote-neon.neon.tech
DB_PORT=5432
DB_DATABASE=fitnesspro
DB_USERNAME=votre_username
DB_PASSWORD=votre_mot_de_passe_securise

# File d'attente (peut être mis à niveau vers Redis/SQS pour l'échelle)
QUEUE_CONNECTION=database

# Mail (SMTP Production)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=votre_cle_api_sendgrid
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@fitnesspro.com
MAIL_FROM_NAME="FitnessPro"

RUN_DB_SEEDERS=false
```

### 🏃 Exécuter l'Application

```bash
# Démarrer le serveur de développement
php artisan serve

# L'application sera accessible à :
# 🌐 http://localhost:8000

# Ce qui se passe en arrière-plan :
# 1. Le serveur PHP intégré démarre
# 2. Laravel s'initialise
# 3. Les routes sont enregistrées
# 4. La pile middleware est prête
# 5. La connexion à la base de données est établie
# 6. L'API est prête à recevoir des requêtes

# Options utiles
php artisan serve --host=0.0.0.0     # Accessible depuis le réseau
php artisan serve --port=8080        # Changer le port

# Afficher les routes
php artisan route:list

# Vider les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### 🔗 Vérifier que le Backend Fonctionne

```bash
# Tester l'endpoint de santé de l'API
curl http://localhost:8000/api/health

# Réponse attendue :
# {"status":"ok","timestamp":"2025-11-04T10:30:00Z"}

# Tester avec le frontend en cours d'exécution :
# 1. Démarrer le backend : php artisan serve (port 8000)
# 2. Démarrer le frontend : ng serve (port 4200)
# 3. Le frontend devrait se connecter à http://localhost:8000/api
```

---

<a name="5-structure"></a>
## 5. Structure Complète du Projet

### 📁 Structure Arborescente Détaillée

```
backend/
├── 📄 composer.json              # Dépendances PHP
├── 📄 artisan                    # Outil CLI Laravel
├── 📄 .env.example               # Template d'environnement
├── 📄 phpunit.xml                # Configuration des tests
│
├── 📁 app/                       # Code de l'application
│   ├── 📁 Console/
│   │   └── Kernel.php            # Commandes planifiées
│   │
│   ├── 📁 Http/
│   │   ├── 📁 Controllers/       # Gestionnaires de requêtes
│   │   │   ├── AuthController.php
│   │   │   ├── WorkoutController.php
│   │   │   ├── GoalController.php
│   │   │   ├── NutritionController.php
│   │   │   └── DashboardController.php
│   │   │
│   │   ├── 📁 Middleware/        # Filtres de requêtes
│   │   │   ├── WorkoutApiLogger.php
│   │   │   ├── WorkoutApiRateLimit.php
│   │   │   └── ValidateWorkoutOwnership.php
│   │   │
│   │   └── 📁 Requests/          # Règles de validation
│   │       ├── Auth/
│   │       │   ├── LoginRequest.php
│   │       │   └── RegisterRequest.php
│   │       └── Workout/
│   │           └── CreateWorkoutRequest.php
│   │
│   ├── 📁 Models/                # Entités de base de données
│   │   ├── User.php
│   │   ├── Workout.php
│   │   ├── Exercise.php
│   │   ├── Goal.php
│   │   └── CalendarTask.php
│   │
│   ├── 📁 Services/              # Logique métier
│   │   ├── AuthService.php
│   │   ├── WorkoutService.php
│   │   ├── GoalService.php
│   │   ├── StatisticsService.php
│   │   └── StreakCalculatorService.php
│   │
│   ├── 📁 Repositories/          # Accès aux données
│   │   ├── Contracts/
│   │   │   ├── WorkoutRepositoryInterface.php
│   │   │   └── GoalRepositoryInterface.php
│   │   ├── WorkoutRepository.php
│   │   └── GoalRepository.php
│   │
│   ├── 📁 Notifications/         # Email & push
│   │   └── ResetPasswordNotification.php
│   │
│   ├── 📁 Traits/                # Code réutilisable
│   │   ├── ApiResponseTrait.php
│   │   └── BelongsToUserTrait.php
│   │
│   └── 📁 Providers/             # Liaisons de services
│       ├── AppServiceProvider.php
│       └── RepositoryServiceProvider.php
│
├── 📁 bootstrap/                 # Bootstrap du framework
│   └── app.php
│
├── 📁 config/                    # Configuration
│   ├── app.php
│   ├── database.php
│   ├── auth.php
│   ├── sanctum.php
│   ├── cors.php
│   └── mail.php
│
├── 📁 database/
│   ├── 📁 migrations/            # Modifications du schéma
│   │   ├── 2024_01_01_create_users_table.php
│   │   ├── 2024_01_02_create_workouts_table.php
│   │   └── 2024_01_03_create_goals_table.php
│   │
│   ├── 📁 seeders/               # Données d'exemple
│   │   ├── DatabaseSeeder.php
│   │   ├── ProductionSeeder.php
│   │   └── ExerciseSeeder.php
│   │
│   └── 📁 factories/             # Générateurs de données de test
│       └── UserFactory.php
│
├── 📁 routes/
│   ├── api.php                   # Endpoints API
│   └── web.php                   # Routes web (minimal)
│
├── 📁 storage/
│   ├── app/
│   ├── framework/
│   └── logs/
│       └── laravel.log           # Logs de l'application
│
└── 📁 tests/
    ├── Feature/                  # Tests d'intégration
    │   ├── AuthTest.php
    │   └── WorkoutTest.php
    └── Unit/                     # Tests unitaires
        └── GoalServiceTest.php
```

### 📖 Fichiers Clés Expliqués

#### 🎯 **routes/api.php** - Endpoints API

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkoutController;

// Routes publiques (pas d'authentification requise)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/password/email', [AuthController::class, 'sendPasswordResetLink']);

// Routes protégées (nécessite authentification)
Route::middleware('auth:sanctum')->group(function () {
    // Routes utilisateur
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Routes entraînement
    Route::apiResource('workouts', WorkoutController::class);
    Route::post('/workouts/logs', [WorkoutController::class, 'completeLog']);

    // Routes objectifs
    Route::apiResource('goals', GoalController::class);
    Route::patch('/goals/{goal}/progress', [GoalController::class, 'updateProgress']);
});
```

**Pourquoi cette structure?**
- Routes publiques accessibles sans jeton
- Routes protégées nécessitent le middleware `auth:sanctum`
- Routes ressources RESTful (index, store, show, update, destroy)
- Actions personnalisées pour la logique métier spécifique

---

<a name="6-request-flow"></a>
## 6. Flux de Requête - De l'Appel API à la Réponse

### 🎬 Exemple Complet : L'Utilisateur Termine un Entraînement

```
┌────────────────────────────────────────────────────────────┐
│     FLUX COMPLET : TERMINER UNE SESSION D'ENTRAÎNEMENT   │
│     (Exemple pédagogique avec tous les détails)           │
└────────────────────────────────────────────────────────────┘


ÉTAPE 1 : 🖱️ L'UTILISATEUR CLIQUE "TERMINER ENTRAÎNEMENT" SUR ANGULAR
──────────────────────────────────────────────────────────────
Frontend : src/app/features/workout/workout.component.ts

completeWorkout(workoutId: number) {
  const payload = {
    workout_id: workoutId,
    exercises: [
      { exercise_id: 1, sets: 3, reps: 12, weight: 50 },
      { exercise_id: 2, sets: 4, reps: 10, weight: 60 }
    ]
  };

  this.http.post('/api/workouts/logs', payload).subscribe();
}


ÉTAPE 2 : 🌐 REQUÊTE HTTP ENVOYÉE
──────────────────────────────────────────────────────────────
POST http://localhost:8000/api/workouts/logs

En-têtes :
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
  Content-Type: application/json
  Accept: application/json

Corps :
{
  "workout_id": 42,
  "exercises": [
    { "exercise_id": 1, "sets": 3, "reps": 12, "weight": 50 },
    { "exercise_id": 2, "sets": 4, "reps": 10, "weight": 60 }
  ]
}


ÉTAPE 3 : 🛣️ ROUTAGE
──────────────────────────────────────────────────────────────
Fichier : routes/api.php

Le routeur trouve :
  POST /api/workouts/logs → WorkoutController@completeLog

Middleware appliqués :
  ├─ Vérification CORS ✅
  ├─ Authentification Sanctum ✅
  ├─ Limitation de débit ✅
  └─ Journalisation API ✅


ÉTAPE 4 : 🔐 AUTHENTIFICATION
──────────────────────────────────────────────────────────────
Middleware : Sanctum

1. Extrait le jeton de l'en-tête Authorization
2. Requête la table personal_access_tokens
3. Trouve l'utilisateur (user_id = 1)
4. Injecte l'utilisateur dans la requête : $request->user()


ÉTAPE 5 : 🎮 LE CONTRÔLEUR REÇOIT LA REQUÊTE
──────────────────────────────────────────────────────────────
Fichier : app/Http/Controllers/WorkoutController.php

public function completeLog(WorkoutCompleteRequest $request)
{
    // La requête est déjà validée par FormRequest
    $validated = $request->validated();

    // Appelle le service pour gérer la logique métier
    $result = $this->workoutService->completeWorkout(
        $request->user(),
        $validated
    );

    // Retourne une réponse JSON formatée
    return $this->success($result, 'Entraînement terminé avec succès');
}


ÉTAPE 6 : ✅ VALIDATION
──────────────────────────────────────────────────────────────
Fichier : app/Http/Requests/Workout/WorkoutCompleteRequest.php

public function rules(): array
{
    return [
        'workout_id' => 'required|exists:workouts,id',
        'exercises' => 'required|array|min:1',
        'exercises.*.exercise_id' => 'required|exists:exercises,id',
        'exercises.*.sets' => 'required|integer|min:1',
        'exercises.*.reps' => 'required|integer|min:1',
        'exercises.*.weight' => 'nullable|numeric|min:0',
    ];
}

// Validation réussie ✅


ÉTAPE 7 : 🧠 LE SERVICE TRAITE LA LOGIQUE MÉTIER
──────────────────────────────────────────────────────────────
Fichier : app/Services/WorkoutService.php

public function completeWorkout(User $user, array $data): Workout
{
    DB::beginTransaction();

    try {
        // 1. Charger l'entraînement
        $workout = $this->workoutRepository->find($data['workout_id']);

        // 2. Vérifier la propriété
        if ($workout->user_id !== $user->id) {
            throw new UnauthorizedException();
        }

        // 3. Créer le journal d'entraînement terminé
        $log = Workout::create([
            'user_id' => $user->id,
            'name' => $workout->name,
            'is_template' => false,
            'completed_at' => now(),
        ]);

        // 4. Attacher les exercices avec les stats
        foreach ($data['exercises'] as $exercise) {
            $log->exercises()->attach($exercise['exercise_id'], [
                'sets' => $exercise['sets'],
                'reps' => $exercise['reps'],
                'weight' => $exercise['weight'] ?? null,
            ]);
        }

        // 5. Mettre à jour la progression des objectifs
        $this->goalService->updateProgressForWorkout($user, $log);

        // 6. Mettre à jour la série
        $this->streakService->calculateStreak($user);

        // 7. Envoyer une notification
        $user->notify(new WorkoutCompletedNotification($log));

        DB::commit();

        return $log->load('exercises');

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}


ÉTAPE 8 : 📦 LE REPOSITORY INTERROGE LA BASE DE DONNÉES
──────────────────────────────────────────────────────────────
Fichier : app/Repositories/WorkoutRepository.php

public function find(int $id): Workout
{
    return Workout::with('exercises')
        ->findOrFail($id);
}

SQL Exécuté :
  SELECT * FROM workouts WHERE id = 42
  SELECT * FROM exercises WHERE id IN (1, 2)


ÉTAPE 9 : 💾 OPÉRATIONS BASE DE DONNÉES
──────────────────────────────────────────────────────────────
PostgreSQL exécute :

BEGIN TRANSACTION;

-- Créer le journal d'entraînement
INSERT INTO workouts (user_id, name, is_template, completed_at, created_at)
VALUES (1, 'Routine Matinale', false, '2025-11-04 10:30:00', NOW());

-- Attacher les exercices
INSERT INTO workout_exercises (workout_id, exercise_id, sets, reps, weight)
VALUES (43, 1, 3, 12, 50), (43, 2, 4, 10, 60);

-- Mettre à jour la progression de l'objectif
UPDATE goals SET progress_percentage = 75 WHERE id = 5;

-- Mettre à jour la série
UPDATE users SET current_streak = 7 WHERE id = 1;

COMMIT;


ÉTAPE 10 : 📬 NOTIFICATION ENVOYÉE
──────────────────────────────────────────────────────────────
Fichier : app/Notifications/WorkoutCompletedNotification.php

public function via($notifiable): array
{
    return ['database', 'mail'];
}

// Stockée dans la table notifications
INSERT INTO notifications (type, notifiable_id, data, created_at)
VALUES ('WorkoutCompleted', 1, '{"workout": "Routine Matinale"}', NOW());

// Email mis en file d'attente (envoyé de manière asynchrone)
INSERT INTO jobs (queue, payload, attempts, created_at)
VALUES ('default', '{"notification": "..."}', 0, NOW());


ÉTAPE 11 : 📤 RÉPONSE ENVOYÉE AU FRONTEND
──────────────────────────────────────────────────────────────
Le contrôleur retourne :

HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "message": "Entraînement terminé avec succès",
  "data": {
    "id": 43,
    "user_id": 1,
    "name": "Routine Matinale",
    "completed_at": "2025-11-04T10:30:00Z",
    "exercises": [
      {
        "id": 1,
        "name": "Pompes",
        "pivot": { "sets": 3, "reps": 12, "weight": 50 }
      },
      {
        "id": 2,
        "name": "Squats",
        "pivot": { "sets": 4, "reps": 10, "weight": 60 }
      }
    ]
  }
}


ÉTAPE 12 : 🎉 LE FRONTEND REÇOIT & AFFICHE
──────────────────────────────────────────────────────────────
Angular met à jour l'interface :
  ✅ Entraînement marqué comme terminé
  ✅ Progression des objectifs mise à jour
  ✅ Compteur de série incrémenté
  ✅ Notification de succès affichée

Temps total : ~150ms
```

### 📊 Diagramme Récapitulatif

```
ACTION UTILISATEUR
   ↓
Requête HTTP (avec jeton JWT)
   ↓
NGINX/Apache → PHP-FPM
   ↓
Bootstrap Laravel
   ↓
Pile Middleware (CORS, Auth, Limitation débit, Journalisation)
   ↓
Routeur → Contrôleur
   ↓
Validation FormRequest
   ↓
Service (Logique Métier)
   ├─> Repository (Requêtes Base de Données)
   ├─> Service Objectifs (Mise à jour Progression)
   ├─> Service Série (Calcul Série)
   └─> Notification (Email en file d'attente)
   ↓
Transaction Base de Données (BEGIN → COMMIT)
   ↓
Le Contrôleur Formate la Réponse (ApiResponseTrait)
   ↓
Réponse JSON → Frontend
   ↓
L'UTILISATEUR VOIT LE RÉSULTAT
```

---

<a name="7-authentication"></a>
## 7. Système d'Authentification

### 🔐 Architecture Laravel Sanctum

```
┌────────────────────────────────────────────────────────────┐
│           SYSTÈME D'AUTHENTIFICATION SANCTUM               │
└────────────────────────────────────────────────────────────┘

📱 FRONTEND (Angular)                  🖥️ BACKEND (Laravel)
┌──────────────────────┐              ┌────────────────────────┐
│                      │              │                        │
│  LoginComponent      │──1.login────>│  AuthController        │
│  ├─ email            │   (POST)     │  ├─ Valider            │
│  └─ password         │              │  ├─ Vérifier identif.  │
│                      │              │  └─ Créer jeton        │
│                      │              │                        │
│                      │<─2.token─────│  Jeton créé :          │
│  AuthService         │   (200 OK)   │  {                     │
│  ├─ Stocker jeton    │              │   "plainTextToken"     │
│  └─ Définir en-tête  │              │  }                     │
│                      │              │                        │
│  localStorage        │              │  Base de données       │
│  └─ auth_token       │              │  └─ personal_access_   │
│                      │              │     tokens             │
│                      │              │                        │
│  TOUTES REQUÊTES     │──3.request──>│                        │
│      ↓               │   + jeton    │  Middleware            │
│  Ajouter En-tête :   │              │  auth:sanctum          │
│  Authorization:      │              │  ├─ Vérifier jeton     │
│  Bearer <token>      │              │  └─ Charger utilisat.  │
│                      │              │                        │
│                      │<─4.data──────│  Données protégées     │
└──────────────────────┘              └────────────────────────┘
```

### 🔄 Flux d'Authentification Complet

```php
// ═══════════════════════════════════════════════════════════
// CONTRÔLEUR AUTH - Login
// ═══════════════════════════════════════════════════════════

public function login(LoginRequest $request)
{
    // 1️⃣ Valider les identifiants
    $credentials = $request->validated();

    // 2️⃣ Tenter l'authentification
    if (!Auth::attempt($credentials)) {
        return $this->error('Identifiants invalides', 401);
    }

    // 3️⃣ Obtenir l'utilisateur authentifié
    $user = Auth::user();

    // 4️⃣ Créer le jeton
    $token = $user->createToken('auth-token')->plainTextToken;

    // 5️⃣ Retourner jeton + données utilisateur
    return $this->success([
        'token' => $token,
        'user' => $user,
    ], 'Connexion réussie');
}
```

### 🔑 Flux de Réinitialisation de Mot de Passe

```
┌────────────────────────────────────────────────────────────┐
│        FLUX DE RÉINITIALISATION DE MOT DE PASSE            │
└────────────────────────────────────────────────────────────┘

1️⃣ L'UTILISATEUR DEMANDE LA RÉINITIALISATION
   Frontend : POST /api/auth/password/email
   Corps : { "email": "user@example.com" }
        ↓

2️⃣ LE BACKEND GÉNÈRE UN JETON
   Fichier : app/Services/AuthService.php

   $token = Str::random(64);

   DB::table('password_reset_tokens')->insert([
       'email' => $email,
       'token' => Hash::make($token),
       'created_at' => now(),
   ]);
        ↓

3️⃣ EMAIL ENVOYÉ
   Fichier : app/Notifications/ResetPasswordNotification.php

   $resetUrl = "{$frontendUrl}/reset-password?token={$token}&email={$email}";

   Le mail envoie le lien à l'utilisateur
        ↓

4️⃣ L'UTILISATEUR CLIQUE SUR LE LIEN
   Le frontend ouvre : /reset-password?token=xxx&email=yyy
   Pré-remplit le champ email (verrouillé)
   L'utilisateur entre le nouveau mot de passe
        ↓

5️⃣ LE FRONTEND SOUMET LE NOUVEAU MOT DE PASSE
   POST /api/auth/password/reset
   Corps : {
       "email": "user@example.com",
       "token": "xxx",
       "password": "nouveaumotdepasse",
       "password_confirmation": "nouveaumotdepasse"
   }
        ↓

6️⃣ LE BACKEND VALIDE & MET À JOUR
   ├─ Vérifier que le jeton existe et n'est pas expiré
   ├─ Vérifier que le jeton correspond
   ├─ Hasher le nouveau mot de passe
   ├─ Mettre à jour l'enregistrement utilisateur
   └─ Supprimer le jeton de réinitialisation
        ↓

7️⃣ SUCCÈS
   L'utilisateur peut maintenant se connecter avec le nouveau mot de passe
```

---

<a name="8-database"></a>
## 8. Base de Données & Modèles

### 💾 Schéma de Base de Données

```
┌────────────────────────────────────────────────────────────┐
│                   SCHÉMA DE BASE DE DONNÉES                │
└────────────────────────────────────────────────────────────┘

users
├─ id (clé primaire)
├─ name
├─ email (unique)
├─ password (haché)
├─ height, weight
├─ current_streak
├─ remember_token
└─ timestamps

      │
      │ 1:N
      ↓

workouts
├─ id (clé primaire)
├─ user_id (clé étrangère → users)
├─ name
├─ description
├─ is_template (boolean)
├─ completed_at (nullable)
└─ timestamps

      │
      │ N:M
      ↓

workout_exercises (pivot)
├─ workout_id (clé étrangère → workouts)
├─ exercise_id (clé étrangère → exercises)
├─ sets
├─ reps
├─ weight
├─ tempo
└─ rest

      │
      ↓

exercises
├─ id (clé primaire)
├─ name
├─ equipment
├─ body_part
├─ difficulty
├─ video_url
└─ timestamps
```

### 📋 Modèles Eloquent

```php
// ═══════════════════════════════════════════════════════════
// MODÈLE USER
// ═══════════════════════════════════════════════════════════

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'height',
        'weight',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ─────────────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────────────

    public function workouts()
    {
        return $this->hasMany(Workout::class);
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function calendarTasks()
    {
        return $this->hasMany(CalendarTask::class);
    }
}
```

```php
// ═══════════════════════════════════════════════════════════
// MODÈLE WORKOUT
// ═══════════════════════════════════════════════════════════

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    use BelongsToUserTrait;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_template',
        'completed_at',
    ];

    protected $casts = [
        'is_template' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exercises()
    {
        return $this->belongsToMany(Exercise::class, 'workout_exercises')
            ->withPivot(['sets', 'reps', 'weight', 'tempo', 'rest'])
            ->withTimestamps();
    }

    // ─────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────

    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }
}
```

### 🔄 Relations Eloquent

```php
// Un-à-Plusieurs (User → Workouts)
$user = User::find(1);
$workouts = $user->workouts; // Tous les entraînements pour cet utilisateur

// Plusieurs-à-Plusieurs (Workout ↔ Exercises)
$workout = Workout::find(1);
$exercises = $workout->exercises; // Tous les exercices dans cet entraînement

// Accéder aux données pivot
foreach ($workout->exercises as $exercise) {
    echo $exercise->pivot->sets;   // 3
    echo $exercise->pivot->reps;   // 12
    echo $exercise->pivot->weight; // 50
}

// Attacher/Détacher
$workout->exercises()->attach($exerciseId, [
    'sets' => 3,
    'reps' => 12,
    'weight' => 50,
]);

$workout->exercises()->detach($exerciseId);
```

---

<a name="9-services"></a>
## 9. Services & Logique Métier

### 🧠 Pattern de la Couche Service

Les services contiennent **toute la logique métier**, gardant les contrôleurs minces.

```php
// ═══════════════════════════════════════════════════════════
// SERVICE WORKOUT - Exemple Complet
// ═══════════════════════════════════════════════════════════

namespace App\Services;

use App\Models\User;
use App\Models\Workout;
use App\Repositories\Contracts\WorkoutRepositoryInterface;
use Illuminate\Support\Facades\DB;

class WorkoutService
{
    public function __construct(
        private WorkoutRepositoryInterface $workoutRepository,
        private GoalService $goalService,
        private StreakCalculatorService $streakService
    ) {}

    // ─────────────────────────────────────────────────────
    // CRÉER ENTRAÎNEMENT
    // ─────────────────────────────────────────────────────

    public function createWorkout(User $user, array $data): Workout
    {
        DB::beginTransaction();

        try {
            // Créer l'entraînement
            $workout = Workout::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_template' => $data['is_template'] ?? false,
            ]);

            // Attacher les exercices
            if (isset($data['exercises'])) {
                foreach ($data['exercises'] as $exercise) {
                    $workout->exercises()->attach($exercise['exercise_id'], [
                        'sets' => $exercise['sets'],
                        'reps' => $exercise['reps'],
                        'weight' => $exercise['weight'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return $workout->load('exercises');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────
    // TERMINER ENTRAÎNEMENT
    // ─────────────────────────────────────────────────────

    public function completeWorkout(User $user, array $data): Workout
    {
        DB::beginTransaction();

        try {
            // Charger le modèle
            $template = $this->workoutRepository->find($data['workout_id']);

            // Créer le journal terminé
            $log = Workout::create([
                'user_id' => $user->id,
                'name' => $template->name,
                'is_template' => false,
                'completed_at' => now(),
            ]);

            // Attacher les exercices avec les stats réelles
            foreach ($data['exercises'] as $exercise) {
                $log->exercises()->attach($exercise['exercise_id'], [
                    'sets' => $exercise['sets'],
                    'reps' => $exercise['reps'],
                    'weight' => $exercise['weight'] ?? null,
                ]);
            }

            // Mettre à jour les objectifs associés
            $this->goalService->updateProgressForWorkout($user, $log);

            // Mettre à jour la série
            $this->streakService->calculateStreak($user);

            DB::commit();

            return $log->load('exercises');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────
    // OBTENIR ENTRAÎNEMENTS UTILISATEUR
    // ─────────────────────────────────────────────────────

    public function getUserWorkouts(User $user, array $filters = [])
    {
        $query = Workout::where('user_id', $user->id);

        // Appliquer les filtres
        if (isset($filters['is_template'])) {
            $query->where('is_template', $filters['is_template']);
        }

        if (isset($filters['completed'])) {
            if ($filters['completed']) {
                $query->whereNotNull('completed_at');
            } else {
                $query->whereNull('completed_at');
            }
        }

        return $query->with('exercises')
            ->latest()
            ->paginate(15);
    }
}
```

---

<a name="10-controllers"></a>
## 10. Contrôleurs & Routes

### 🎮 Pattern de Contrôleur

Les contrôleurs sont **minces** - ils délèguent la logique métier aux services.

```php
// ═══════════════════════════════════════════════════════════
// CONTRÔLEUR WORKOUT - Mince & Propre
// ═══════════════════════════════════════════════════════════

namespace App\Http\Controllers;

use App\Http\Requests\Workout\CreateWorkoutRequest;
use App\Services\WorkoutService;
use Illuminate\Http\JsonResponse;

class WorkoutController extends BaseController
{
    public function __construct(
        private WorkoutService $workoutService
    ) {}

    // ─────────────────────────────────────────────────────
    // INDEX - Lister les entraînements
    // ─────────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $workouts = $this->workoutService->getUserWorkouts(
            auth()->user(),
            request()->only(['is_template', 'completed'])
        );

        return $this->success($workouts);
    }

    // ─────────────────────────────────────────────────────
    // STORE - Créer un entraînement
    // ─────────────────────────────────────────────────────

    public function store(CreateWorkoutRequest $request): JsonResponse
    {
        $workout = $this->workoutService->createWorkout(
            $request->user(),
            $request->validated()
        );

        return $this->success($workout, 'Entraînement créé', 201);
    }

    // ─────────────────────────────────────────────────────
    // SHOW - Obtenir un seul entraînement
    // ─────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $workout = $this->workoutService->getWorkout(
            auth()->user(),
            $id
        );

        return $this->success($workout);
    }

    // ─────────────────────────────────────────────────────
    // UPDATE - Mettre à jour l'entraînement
    // ─────────────────────────────────────────────────────

    public function update(UpdateWorkoutRequest $request, int $id): JsonResponse
    {
        $workout = $this->workoutService->updateWorkout(
            $request->user(),
            $id,
            $request->validated()
        );

        return $this->success($workout, 'Entraînement mis à jour');
    }

    // ─────────────────────────────────────────────────────
    // DESTROY - Supprimer l'entraînement
    // ─────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        $this->workoutService->deleteWorkout(
            auth()->user(),
            $id
        );

        return $this->success(null, 'Entraînement supprimé');
    }
}
```

### 📋 Contrôleur de Base avec ApiResponseTrait

```php
// ═══════════════════════════════════════════════════════════
// CONTRÔLEUR DE BASE - Réponses standardisées
// ═══════════════════════════════════════════════════════════

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;

class BaseController extends Controller
{
    use ApiResponseTrait;
}
```

```php
// ═══════════════════════════════════════════════════════════
// TRAIT API RESPONSE - Réponses JSON cohérentes
// ═══════════════════════════════════════════════════════════

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    protected function success($data = null, string $message = 'Succès', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message = 'Erreur', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
```

---

<a name="11-middleware"></a>
## 11. Middleware & Sécurité

### 🛡️ Pile Middleware

```php
// ═══════════════════════════════════════════════════════════
// WORKOUT API LOGGER - Journaliser toutes les requêtes
// ═══════════════════════════════════════════════════════════

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkoutApiLogger
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::info('Requête API', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => $request->user()?->id,
            'duration_ms' => $duration,
            'status' => $response->status(),
        ]);

        return $response;
    }
}
```

```php
// ═══════════════════════════════════════════════════════════
// LIMITATION DE DÉBIT - Prévenir les abus
// ═══════════════════════════════════════════════════════════

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class WorkoutApiRateLimit
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'api:' . $request->user()?->id ?? $request->ip();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json([
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez ralentir.',
            ], 429);
        }

        RateLimiter::hit($key, 60); // 60 requêtes par minute

        return $next($request);
    }
}
```

### 🔒 Meilleures Pratiques de Sécurité

- ✅ **CORS** configuré dans `config/cors.php`
- ✅ **Protection CSRF** via Sanctum
- ✅ **Limitation de débit** sur toutes les routes API
- ✅ **Injection SQL** empêchée par Eloquent/requêtes préparées
- ✅ **XSS** empêché par réponses JSON (pas de rendu HTML)
- ✅ **Hachage des mots de passe** utilisant bcrypt
- ✅ **Jetons JWT** avec expiration

---

<a name="12-notifications"></a>
## 12. Notifications & Jobs

### 📧 Notifications Email

```php
// ═══════════════════════════════════════════════════════════
// NOTIFICATION RÉINITIALISATION MOT DE PASSE
// ═══════════════════════════════════════════════════════════

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    public function __construct(
        private string $token,
        private string $email
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = config('app.frontend_url')
            . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($this->email);

        return (new MailMessage)
            ->subject('Réinitialiser le mot de passe - FitnessPro')
            ->line('Vous avez demandé à réinitialiser votre mot de passe.')
            ->action('Réinitialiser le mot de passe', $url)
            ->line('Ce lien expirera dans 60 minutes.')
            ->line('Si vous n\'avez pas demandé cela, aucune action n\'est nécessaire.');
    }
}
```

### ⏰ Jobs en File d'Attente

```php
// ═══════════════════════════════════════════════════════════
// JOB RAPPEL ENTRAÎNEMENT
// ═══════════════════════════════════════════════════════════

namespace App\Jobs;

use App\Models\User;
use App\Notifications\WorkoutReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SendWorkoutReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        private User $user
    ) {}

    public function handle(): void
    {
        $this->user->notify(new WorkoutReminderNotification());
    }
}

// Dispatcher le job
SendWorkoutReminderJob::dispatch($user);

// Traiter les jobs en file d'attente
php artisan queue:work
```

---

<a name="13-testing"></a>
## 13. Tests

### 🧪 Tests de Fonctionnalité

```php
// ═══════════════════════════════════════════════════════════
// TEST WORKOUT - Test d'intégration
// ═══════════════════════════════════════════════════════════

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_workout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/workouts', [
                'name' => 'Routine Matinale',
                'description' => 'Entraînement rapide de 30min',
                'is_template' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Entraînement créé',
            ]);

        $this->assertDatabaseHas('workouts', [
            'user_id' => $user->id,
            'name' => 'Routine Matinale',
        ]);
    }

    public function test_user_cannot_view_others_workouts(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $workout = Workout::factory()->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson("/api/workouts/{$workout->id}");

        $response->assertStatus(403);
    }
}
```

### 🎯 Tests Unitaires

```php
// ═══════════════════════════════════════════════════════════
// TEST SERVICE OBJECTIFS - Test unitaire
// ═══════════════════════════════════════════════════════════

namespace Tests\Unit;

use App\Models\Goal;
use App\Models\User;
use App\Services\GoalService;
use Tests\TestCase;

class GoalServiceTest extends TestCase
{
    public function test_calculate_progress_percentage(): void
    {
        $goal = Goal::factory()->create([
            'target_value' => 100,
            'current_progress' => 75,
        ]);

        $service = new GoalService();
        $percentage = $service->calculateProgressPercentage($goal);

        $this->assertEquals(75, $percentage);
    }
}
```

### 🏃 Exécuter les Tests

```bash
# Exécuter tous les tests
php artisan test

# Exécuter un fichier de test spécifique
php artisan test tests/Feature/WorkoutTest.php

# Exécuter avec couverture
php artisan test --coverage

# Filtrer par nom de test
php artisan test --filter=test_user_can_create_workout
```

---

<a name="14-deployment"></a>
## 14. Déploiement

### ▲ Déploiement Render.com

```yaml
# render.yaml
services:
  - type: web
    name: fitnesspro-api
    env: php
    buildCommand: composer install --optimize-autoloader --no-dev
    startCommand: php artisan serve --host=0.0.0.0 --port=$PORT
    envVars:
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: false
      - key: DB_CONNECTION
        value: pgsql
      - key: DB_HOST
        fromDatabase:
          name: fitnesspro-db
          property: host
```

### 🐳 Déploiement Docker

```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Installer les dépendances
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libpq-dev \
    zip \
    unzip

# Installer les extensions PHP
RUN docker-php-ext-install pdo pdo_pgsql

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier l'application
COPY . .

# Installer les dépendances
RUN composer install --optimize-autoloader --no-dev

# Définir les permissions
RUN chown -R www-data:www-data storage bootstrap/cache

CMD php artisan serve --host=0.0.0.0 --port=8000
```

### 🚀 Liste de Vérification du Déploiement

```bash
# 1. Variables d'environnement
✅ APP_ENV=production
✅ APP_DEBUG=false
✅ APP_KEY (générer nouvelle)
✅ Identifiants base de données
✅ FRONTEND_URL
✅ Configuration mail

# 2. Optimiser l'application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Exécuter les migrations
php artisan migrate --force

# 4. Peupler les données de production (première fois uniquement)
php artisan db:seed --class=ProductionSeeder --force

# 5. Vider les caches
php artisan cache:clear
php artisan config:clear
```

---

<a name="15-best-practices"></a>
## 15. Bonnes Pratiques

### 📝 Style de Code

```php
// ═══════════════════════════════════════════════════════════
// BONNES PRATIQUES
// ═══════════════════════════════════════════════════════════

// ✅ Indications de type partout
public function createWorkout(User $user, array $data): Workout
{
    // ...
}

// ✅ Utiliser l'injection de dépendances
public function __construct(
    private WorkoutService $workoutService
) {}

// ✅ Utiliser les transactions pour plusieurs opérations base de données
DB::beginTransaction();
try {
    // Plusieurs opérations
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}

// ✅ Utiliser les scopes Eloquent
$workouts = Workout::templates()->completed()->get();

// ✅ Utiliser les resources pour les réponses API
return WorkoutResource::collection($workouts);

// ✅ Utiliser FormRequests pour la validation
public function store(CreateWorkoutRequest $request)
{
    $validated = $request->validated();
}

// ❌ Éviter la logique dans les contrôleurs
// Les contrôleurs doivent déléguer aux services

// ❌ Éviter le SQL brut
// Utiliser Eloquent ou Query Builder
```

### 🏗️ Directives d'Architecture

1. **Contrôleurs** → Minces, déléguer aux services
2. **Services** → Logique métier, orchestration
3. **Repositories** → Couche d'accès aux données
4. **Modèles** → Relations, scopes, accesseurs
5. **Traits** → Code réutilisable entre modèles
6. **FormRequests** → Règles de validation
7. **Resources** → Formatage des réponses API
8. **Jobs** → Tâches asynchrones
9. **Notifications** → Email, SMS, base de données

---

<a name="16-troubleshooting"></a>
## 16. Dépannage & FAQ

### 🐛 Problèmes Courants

#### ❌ Erreur : Classe non trouvée

**Cause :** Cache de l'autoloader obsolète

**Solution :**
```bash
composer dump-autoload
```

#### ❌ Erreur CORS

**Cause :** URL frontend pas dans les origines autorisées

**Solution :** Vérifier `config/cors.php` :
```php
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:4200')],
```

#### ❌ 401 Non autorisé

**Cause :** Jeton expiré ou invalide

**Solution :**
```bash
# Nettoyer les jetons Sanctum
php artisan sanctum:prune-expired --hours=24
```

#### ❌ Échec de connexion à la base de données

**Cause :** Mauvais identifiants ou base de données non démarrée

**Solution :**
```bash
# Tester la connexion
php artisan tinker
>>> DB::connection()->getPdo();

# Vérifier .env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=fitnesspro
DB_USERNAME=votre_username
DB_PASSWORD=votre_mot_de_passe
```

#### ❌ Échec des migrations

**Cause :** La base de données a déjà des tables

**Solution :**
```bash
# Migration fraîche (⚠️ détruit les données)
php artisan migrate:fresh

# Ou réinitialiser
php artisan migrate:reset
php artisan migrate
```

### 💡 Commandes Utiles

```bash
# Vider tous les caches
php artisan optimize:clear

# Afficher les routes
php artisan route:list

# Générer la clé d'application
php artisan key:generate

# Exécuter les seeders
php artisan db:seed

# Créer une migration
php artisan make:migration create_table_name

# Créer un modèle avec migration
php artisan make:model ModelName -m

# Créer un contrôleur
php artisan make:controller ControllerName

# Créer un service
php artisan make:class Services/ServiceName

# Exécuter le worker de file d'attente
php artisan queue:work

# Afficher les logs
tail -f storage/logs/laravel.log
```

### 📚 Ressources

- [Documentation Laravel](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [ORM Eloquent](https://laravel.com/docs/eloquent)
- [Documentation PostgreSQL](https://www.postgresql.org/docs/)

---

## 🎉 Conclusion

Vous avez maintenant une compréhension complète du backend FitnessPro !

**Points clés :**
- ✅ Architecture en couches (Contrôleur → Service → Repository → Modèle)
- ✅ Authentification Laravel Sanctum
- ✅ Conception API RESTful
- ✅ Relations de base de données avec Eloquent
- ✅ Couche service pour la logique métier
- ✅ Système de file d'attente pour les jobs en arrière-plan
- ✅ Tests complets
- ✅ Déploiement prêt pour la production


---

**Version :** 2.1.0
**Dernière mise à jour :** Novembre 2025
**Auteur :** Ivan Petrov