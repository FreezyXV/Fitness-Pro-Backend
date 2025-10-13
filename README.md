# 🏋️ FitnessPro - Backend API Documentation

> **Le serveur Laravel qui alimente votre parcours fitness** - Une API REST robuste construite avec Laravel 12, gérant l'authentification, les entraînements, la nutrition, et bien plus encore.

---

## 📚 Table des Matières

- [Vue d'Ensemble](#-vue-densemble)
- [Architecture Expliquée](#-architecture-expliquée)
- [Installation & Configuration](#-installation--configuration)
- [Structure de la Base de Données](#-structure-de-la-base-de-données)
- [Points d'API (Endpoints)](#-points-dapi-endpoints)
- [Cycle de Vie d'une Requête](#-cycle-de-vie-dune-requête)
- [Services & Logique Métier](#-services--logique-métier)
- [Authentification & Sécurité](#-authentification--sécurité)
- [Développement](#-développement)
- [Déploiement](#-déploiement)
- [Dépannage](#-dépannage)

---

## 🎯 Vue d'Ensemble

### Qu'est-ce que ce Backend ?

Ce backend est le **cerveau** de l'application FitnessPro. Imaginez-le comme le **chef d'orchestre** d'un restaurant :

- 📥 Il **reçoit les commandes** (requêtes HTTP) du frontend Angular
- 🔐 Il **vérifie l'identité** des clients (authentification JWT avec Laravel Sanctum)
- 🗄️ Il **accède à la base de données** pour stocker et récupérer des informations
- 🔧 Il **traite la logique métier** (calculs de calories, validation de données, etc.)
- 📤 Il **retourne les résultats** formatés en JSON au frontend

### Technologies Principales

```
┌─────────────────────────────────────────────────────────┐
│                    BACKEND STACK                        │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  🐘 PHP 8.2+          Langage de programmation moderne │
│  🎼 Laravel 12        Framework MVC robuste            │
│  🔐 Sanctum           Authentification SPA/API         │
│  🗄️ SQLite/PostgreSQL Base de données relationnelle   │
│  📮 Composer          Gestionnaire de dépendances      │
│  🚀 Fly.io            Plateforme de déploiement cloud  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Capacités du Backend

- ✅ **Authentification JWT** - Connexion sécurisée avec tokens
- ✅ **Gestion des Utilisateurs** - Inscription, profil, préférences
- ✅ **Base d'Exercices** - 800+ exercices avec catégories et muscles ciblés
- ✅ **Système d'Entraînement** - Templates et sessions de workout
- ✅ **Suivi Nutritionnel** - Journaux de repas et calculs caloriques
- ✅ **Gestion des Objectifs** - Objectifs SMART avec suivi de progression
- ✅ **Système de Calendrier** - Planification d'entraînements
- ✅ **Tableau de Bord** - Statistiques et analyses de performance
- ✅ **Système de Gamification** - Achievements et scores

---

## 🏗️ Architecture Expliquée

### Le Pattern MVC (Model-View-Controller)

Laravel utilise l'architecture MVC. Voici comment cela fonctionne dans notre backend :

```
┌────────────────────────────────────────────────────────────────┐
│                    ARCHITECTURE MVC                            │
└────────────────────────────────────────────────────────────────┘

    REQUEST (HTTP)                     RESPONSE (JSON)
         │                                    ▲
         │                                    │
         ▼                                    │
  ┌─────────────────┐                 ┌──────────────┐
  │   ROUTE         │                 │   RESPONSE   │
  │   (api.php)     │                 │   (JSON)     │
  └────────┬────────┘                 └──────▲───────┘
           │                                  │
           │ 1. Route vers le bon Controller  │
           ▼                                  │
  ┌─────────────────────────────────────────────────────┐
  │            CONTROLLER                               │
  │  (AuthController, WorkoutController, etc.)         │
  │  ➜ Reçoit la requête                               │
  │  ➜ Valide les données                              │
  │  ➜ Appelle le Service                              │
  └────────┬───────────────────────────────────▲───────┘
           │                                   │
           │ 2. Délègue au Service             │ 6. Retourne données
           ▼                                   │
  ┌─────────────────────────────────────────────────────┐
  │              SERVICE LAYER                          │
  │  (AuthService, WorkoutService, etc.)               │
  │  ➜ Logique métier complexe                         │
  │  ➜ Transformations de données                      │
  │  ➜ Validations avancées                            │
  └────────┬───────────────────────────────────▲───────┘
           │                                   │
           │ 3. Interagit avec Model           │ 5. Retourne résultats
           ▼                                   │
  ┌─────────────────────────────────────────────────────┐
  │               MODELS                                │
  │  (User, Workout, Exercise, Goal, etc.)             │
  │  ➜ Représentent les tables de la BD                │
  │  ➜ Définissent les relations                       │
  │  ➜ Contiennent les règles de validation            │
  └────────┬───────────────────────────────────▲───────┘
           │                                   │
           │ 4. Requêtes SQL via Eloquent      │
           ▼                                   │
  ┌─────────────────────────────────────────────────────┐
  │            BASE DE DONNÉES                          │
  │  (SQLite en local, PostgreSQL en production)       │
  │  ➜ Stockage persistant des données                 │
  │  ➜ Relations entre tables                          │
  │  ➜ Indexes pour performance                        │
  └─────────────────────────────────────────────────────┘
```

### Structure des Dossiers

```
backend/
│
├── app/                              # Code principal de l'application
│   ├── Http/
│   │   ├── Controllers/              # 🎮 Controllers - Points d'entrée des requêtes
│   │   │   ├── AuthController.php    #    → Authentification (login/register/logout)
│   │   │   ├── WorkoutController.php #    → Gestion des entraînements
│   │   │   ├── ExerciseController.php#    → Base de données d'exercices
│   │   │   ├── NutritionController.php#   → Suivi nutritionnel
│   │   │   ├── GoalController.php    #    → Gestion des objectifs
│   │   │   ├── CalendarController.php#    → Calendrier et tâches
│   │   │   └── DashboardController.php#   → Statistiques et analytics
│   │   │
│   │   └── Middleware/               # 🛡️ Filtres de requêtes
│   │       └── Authenticate.php      #    → Vérification de l'authentification
│   │
│   ├── Models/                       # 🗂️ Models - Représentation des données
│   │   ├── User.php                  #    → Utilisateurs
│   │   ├── Workout.php               #    → Entraînements
│   │   ├── Exercise.php              #    → Exercices
│   │   ├── WorkoutExercise.php       #    → Liaison workout-exercise
│   │   ├── Goal.php                  #    → Objectifs utilisateur
│   │   ├── MealEntry.php             #    → Entrées nutritionnelles
│   │   ├── CalendarTask.php          #    → Tâches du calendrier
│   │   └── Achievement.php           #    → Système d'achievements
│   │
│   ├── Services/                     # 🔧 Services - Logique métier
│   │   ├── AuthService.php           #    → Logique d'authentification
│   │   ├── WorkoutService.php        #    → Logique d'entraînement
│   │   ├── ExerciseService.php       #    → Logique d'exercices
│   │   ├── NutritionService.php      #    → Calculs nutritionnels
│   │   └── GoalService.php           #    → Gestion des objectifs
│   │
│   └── Traits/                       # ♻️ Traits - Code réutilisable
│       └── HasAchievements.php       #    → Fonctionnalités d'achievements
│
├── database/                         # 📊 Base de données
│   ├── migrations/                   #    → Schéma de la base de données (versions)
│   ├── seeders/                      #    → Données de test
│   └── database.sqlite               #    → Base SQLite locale
│
├── routes/                           # 🛣️ Routes - Définition des endpoints
│   ├── api.php                       #    → Routes API (utilisées par frontend)
│   └── web.php                       #    → Routes web (interfaces Laravel)
│
├── config/                           # ⚙️ Configuration
│   ├── cors.php                      #    → Configuration CORS
│   ├── sanctum.php                   #    → Configuration authentification
│   └── database.php                  #    → Configuration base de données
│
├── storage/                          # 💾 Stockage
│   ├── logs/                         #    → Logs de l'application
│   └── app/                          #    → Fichiers uploadés
│
├── .env                              # 🔐 Variables d'environnement
├── composer.json                     # 📦 Dépendances PHP
└── artisan                           # 🔨 CLI Laravel
```

---

## 🚀 Installation & Configuration

### Prérequis

Avant de commencer, assurez-vous d'avoir :

```bash
# 1. PHP 8.2 ou supérieur
php --version
# Doit afficher: PHP 8.2.x ou plus

# 2. Composer (gestionnaire de dépendances PHP)
composer --version
# Doit afficher: Composer version 2.x

# 3. Base de données (SQLite ou PostgreSQL)
# SQLite est inclus dans PHP
# Pour PostgreSQL:
psql --version
```

### Installation Étape par Étape

#### 1️⃣ Cloner et Installer les Dépendances

```bash
# Se déplacer dans le dossier backend
cd backend

# Installer les dépendances PHP
composer install
# Cela télécharge tous les packages Laravel nécessaires

# Copier le fichier d'environnement
cp .env.example .env
# ou simplement:
# cp .env .env.local
```

#### 2️⃣ Configurer la Base de Données

**Option A: SQLite (recommandé pour développement local)**

```bash
# Créer le fichier de base de données SQLite
touch database/database.sqlite

# Dans le fichier .env, configurer:
DB_CONNECTION=sqlite
DB_DATABASE=/chemin/absolu/vers/backend/database/database.sqlite
```

**Option B: PostgreSQL (recommandé pour production)**

```bash
# Dans le fichier .env, configurer:
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fitness_pro
DB_USERNAME=votre_username
DB_PASSWORD=votre_password
```

#### 3️⃣ Générer la Clé d'Application

```bash
# Générer une clé de sécurité unique
php artisan key:generate
# Cette clé est utilisée pour chiffrer les données sensibles
```

#### 4️⃣ Exécuter les Migrations

```bash
# Créer toutes les tables dans la base de données
php artisan migrate
# Cela exécute tous les fichiers dans database/migrations/

# Si vous voulez repartir de zéro:
php artisan migrate:fresh
# ⚠️ Attention: cela supprime toutes les données existantes!
```

#### 5️⃣ Peupler la Base de Données (Optionnel)

```bash
# Ajouter des données de test
php artisan db:seed
# Cela crée des utilisateurs, exercices, et workouts de démonstration

# Pour tout recréer avec des données de test:
php artisan migrate:fresh --seed
```

#### 6️⃣ Lancer le Serveur de Développement

```bash
# Option 1: Serveur Laravel sur localhost:8000
php artisan serve

# Option 2: Serveur sur un port spécifique
php artisan serve --port=8080

# Option 3: Serveur accessible depuis le réseau
php artisan serve --host=0.0.0.0 --port=8000
```

### Configuration Détaillée du Fichier `.env`

```env
# ======================
# 🚀 APPLICATION
# ======================
APP_NAME=FitnessPro
APP_ENV=local                        # local | production
APP_KEY=base64:xxxxx                 # Généré par php artisan key:generate
APP_DEBUG=true                       # true en dev, false en prod
APP_URL=http://localhost:8000        # URL du backend
FRONTEND_URL=http://localhost:4200   # URL du frontend (pour CORS)

# ======================
# 🗄️ BASE DE DONNÉES
# ======================
DB_CONNECTION=sqlite                 # sqlite | pgsql | mysql
DB_DATABASE=/path/to/database.sqlite # Chemin absolu pour SQLite

# Pour PostgreSQL/MySQL:
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=fitness_pro
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# ======================
# 🔐 AUTHENTIFICATION
# ======================
SANCTUM_STATEFUL_DOMAINS=localhost:4200,127.0.0.1:4200
SANCTUM_EXPIRATION=1440              # Durée du token en minutes (24h)
SESSION_COOKIE=fitness_pro_session

# ======================
# 🌐 CORS
# ======================
CORS_ALLOWED_ORIGINS=http://localhost:4200,http://127.0.0.1:4200
CORS_SUPPORTS_CREDENTIALS=true

# ======================
# 📧 MAIL (pour reset password)
# ======================
MAIL_MAILER=log                      # log | smtp | sendmail
MAIL_FROM_ADDRESS=noreply@fitnesspro.com
MAIL_FROM_NAME="${APP_NAME}"

# Pour SMTP en production:
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.mailtrap.io
# MAIL_PORT=2525
# MAIL_USERNAME=your_username
# MAIL_PASSWORD=your_password
# MAIL_ENCRYPTION=tls
```

---

## 🗄️ Structure de la Base de Données

### Vue d'Ensemble des Tables

Notre base de données contient **15 tables principales** organisées autour de 5 domaines fonctionnels :

```
┌─────────────────────────────────────────────────────────────┐
│              SCHÉMA DE LA BASE DE DONNÉES                   │
└─────────────────────────────────────────────────────────────┘

1. AUTHENTIFICATION & UTILISATEURS
   ├─ users                    → Comptes utilisateurs
   ├─ personal_access_tokens   → Tokens JWT Sanctum
   └─ password_reset_tokens    → Tokens de réinitialisation

2. EXERCICES & ENTRAÎNEMENTS
   ├─ exercises                → Base de données d'exercices
   ├─ workouts                 → Templates et sessions de workout
   └─ workout_exercises        → Liaison workout-exercise (pivot)

3. NUTRITION
   ├─ aliments                 → Base de données alimentaire
   ├─ meal_entries             → Journal des repas
   ├─ nutrition_goals          → Objectifs nutritionnels
   └─ user_diets               → Plans alimentaires personnalisés

4. OBJECTIFS & PROGRESSION
   ├─ goals                    → Objectifs fitness utilisateur
   └─ calendar_tasks           → Tâches et planification

5. GAMIFICATION
   ├─ achievements             → Succès déblocables
   ├─ user_achievements        → Achievements utilisateur (pivot)
   └─ user_scores              → Système de points
```

### 1️⃣ Table `users` - Utilisateurs

**Rôle**: Stocke les comptes utilisateurs avec leurs informations de profil.

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(255) NOT NULL,      -- Prénom
    last_name VARCHAR(255) NOT NULL,       -- Nom
    name VARCHAR(255) NOT NULL,            -- Nom complet (calculé)
    email VARCHAR(255) UNIQUE NOT NULL,    -- Email (unique, pour login)
    password VARCHAR(255) NOT NULL,        -- Mot de passe hashé (bcrypt)
    email_verified_at TIMESTAMP NULL,      -- Date de vérification email
    remember_token VARCHAR(100) NULL,      -- Token "se souvenir de moi"
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Index pour performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_created_at ON users(created_at);
```

**Exemple de données**:
```json
{
  "id": 1,
  "first_name": "Ivan",
  "last_name": "Petrov",
  "name": "Ivan Petrov",
  "email": "ivanpetrov@mail.com",
  "email_verified_at": "2025-01-10T10:00:00Z",
  "created_at": "2025-01-10T10:00:00Z"
}
```

**Relations Eloquent** ([app/Models/User.php](app/Models/User.php:1)):
```php
class User extends Authenticatable {
    // Un utilisateur a plusieurs workouts
    public function workouts() {
        return $this->hasMany(Workout::class);
    }

    // Un utilisateur a plusieurs objectifs
    public function goals() {
        return $this->hasMany(Goal::class);
    }

    // Un utilisateur a plusieurs entrées de repas
    public function mealEntries() {
        return $this->hasMany(MealEntry::class);
    }
}
```

### 2️⃣ Table `personal_access_tokens` - Tokens JWT

**Rôle**: Gère l'authentification JWT avec Laravel Sanctum.

```sql
CREATE TABLE personal_access_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,  -- Type de modèle (User)
    tokenable_id BIGINT NOT NULL,          -- ID de l'utilisateur
    name VARCHAR(255) NOT NULL,            -- Nom du token
    token VARCHAR(64) UNIQUE NOT NULL,     -- Hash du token (SHA-256)
    abilities TEXT NULL,                   -- Permissions (JSON)
    expires_at TIMESTAMP NULL,             -- Date d'expiration
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    FOREIGN KEY (tokenable_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour performance
CREATE INDEX idx_tokens_tokenable ON personal_access_tokens(tokenable_type, tokenable_id);
CREATE INDEX idx_tokens_token ON personal_access_tokens(token);
```

**Fonctionnement**:
1. **Login**: Le backend génère un token aléatoire
2. **Hash**: Le token est hashé en SHA-256 avant stockage
3. **Retour**: Le token original est envoyé au frontend
4. **Validation**: À chaque requête, le frontend envoie le token qui est re-hashé et vérifié

### 3️⃣ Table `exercises` - Base de Données d'Exercices

**Rôle**: Contient 800+ exercices avec détails, muscles ciblés, et équipement.

```sql
CREATE TABLE exercises (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,            -- Nom de l'exercice
    bodyPart VARCHAR(100) NOT NULL,        -- Partie du corps (chest, back, legs...)
    equipment VARCHAR(100) NOT NULL,       -- Équipement requis
    gifUrl TEXT NULL,                      -- URL du GIF de démonstration
    target VARCHAR(100) NOT NULL,          -- Muscle principal ciblé
    secondaryMuscles JSON NULL,            -- Muscles secondaires (tableau JSON)
    instructions JSON NULL,                -- Instructions étape par étape (tableau JSON)
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Index pour recherche et filtrage
CREATE INDEX idx_exercises_bodypart ON exercises(bodyPart);
CREATE INDEX idx_exercises_equipment ON exercises(equipment);
CREATE INDEX idx_exercises_target ON exercises(target);
CREATE INDEX idx_exercises_name ON exercises(name);
```

**Exemple de données**:
```json
{
  "id": 42,
  "name": "barbell bench press",
  "bodyPart": "chest",
  "equipment": "barbell",
  "gifUrl": "https://exercisedb.p.rapidapi.com/image/42.gif",
  "target": "pectorals",
  "secondaryMuscles": ["triceps", "shoulders"],
  "instructions": [
    "Lie on a flat bench with feet firmly on the ground",
    "Grip the barbell slightly wider than shoulder width",
    "Lower the bar to your chest in a controlled manner",
    "Press the bar back up until arms are fully extended"
  ]
}
```

### 4️⃣ Table `workouts` - Entraînements

**Rôle**: Stocke à la fois les **templates** (modèles réutilisables) et les **sessions** (entraînements effectués).

```sql
CREATE TABLE workouts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,               -- ID de l'utilisateur
    name VARCHAR(255) NOT NULL,            -- Nom du workout
    description TEXT NULL,                 -- Description
    is_template BOOLEAN DEFAULT FALSE,     -- TRUE = template, FALSE = session effectuée
    duration INTEGER NULL,                 -- Durée en minutes
    date DATE NULL,                        -- Date de la session (NULL pour templates)
    notes TEXT NULL,                       -- Notes personnelles
    status VARCHAR(50) DEFAULT 'pending',  -- pending | in_progress | completed
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour performance
CREATE INDEX idx_workouts_user_id ON workouts(user_id);
CREATE INDEX idx_workouts_is_template ON workouts(is_template);
CREATE INDEX idx_workouts_date ON workouts(date);
CREATE INDEX idx_workouts_status ON workouts(status);
```

**Différence Template vs Session**:
- **Template** (`is_template = true`): Modèle réutilisable créé par l'utilisateur
  - Exemple: "Full Body Workout", "Leg Day", "Push Day"
  - Pas de date, pas de durée réelle

- **Session** (`is_template = false`): Entraînement réellement effectué
  - Créé en copiant un template ou from scratch
  - Contient date, durée effective, notes de session

### 5️⃣ Table `workout_exercises` - Liaison Workout-Exercise

**Rôle**: Table pivot qui connecte les workouts aux exercices avec les détails (séries, reps, poids).

```sql
CREATE TABLE workout_exercises (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    workout_id BIGINT NOT NULL,            -- ID du workout
    exercise_id BIGINT NOT NULL,           -- ID de l'exercice
    sets INTEGER NOT NULL DEFAULT 3,       -- Nombre de séries
    reps INTEGER NOT NULL DEFAULT 10,      -- Répétitions par série
    weight DECIMAL(5,2) NULL,              -- Poids utilisé (kg)
    rest_seconds INTEGER DEFAULT 60,       -- Temps de repos (secondes)
    order INTEGER NOT NULL DEFAULT 0,      -- Ordre dans le workout
    notes TEXT NULL,                       -- Notes sur l'exercice
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
);

-- Index pour performance
CREATE INDEX idx_workout_exercises_workout_id ON workout_exercises(workout_id);
CREATE INDEX idx_workout_exercises_exercise_id ON workout_exercises(exercise_id);
CREATE INDEX idx_workout_exercises_order ON workout_exercises(workout_id, order);
```

**Exemple de données**:
```json
{
  "id": 1,
  "workout_id": 5,
  "exercise_id": 42,
  "sets": 4,
  "reps": 8,
  "weight": 80.5,
  "rest_seconds": 90,
  "order": 1,
  "notes": "Form was excellent, felt strong today"
}
```

### 6️⃣ Table `goals` - Objectifs Fitness

**Rôle**: Stocke les objectifs utilisateur avec suivi de progression.

```sql
CREATE TABLE goals (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,           -- Titre de l'objectif
    description TEXT NULL,                 -- Description détaillée
    target_value DECIMAL(8,2) NOT NULL,    -- Valeur cible (ex: 80kg, 100reps)
    current_value DECIMAL(8,2) DEFAULT 0,  -- Valeur actuelle
    unit VARCHAR(50) NULL,                 -- Unité (kg, reps, minutes...)
    category VARCHAR(50) NOT NULL,         -- weight | strength | endurance | nutrition
    start_date DATE NOT NULL,              -- Date de début
    target_date DATE NOT NULL,             -- Date cible d'accomplissement
    status VARCHAR(50) DEFAULT 'active',   -- active | completed | abandoned
    is_achieved BOOLEAN DEFAULT FALSE,     -- Objectif atteint?
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour requêtes fréquentes
CREATE INDEX idx_goals_user_id ON goals(user_id);
CREATE INDEX idx_goals_status ON goals(status);
CREATE INDEX idx_goals_category ON goals(category);
CREATE INDEX idx_goals_target_date ON goals(target_date);
```

**Exemple de données**:
```json
{
  "id": 1,
  "user_id": 1,
  "title": "Reach 100kg Bench Press",
  "description": "Progressive overload with 2.5kg increase per week",
  "target_value": 100.0,
  "current_value": 85.0,
  "unit": "kg",
  "category": "strength",
  "start_date": "2025-01-01",
  "target_date": "2025-06-01",
  "status": "active",
  "is_achieved": false,
  "progress_percentage": 85.0  // Calculé: (85/100) * 100
}
```

### 7️⃣ Table `meal_entries` - Journal Nutritionnel

**Rôle**: Enregistre tous les repas consommés par l'utilisateur avec détails nutritionnels.

```sql
CREATE TABLE meal_entries (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    date DATE NOT NULL,                    -- Date du repas
    meal_type VARCHAR(50) NOT NULL,        -- breakfast | lunch | dinner | snack
    name VARCHAR(255) NOT NULL,            -- Nom de l'aliment/repas
    calories INTEGER NOT NULL,             -- Calories totales
    protein DECIMAL(5,2) DEFAULT 0,        -- Protéines (g)
    carbs DECIMAL(5,2) DEFAULT 0,          -- Glucides (g)
    fats DECIMAL(5,2) DEFAULT 0,           -- Lipides (g)
    quantity DECIMAL(5,2) DEFAULT 1,       -- Quantité
    unit VARCHAR(20) DEFAULT 'serving',    -- Unité (g, ml, serving...)
    notes TEXT NULL,                       -- Notes
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour agrégations par date
CREATE INDEX idx_meal_entries_user_date ON meal_entries(user_id, date);
CREATE INDEX idx_meal_entries_meal_type ON meal_entries(meal_type);
CREATE INDEX idx_meal_entries_date ON meal_entries(date);
```

**Exemple de données**:
```json
{
  "id": 1,
  "user_id": 1,
  "date": "2025-01-13",
  "meal_type": "breakfast",
  "name": "Oatmeal with banana and almonds",
  "calories": 350,
  "protein": 12.5,
  "carbs": 55.0,
  "fats": 8.5,
  "quantity": 1.0,
  "unit": "serving",
  "notes": "Added honey for taste"
}
```

### Relations Entre Tables

```
┌──────────────────────────────────────────────────────────────┐
│              RELATIONS ENTRE TABLES                          │
└──────────────────────────────────────────────────────────────┘

users (1) ───────────────────┐
  │                           │
  │ (1:M)                     │ (1:M)
  │                           │
  ├─→ workouts (M)            ├─→ goals (M)
  │     │                     │
  │     │ (M:M via pivot)     │
  │     │                     │
  │     └─→ workout_exercises ├─→ meal_entries (M)
  │             │              │
  │             │ (M:1)        │
  │             ▼              │
  │         exercises (M)      │
  │                            │
  └─→ personal_access_tokens (M)

Légende:
  (1)   = Un seul
  (M)   = Plusieurs
  (1:M) = One-to-Many (un utilisateur a plusieurs workouts)
  (M:M) = Many-to-Many (un workout a plusieurs exercices)
  (M:1) = Many-to-One (plusieurs workout_exercises → un exercise)
```

---

## 🔌 Points d'API (Endpoints)

### Format de Réponse Standard

Toutes les réponses API suivent ce format JSON standardisé :

**✅ Succès**:
```json
{
  "success": true,
  "data": { /* résultats ici */ },
  "message": "Operation successful",
  "timestamp": "2025-01-13T10:30:00Z"
}
```

**❌ Erreur**:
```json
{
  "success": false,
  "message": "Descriptive error message",
  "errors": {
    "field_name": ["Validation error message"]
  },
  "code": "ERROR_CODE",
  "timestamp": "2025-01-13T10:30:00Z"
}
```

### 1️⃣ Authentification - `/api/auth`

#### **POST `/api/auth/register`** - Inscription

**Requête**:
```json
{
  "first_name": "Ivan",
  "last_name": "Petrov",
  "email": "ivanpetrov@mail.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "acceptTerms": true
}
```

**Réponse** (201 Created):
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "first_name": "Ivan",
      "last_name": "Petrov",
      "name": "Ivan Petrov",
      "email": "ivanpetrov@mail.com",
      "email_verified_at": "2025-01-13T10:00:00Z",
      "created_at": "2025-01-13T10:00:00Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
  },
  "message": "User registered successfully"
}
```

**Logique Backend** ([app/Services/AuthService.php](app/Services/AuthService.php:19)):
```php
public function registerUser(array $data): array {
    DB::beginTransaction();

    try {
        // 1. Créer l'utilisateur avec mot de passe hashé
        $user = User::create([
            'first_name' => strtolower(trim($data['first_name'])),
            'last_name' => strtolower(trim($data['last_name'])),
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'email_verified_at' => now()  // Auto-vérification pour dev
        ]);

        // 2. Créer un token JWT pour l'utilisateur
        $tokenName = 'FitnessPro_' . now()->timestamp;
        $token = $user->createToken($tokenName)->plainTextToken;

        DB::commit();

        return ['user' => $user->fresh(), 'token' => $token];
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

#### **POST `/api/auth/login`** - Connexion

**Requête**:
```json
{
  "email": "ivanpetrov@mail.com",
  "password": "SecurePass123!",
  "rememberMe": true
}
```

**Réponse** (200 OK):
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Ivan Petrov",
      "email": "ivanpetrov@mail.com"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
    "refresh_token": "2|xyz9876543210zyxwvutsrqponmlkjihgfedcba"  // Si rememberMe = true
  },
  "message": "Login successful"
}
```

**Logique Backend** ([app/Services/AuthService.php](app/Services/AuthService.php:117)):
```php
public function loginUser(array $credentials): ?array {
    $email = strtolower(trim($credentials['email']));
    $password = $credentials['password'];

    // 1. Trouver l'utilisateur
    $user = User::where('email', $email)->first();

    // 2. Vérifier le mot de passe avec bcrypt
    if (!$user || !Hash::check($password, $user->password)) {
        Log::warning('Invalid credentials', ['email' => $email]);
        return null;  // Identifiants invalides
    }

    // 3. Révoquer tous les anciens tokens
    $user->tokens()->delete();

    // 4. Générer nouveaux tokens (access + refresh si rememberMe)
    $tokens = $this->generateTokens($user, $credentials['rememberMe'] ?? false);

    return array_merge(['user' => $user->fresh()], $tokens);
}
```

#### **GET `/api/auth/me`** - Obtenir Utilisateur Courant

**Headers**:
```
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890
```

**Réponse** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Ivan Petrov",
    "email": "ivanpetrov@mail.com",
    "email_verified_at": "2025-01-13T10:00:00Z",
    "created_at": "2025-01-13T10:00:00Z"
  }
}
```

#### **POST `/api/auth/logout`** - Déconnexion

**Headers**:
```
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890
```

**Réponse** (200 OK):
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**Logique Backend** ([app/Services/AuthService.php](app/Services/AuthService.php:184)):
```php
public function logoutUser(User $user): bool {
    try {
        // Supprimer le token courant
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        Log::info('Logout successful', ['user_id' => $user->id]);
        return true;
    } catch (\Exception $e) {
        Log::error('Logout failed', ['error' => $e->getMessage()]);
        return false;
    }
}
```

#### **POST `/api/auth/password/email`** - Demande de Reset Password

**Requête**:
```json
{
  "email": "ivanpetrov@mail.com"
}
```

**Réponse** (200 OK):
```json
{
  "success": true,
  "message": "Password reset link sent to your email"
}
```

#### **POST `/api/auth/password/direct-reset`** - Reset Direct (Sans Email)

**Requête**:
```json
{
  "email": "ivanpetrov@mail.com",
  "password": "NewSecurePass123!",
  "password_confirmation": "NewSecurePass123!"
}
```

**Réponse** (200 OK):
```json
{
  "success": true,
  "message": "Password has been reset successfully"
}
```

### 2️⃣ Exercices - `/api/exercises`

#### **GET `/api/exercises`** - Lister Tous les Exercices

**Query Parameters**:
- `?bodyPart=chest` - Filtrer par partie du corps
- `?equipment=barbell` - Filtrer par équipement
- `?search=bench` - Recherche par nom

**Exemple**: `GET /api/exercises?bodyPart=chest&equipment=barbell`

**Réponse** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "name": "barbell bench press",
      "bodyPart": "chest",
      "equipment": "barbell",
      "target": "pectorals",
      "gifUrl": "https://exercisedb.p.rapidapi.com/image/42.gif",
      "secondaryMuscles": ["triceps", "shoulders"],
      "instructions": [
        "Lie on a flat bench...",
        "Grip the barbell...",
        "Lower the bar...",
        "Press the bar back up..."
      ]
    },
    // ... autres exercices
  ],
  "message": "Exercises retrieved successfully"
}
```

#### **GET `/api/exercises/{id}`** - Détails d'un Exercice

**Réponse** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 42,
    "name": "barbell bench press",
    "bodyPart": "chest",
    "equipment": "barbell",
    "target": "pectorals",
    "secondaryMuscles": ["triceps", "shoulders"],
    "instructions": [/* instructions détaillées */]
  }
}
```

### 3️⃣ Workouts - `/api/workouts`

#### **GET `/api/workouts/templates`** - Lister Templates de Workout

**Headers**:
```
Authorization: Bearer {token}
```

**Réponse** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Full Body Workout",
      "description": "Complete full body training session",
      "is_template": true,
      "exercises": [
        {
          "id": 42,
          "name": "barbell bench press",
          "sets": 4,
          "reps": 8,
          "weight": 80,
          "rest_seconds": 90,
          "order": 1
        },
        // ... autres exercices
      ],
      "created_at": "2025-01-10T10:00:00Z"
    }
  ]
}
```

#### **POST `/api/workouts/templates`** - Créer Template de Workout

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Requête**:
```json
{
  "name": "Push Day",
  "description": "Chest, shoulders, and triceps",
  "exercises": [
    {
      "exercise_id": 42,
      "sets": 4,
      "reps": 8,
      "weight": 80,
      "rest_seconds": 90,
      "order": 1,
      "notes": "Focus on form"
    },
    {
      "exercise_id": 125,
      "sets": 3,
      "reps": 12,
      "weight": 15,
      "rest_seconds": 60,
      "order": 2
    }
  ]
}
```

**Réponse** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "Push Day",
    "description": "Chest, shoulders, and triceps",
    "is_template": true,
    "exercises": [/* détails des exercices */],
    "created_at": "2025-01-13T10:30:00Z"
  },
  "message": "Workout template created successfully"
}
```

**Logique Backend** ([app/Services/WorkoutService.php](app/Services/WorkoutService.php:1)):
```php
public function createWorkoutTemplate(array $data, User $user): Workout {
    DB::beginTransaction();

    try {
        // 1. Créer le workout template
        $workout = Workout::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_template' => true  // Marquer comme template
        ]);

        // 2. Attacher les exercices
        foreach ($data['exercises'] as $exerciseData) {
            WorkoutExercise::create([
                'workout_id' => $workout->id,
                'exercise_id' => $exerciseData['exercise_id'],
                'sets' => $exerciseData['sets'],
                'reps' => $exerciseData['reps'],
                'weight' => $exerciseData['weight'] ?? null,
                'rest_seconds' => $exerciseData['rest_seconds'] ?? 60,
                'order' => $exerciseData['order'],
                'notes' => $exerciseData['notes'] ?? null
            ]);
        }

        DB::commit();

        return $workout->load('exercises');  // Charger relations
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

#### **POST `/api/workouts/start`** - Démarrer une Session

**Requête**:
```json
{
  "template_id": 1,  // Optionnel: ID du template à copier
  "name": "Push Day Session",
  "date": "2025-01-13"
}
```

**Réponse** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 10,
    "name": "Push Day Session",
    "is_template": false,
    "date": "2025-01-13",
    "status": "in_progress",
    "exercises": [/* exercices copiés du template */],
    "started_at": "2025-01-13T14:30:00Z"
  },
  "message": "Workout session started"
}
```

#### **POST `/api/workouts/logs/{id}/complete`** - Compléter une Session

**Requête**:
```json
{
  "duration": 75,  // minutes
  "notes": "Great session, felt strong today",
  "exercises": [
    {
      "workout_exercise_id": 42,
      "actual_sets": 4,
      "actual_reps": 8,
      "actual_weight": 82.5  // Progression!
    }
  ]
}
```

**Réponse** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 10,
    "status": "completed",
    "duration": 75,
    "notes": "Great session, felt strong today",
    "completed_at": "2025-01-13T15:45:00Z"
  },
  "message": "Workout session completed"
}
```

### 4️⃣ Nutrition - `/api/nutrition`

#### **GET `/api/nutrition/daily-summary/{date}`** - Résumé Journalier

**Exemple**: `GET /api/nutrition/daily-summary/2025-01-13`

**Réponse** (200 OK):
```json
{
  "success": true,
  "data": {
    "date": "2025-01-13",
    "totals": {
      "calories": 2150,
      "protein": 165.5,
      "carbs": 225.0,
      "fats": 70.5
    },
    "goals": {
      "calories": 2500,
      "protein": 180,
      "carbs": 250,
      "fats": 80
    },
    "percentages": {
      "calories": 86,
      "protein": 92,
      "carbs": 90,
      "fats": 88
    },
    "meals": {
      "breakfast": [
        {
          "id": 1,
          "name": "Oatmeal with banana",
          "calories": 350,
          "protein": 12.5,
          "carbs": 55.0,
          "fats": 8.5
        }
      ],
      "lunch": [/* repas du midi */],
      "dinner": [/* repas du soir */],
      "snack": [/* collations */]
    }
  }
}
```

**Logique Backend** ([app/Services/NutritionService.php](app/Services/NutritionService.php:1)):
```php
public function getDailySummary(User $user, string $date): array {
    // 1. Récupérer tous les repas du jour
    $meals = MealEntry::where('user_id', $user->id)
        ->where('date', $date)
        ->get();

    // 2. Calculer les totaux
    $totals = [
        'calories' => $meals->sum('calories'),
        'protein' => $meals->sum('protein'),
        'carbs' => $meals->sum('carbs'),
        'fats' => $meals->sum('fats')
    ];

    // 3. Récupérer les objectifs
    $goals = $user->nutritionGoals()->latest()->first();

    // 4. Calculer les pourcentages
    $percentages = [
        'calories' => round(($totals['calories'] / $goals->calories) * 100),
        'protein' => round(($totals['protein'] / $goals->protein) * 100),
        'carbs' => round(($totals['carbs'] / $goals->carbs) * 100),
        'fats' => round(($totals['fats'] / $goals->fats) * 100)
    ];

    // 5. Grouper par type de repas
    $mealsByType = $meals->groupBy('meal_type');

    return [
        'date' => $date,
        'totals' => $totals,
        'goals' => $goals,
        'percentages' => $percentages,
        'meals' => $mealsByType
    ];
}
```

#### **POST `/api/nutrition/meals`** - Ajouter un Repas

**Requête**:
```json
{
  "date": "2025-01-13",
  "meal_type": "breakfast",
  "name": "Greek yogurt with berries",
  "calories": 250,
  "protein": 20.0,
  "carbs": 30.0,
  "fats": 5.0,
  "quantity": 1.0,
  "unit": "serving",
  "notes": "Added honey for sweetness"
}
```

**Réponse** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 15,
    "date": "2025-01-13",
    "meal_type": "breakfast",
    "name": "Greek yogurt with berries",
    "calories": 250,
    "protein": 20.0,
    "carbs": 30.0,
    "fats": 5.0,
    "created_at": "2025-01-13T08:30:00Z"
  },
  "message": "Meal entry added successfully"
}
```

### 5️⃣ Goals - `/api/goals`

#### **GET `/api/goals`** - Lister les Objectifs

**Query Parameters**:
- `?status=active` - Filtrer par statut (active, completed, abandoned)
- `?category=strength` - Filtrer par catégorie

**Réponse** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Reach 100kg Bench Press",
      "description": "Progressive overload training",
      "category": "strength",
      "target_value": 100.0,
      "current_value": 87.5,
      "unit": "kg",
      "progress_percentage": 87.5,
      "start_date": "2025-01-01",
      "target_date": "2025-06-01",
      "status": "active",
      "is_achieved": false
    }
  ]
}
```

#### **POST `/api/goals`** - Créer un Objectif

**Requête**:
```json
{
  "title": "Lose 10kg",
  "description": "Healthy weight loss through diet and exercise",
  "category": "weight",
  "target_value": 75.0,
  "current_value": 85.0,
  "unit": "kg",
  "start_date": "2025-01-13",
  "target_date": "2025-04-13"
}
```

**Réponse** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 5,
    "title": "Lose 10kg",
    "category": "weight",
    "target_value": 75.0,
    "current_value": 85.0,
    "progress_percentage": 0.0,
    "status": "active",
    "created_at": "2025-01-13T10:00:00Z"
  },
  "message": "Goal created successfully"
}
```

#### **POST `/api/goals/{id}/progress`** - Mettre à Jour la Progression

**Requête**:
```json
{
  "current_value": 83.5,
  "notes": "Lost 1.5kg this week!"
}
```

**Réponse** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 5,
    "current_value": 83.5,
    "target_value": 75.0,
    "progress_percentage": 17.6,  // (85-83.5)/(85-75) * 100
    "status": "active"
  },
  "message": "Goal progress updated"
}
```

### 6️⃣ Dashboard - `/api/dashboard`

#### **GET `/api/dashboard`** - Vue d'Ensemble

**Réponse** (200 OK):
```json
{
  "success": true,
  "data": {
    "user": {
      "name": "Ivan Petrov",
      "email": "ivanpetrov@mail.com"
    },
    "stats": {
      "total_workouts": 45,
      "workouts_this_week": 4,
      "workouts_this_month": 18,
      "total_workout_time": 3375,  // minutes
      "active_goals": 3,
      "completed_goals": 2,
      "current_streak": 7  // jours consécutifs
    },
    "recent_workouts": [
      {
        "id": 10,
        "name": "Push Day Session",
        "date": "2025-01-13",
        "duration": 75,
        "exercises_count": 6
      }
    ],
    "active_goals": [
      {
        "id": 1,
        "title": "Reach 100kg Bench Press",
        "progress_percentage": 87.5
      }
    ],
    "nutrition_today": {
      "calories": 1850,
      "goal_calories": 2500,
      "percentage": 74
    }
  }
}
```

---

## 🔄 Cycle de Vie d'une Requête

Comprenons en détail ce qui se passe quand le frontend Angular envoie une requête au backend Laravel.

### Exemple Concret: Login d'un Utilisateur

```
┌──────────────────────────────────────────────────────────────┐
│      CYCLE DE VIE COMPLET D'UNE REQUÊTE LOGIN               │
└──────────────────────────────────────────────────────────────┘

🔹 ÉTAPE 1: FRONTEND - Utilisateur clique "Se connecter"
───────────────────────────────────────────────────────────────
Frontend (Angular):
  └─ LoginComponent.login()
      └─ authService.login(credentials)
          └─ http.post('/api/auth/login', {email, password})

📤 Requête HTTP envoyée:
POST http://localhost:8000/api/auth/login
Headers:
  Content-Type: application/json
  Accept: application/json
  X-Requested-With: XMLHttpRequest
Body:
  {
    "email": "ivanpetrov@mail.com",
    "password": "SecurePass123!",
    "rememberMe": true
  }


🔹 ÉTAPE 2: RÉSEAU - Voyage à travers Internet
───────────────────────────────────────────────────────────────
1. DNS Resolution: localhost → 127.0.0.1
2. TCP Connection: Port 8000
3. HTTP Request: POST /api/auth/login
4. Requête arrive au serveur Laravel


🔹 ÉTAPE 3: BACKEND - Middleware Chain (Filtres)
───────────────────────────────────────────────────────────────
Laravel Middleware Pipeline:

1. CORS Middleware (/config/cors.php)
   ├─ Vérifie l'origine: http://localhost:4200
   ├─ Autorisé? ✅ (dans CORS_ALLOWED_ORIGINS)
   └─ Ajoute headers CORS à la réponse

2. JSON Middleware
   ├─ Parse le body JSON
   └─ Transforme en array PHP

3. Rate Limiter
   ├─ Vérifie limite de requêtes
   ├─ 60 tentatives/minute autorisées
   └─ OK ✅

4. Validation Middleware
   └─ Passe au Controller


🔹 ÉTAPE 4: ROUTING - Trouver la Bonne Route
───────────────────────────────────────────────────────────────
Laravel Router (/routes/api.php):

Route::post('auth/login', [AuthController::class, 'login']);
              ↓
    Correspond à notre requête!
              ↓
    Appelle: AuthController@login


🔹 ÉTAPE 5: CONTROLLER - Traitement Initial
───────────────────────────────────────────────────────────────
AuthController::login(Request $request)

public function login(Request $request): JsonResponse {
    // 1. Log de la tentative
    Log::info('Login attempt', ['email' => $request->email]);

    // 2. Validation des données
    try {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'rememberMe' => 'boolean'
        ]);
    } catch (ValidationException $e) {
        return $this->errorResponse('Validation failed', 422, $e->errors());
    }

    // 3. Déléguer au Service
    $result = $this->authService->loginUser($validated);

    // 4. Retourner la réponse
    if ($result) {
        return $this->successResponse($result, 'Login successful');
    }

    return $this->errorResponse('Invalid credentials', 401);
}


🔹 ÉTAPE 6: SERVICE - Logique Métier
───────────────────────────────────────────────────────────────
AuthService::loginUser(array $credentials)

public function loginUser(array $credentials): ?array {
    // 1. Normaliser l'email
    $email = strtolower(trim($credentials['email']));
    // → "ivanpetrov@mail.com"

    // 2. Requête à la base de données via Model
    $user = User::where('email', $email)->first();
    // SQL Généré: SELECT * FROM users WHERE email = 'ivanpetrov@mail.com' LIMIT 1

    // 3. Vérifier si utilisateur existe
    if (!$user) {
        Log::warning('User not found', ['email' => $email]);
        return null;
    }

    // 4. Vérifier le mot de passe avec bcrypt
    $passwordValid = Hash::check(
        $credentials['password'],      // "SecurePass123!"
        $user->password               // "$2y$10$hashed_password..."
    );

    if (!$passwordValid) {
        Log::warning('Invalid password', ['email' => $email]);
        return null;
    }

    // ✅ Authentification réussie!

    // 5. Révoquer anciens tokens
    $user->tokens()->delete();
    // SQL: DELETE FROM personal_access_tokens WHERE tokenable_id = 1

    // 6. Générer nouveaux tokens
    $tokens = $this->generateTokens($user, $credentials['rememberMe']);

    return array_merge(['user' => $user], $tokens);
}


🔹 ÉTAPE 7: MODEL - Interaction Base de Données
───────────────────────────────────────────────────────────────
User Model (/app/Models/User.php)

// Eloquent ORM gère automatiquement:
User::where('email', $email)->first()

Devient SQL:
SELECT * FROM users
WHERE email = 'ivanpetrov@mail.com'
LIMIT 1;

Résultat:
┌────┬───────┬─────────┬──────────────────────┬──────────────┐
│ id │ first │ last    │ email                │ password     │
├────┼───────┼─────────┼──────────────────────┼──────────────┤
│ 1  │ ivan  │ petrov  │ ivanpetrov@mail.com  │ $2y$10$...  │
└────┴───────┴─────────┴──────────────────────┴──────────────┘


🔹 ÉTAPE 8: TOKEN GENERATION - Créer Token JWT
───────────────────────────────────────────────────────────────
Laravel Sanctum:

$token = $user->createToken('FitnessPro_1705145000')->plainTextToken;

Processus interne:
1. Génère token aléatoire: "1|abcdefghijklmnopqrstuvwxyz1234567890"
2. Hash le token en SHA-256: "5f7c8e9a2b4d6f8a..."
3. Stocke dans personal_access_tokens:

INSERT INTO personal_access_tokens
(tokenable_type, tokenable_id, name, token, abilities, expires_at)
VALUES
('App\\Models\\User', 1, 'FitnessPro_1705145000', '5f7c8e9a...', '["*"]', '2025-01-14 10:00:00');

4. Retourne token original (non-hashé) au client


🔹 ÉTAPE 9: RESPONSE - Formater et Retourner
───────────────────────────────────────────────────────────────
Controller retourne:

return $this->successResponse($result, 'Login successful');

Devient JSON:
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Ivan Petrov",
      "email": "ivanpetrov@mail.com",
      "email_verified_at": "2025-01-13T10:00:00Z",
      "created_at": "2025-01-10T10:00:00Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
    "refresh_token": "2|xyz9876543210zyxwvutsrqponmlkjihgfedcba"
  },
  "message": "Login successful",
  "timestamp": "2025-01-13T14:30:00Z"
}

Headers ajoutés:
  Content-Type: application/json
  Access-Control-Allow-Origin: http://localhost:4200
  Access-Control-Allow-Credentials: true


🔹 ÉTAPE 10: FRONTEND - Réception et Traitement
───────────────────────────────────────────────────────────────
Angular AuthService reçoit la réponse:

this.http.post('/api/auth/login', credentials)
  .pipe(
    map(response => {
      // 1. Stocker le token
      localStorage.setItem('fitness_token', response.data.token);

      // 2. Stocker l'utilisateur
      localStorage.setItem('fitness_user', JSON.stringify(response.data.user));

      // 3. Mettre à jour les subjects RxJS
      this.tokenSubject.next(response.data.token);
      this.currentUserSubject.next(response.data.user);

      // 4. Rediriger vers dashboard
      this.router.navigate(['/dashboard']);

      // 5. Afficher notification
      this.notificationService.success('Bienvenue Ivan Petrov!');

      return response.data;
    })
  )
  .subscribe();


🔹 RÉSULTAT FINAL
───────────────────────────────────────────────────────────────
✅ Utilisateur authentifié
✅ Token JWT stocké dans localStorage
✅ Données utilisateur disponibles dans l'app
✅ Redirection vers /dashboard
✅ Notification de succès affichée
✅ Auto-logout planifié dans 24h

Durée totale: ~100-300ms
```

---

## 🔧 Services & Logique Métier

### Pourquoi Utiliser une Couche de Services?

Laravel encourage le pattern **Controller → Service → Model** pour séparer les responsabilités:

```
┌──────────────────────────────────────────────────────────────┐
│         COMPARAISON: AVEC vs SANS SERVICE LAYER              │
└──────────────────────────────────────────────────────────────┘

❌ SANS SERVICE LAYER (Antipattern)
─────────────────────────────────────────────────────
AuthController::login() {
    // 😱 Tout le code dans le controller
    $user = User::where('email', $email)->first();
    if (!$user || !Hash::check($password, $user->password)) {
        return response()->json(['error' => 'Invalid'], 401);
    }
    $user->tokens()->delete();
    $token = $user->createToken('token')->plainTextToken;
    Log::info('Login', ['user' => $user->id]);
    return response()->json(['token' => $token]);
}

Problèmes:
  • Code non réutilisable
  • Tests difficiles
  • Maintenance compliquée
  • Pas de séparation des responsabilités


✅ AVEC SERVICE LAYER (Best Practice)
─────────────────────────────────────────────────────
AuthController::login() {
    // 👍 Controller léger et simple
    $validated = $request->validate([...]);
    $result = $this->authService->loginUser($validated);

    return $result
        ? $this->successResponse($result)
        : $this->errorResponse('Invalid credentials', 401);
}

AuthService::loginUser() {
    // 🎯 Logique métier isolée et testable
    // Code complet et bien organisé
    // Facilement réutilisable
}

Avantages:
  ✅ Code réutilisable (CLI, Jobs, Events)
  ✅ Tests unitaires simples
  ✅ Maintenance facile
  ✅ Séparation claire des responsabilités
```

### Nos Services Principaux

#### 1. AuthService ([app/Services/AuthService.php](app/Services/AuthService.php:1))

**Responsabilités**:
- Inscription et connexion d'utilisateurs
- Génération et validation de tokens JWT
- Réinitialisation de mots de passe
- Gestion de sessions

**Méthodes Clés**:
```php
class AuthService {
    // Inscription d'un nouvel utilisateur
    public function registerUser(array $data): array

    // Connexion utilisateur
    public function loginUser(array $credentials): ?array

    // Déconnexion
    public function logoutUser(User $user): bool

    // Révoquer tous les tokens
    public function revokeAllTokens(User $user): bool

    // Refresh token
    public function refreshToken(User $user, string $refreshToken): ?array

    // Demande reset password
    public function sendPasswordResetLink(array $data): string

    // Reset password avec token
    public function resetUserPassword(array $data): string

    // Reset direct (sans email)
    public function directResetPassword(string $email, string $password): bool

    // Génération de tokens (privée)
    private function generateTokens(User $user, bool $rememberMe): array
}
```

**Exemple d'Utilisation**:
```php
// Dans AuthController
public function login(Request $request): JsonResponse {
    $validated = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string|min:8',
        'rememberMe' => 'boolean'
    ]);

    $result = $this->authService->loginUser($validated);

    if (!$result) {
        return $this->errorResponse('Invalid credentials', 401);
    }

    return $this->successResponse($result, 'Login successful');
}
```

#### 2. WorkoutService ([app/Services/WorkoutService.php](app/Services/WorkoutService.php:1))

**Responsabilités**:
- Création et gestion de templates de workout
- Démarrage et suivi de sessions d'entraînement
- Calculs de statistiques d'entraînement
- Gestion de la progression

**Structure Typique**:
```php
class WorkoutService {
    // Templates
    public function createWorkoutTemplate(array $data, User $user): Workout
    public function updateWorkoutTemplate(Workout $workout, array $data): Workout
    public function deleteWorkoutTemplate(Workout $workout): bool
    public function getUserTemplates(User $user): Collection

    // Sessions
    public function startWorkoutSession(User $user, ?int $templateId): Workout
    public function updateWorkoutSession(Workout $workout, array $data): Workout
    public function completeWorkoutSession(Workout $workout, array $data): Workout

    // Statistiques
    public function getWorkoutStats(User $user, array $filters = []): array
    public function getWeeklyStats(User $user): array
    public function getMonthlyStats(User $user): array

    // Exercices
    public function addExerciseToWorkout(Workout $workout, array $data): WorkoutExercise
    public function updateWorkoutExercise(WorkoutExercise $exercise, array $data): WorkoutExercise
    public function removeExerciseFromWorkout(WorkoutExercise $exercise): bool
}
```

**Exemple Complexe - Compléter un Workout**:
```php
public function completeWorkoutSession(Workout $workout, array $data): Workout {
    DB::beginTransaction();

    try {
        // 1. Mettre à jour le workout
        $workout->update([
            'status' => 'completed',
            'duration' => $data['duration'],
            'notes' => $data['notes'] ?? null,
            'completed_at' => now()
        ]);

        // 2. Mettre à jour chaque exercice avec performances réelles
        foreach ($data['exercises'] as $exerciseData) {
            $workoutExercise = WorkoutExercise::find($exerciseData['workout_exercise_id']);
            $workoutExercise->update([
                'actual_sets' => $exerciseData['actual_sets'],
                'actual_reps' => $exerciseData['actual_reps'],
                'actual_weight' => $exerciseData['actual_weight'] ?? null
            ]);
        }

        // 3. Calculer les statistiques de progression
        $progressData = $this->calculateProgress($workout);

        // 4. Mettre à jour les objectifs liés si applicable
        $this->updateRelatedGoals($workout->user, $progressData);

        // 5. Vérifier et débloquer achievements
        $this->checkAchievements($workout->user);

        DB::commit();

        Log::info('Workout completed', [
            'workout_id' => $workout->id,
            'user_id' => $workout->user_id,
            'duration' => $data['duration']
        ]);

        return $workout->fresh()->load('exercises.exercise');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to complete workout', [
            'workout_id' => $workout->id,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}
```

#### 3. NutritionService ([app/Services/NutritionService.php](app/Services/NutritionService.php:1))

**Responsabilités**:
- Calculs nutritionnels (calories, macros)
- Gestion du journal alimentaire
- Génération de plans alimentaires
- Statistiques nutritionnelles

**Méthodes Principales**:
```php
class NutritionService {
    // Journal alimentaire
    public function addMealEntry(array $data, User $user): MealEntry
    public function updateMealEntry(MealEntry $entry, array $data): MealEntry
    public function deleteMealEntry(MealEntry $entry): bool

    // Résumés et statistiques
    public function getDailySummary(User $user, string $date): array
    public function getWeeklySummary(User $user, string $startDate): array
    public function getMonthlySummary(User $user, string $month): array

    // Objectifs nutritionnels
    public function setNutritionGoals(User $user, array $goals): NutritionGoal
    public function getNutritionGoals(User $user): ?NutritionGoal

    // Génération de plans
    public function generateMealPlan(User $user, array $preferences): UserDiet

    // Calculs
    private function calculateDailyTotals(Collection $meals): array
    private function calculateMacroPercentages(array $totals): array
}
```

---

## 🔐 Authentification & Sécurité

### Laravel Sanctum - Authentification SPA

**Qu'est-ce que Sanctum?**

Laravel Sanctum est un système d'authentification léger spécialement conçu pour les **SPAs (Single Page Applications)** comme notre frontend Angular.

```
┌──────────────────────────────────────────────────────────────┐
│           FONCTIONNEMENT DE SANCTUM                          │
└──────────────────────────────────────────────────────────────┘

1. LOGIN
   Frontend                        Backend
      │                               │
      ├─ POST /api/auth/login ───────>│
      │  {email, password}            │
      │                               │
      │                        ┌──────┴──────┐
      │                        │ Vérifier    │
      │                        │ credentials │
      │                        └──────┬──────┘
      │                               │
      │                        ┌──────┴──────────┐
      │                        │ Créer token JWT │
      │                        │ Hash en SHA-256 │
      │                        │ Stocker en BD   │
      │                        └──────┬──────────┘
      │                               │
      │<─── {user, token} ────────────┤
      │                               │
   ┌──┴──────────────┐               │
   │ localStorage.   │               │
   │ setItem(token)  │               │
   └─────────────────┘               │


2. REQUÊTES AUTHENTIFIÉES
   Frontend                        Backend
      │                               │
      ├─ GET /api/workouts ──────────>│
      │  Headers:                     │
      │    Authorization: Bearer 1|abc│
      │                               │
      │                        ┌──────┴──────────┐
      │                        │ Extraire token  │
      │                        │ Hash en SHA-256 │
      │                        └──────┬──────────┘
      │                               │
      │                        ┌──────┴──────────┐
      │                        │ SELECT * FROM   │
      │                        │ personal_access │
      │                        │ _tokens WHERE   │
      │                        │ token = hash    │
      │                        └──────┬──────────┘
      │                               │
      │                        ┌──────┴──────────┐
      │                        │ Token trouvé?   │
      │                        │ Non expiré?     │
      │                        └──────┬──────────┘
      │                               │
      │                        ┌──────┴──────────┐
      │                        │ Charger User    │
      │                        │ Traiter requête │
      │                        └──────┬──────────┘
      │                               │
      │<─── {workouts: [...]} ────────┤
      │                               │


3. EXPIRATION & REFRESH
   Frontend                        Backend
      │                               │
      ├─ GET /api/workouts ──────────>│
      │  Authorization: Bearer expired │
      │                               │
      │                        ┌──────┴──────────┐
      │                        │ Token expiré!   │
      │                        └──────┬──────────┘
      │                               │
      │<─── 401 Unauthorized ──────────┤
      │                               │
   ┌──┴──────────────┐               │
   │ Interceptor:    │               │
   │ Détecter 401    │               │
   │ Supprimer token │               │
   │ Redirect /login │               │
   └─────────────────┘               │
```

### Configuration Sanctum

**1. Fichier de Configuration** ([config/sanctum.php](config/sanctum.php:1)):
```php
return [
    // Domaines frontend autorisés (CORS)
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost:4200')),

    // Middleware à appliquer
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],

    // Durée de vie des tokens (en minutes)
    'expiration' => env('SANCTUM_EXPIRATION', 1440),  // 24 heures par défaut
];
```

**2. Variables d'Environnement** ([.env](backend/.env:1)):
```env
SANCTUM_STATEFUL_DOMAINS=localhost:4200,127.0.0.1:4200
SANCTUM_EXPIRATION=1440  # 24 heures
```

**3. Middleware dans Routes** ([routes/api.php](routes/api.php:1)):
```php
// Routes publiques (pas d'authentification)
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);

// Routes protégées (authentification requise)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('workouts', WorkoutController::class);
    Route::apiResource('goals', GoalController::class);
    // ... autres routes protégées
});
```

### Sécurité - Bonnes Pratiques Implémentées

#### 1️⃣ **Hashing de Mots de Passe (bcrypt)**

```php
// ❌ JAMAIS stocker en clair
$user->password = $request->password;  // DANGER!

// ✅ TOUJOURS hasher avec bcrypt
$user->password = Hash::make($request->password);
// Résultat: "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi"

// Vérification
if (Hash::check($inputPassword, $user->password)) {
    // Mot de passe correct
}
```

**Pourquoi bcrypt?**
- **Lent par conception**: Rend les attaques par force brute impraticables
- **Salt automatique**: Chaque hash est unique même pour le même mot de passe
- **Résistant aux GPU**: Difficile à paralléliser
- **Adaptatif**: Le coût peut augmenter avec le temps

#### 2️⃣ **Validation des Entrées**

```php
// Validation au niveau Controller
$validated = $request->validate([
    'email' => 'required|email|max:255|unique:users,email',
    'password' => 'required|string|min:8|confirmed',
    'first_name' => 'required|string|max:255',
    'last_name' => 'required|string|max:255'
]);

// Si validation échoue, Laravel retourne automatiquement une erreur 422
```

**Règles de Validation Communes**:
- `required`: Champ obligatoire
- `email`: Doit être un email valide
- `unique:table,column`: Valeur unique dans la table
- `min:8`: Minimum 8 caractères
- `confirmed`: Doit correspondre à `field_confirmation`
- `numeric`: Doit être un nombre
- `date`: Doit être une date valide
- `in:foo,bar`: Doit être dans la liste

#### 3️⃣ **Protection CORS**

**Configuration** ([config/cors.php](config/cors.php:1)):
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:4200')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),
];
```

**Variables d'Environnement**:
```env
CORS_ALLOWED_ORIGINS=http://localhost:4200,http://127.0.0.1:4200
CORS_SUPPORTS_CREDENTIALS=true
```

#### 4️⃣ **Rate Limiting**

Limite le nombre de requêtes pour prévenir les abus:

```php
// Dans routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);
});
// 60 requêtes par minute maximum
```

**Configuration Personnalisée** ([app/Providers/RouteServiceProvider.php](app/Providers/RouteServiceProvider.php:1)):
```php
protected function configureRateLimiting() {
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(5)->by($request->email.$request->ip());
    });
}
```

#### 5️⃣ **SQL Injection Protection**

Laravel Eloquent protège automatiquement contre les injections SQL:

```php
// ✅ SAFE - Eloquent utilise des prepared statements
$user = User::where('email', $request->email)->first();
// SQL: SELECT * FROM users WHERE email = ?
// Binding: ['ivanpetrov@mail.com']

// ❌ UNSAFE - Concaténation directe (NE JAMAIS FAIRE)
$user = DB::select("SELECT * FROM users WHERE email = '$email'");
// Vulnérable à: ' OR 1=1 --
```

#### 6️⃣ **XSS Protection**

Laravel échappe automatiquement les données dans les vues:

```php
// Dans un template Blade (si utilisé)
{{ $user->name }}  // Échappé automatiquement
{!! $user->name !!}  // NON échappé (dangereux, utiliser avec précaution)
```

Pour les APIs JSON, le header `Content-Type: application/json` empêche l'exécution de scripts.

#### 7️⃣ **Logging et Monitoring**

```php
// Log toutes les tentatives de login
Log::info('Login attempt', [
    'email' => $request->email,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent()
]);

// Log les erreurs avec contexte
Log::error('Login failed', [
    'email' => $request->email,
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString()
]);

// Niveaux de log disponibles:
Log::emergency();  // Système inutilisable
Log::alert();      // Action immédiate requise
Log::critical();   // Conditions critiques
Log::error();      // Erreurs d'exécution
Log::warning();    // Avertissements
Log::notice();     // Conditions normales mais significatives
Log::info();       // Messages informatifs
Log::debug();      // Messages de débogage détaillés
```

**Fichiers de Logs**: [storage/logs/laravel.log](storage/logs/laravel.log:1)

---

## 💻 Développement

### Commandes Artisan Essentielles

```bash
# SERVEUR DE DÉVELOPPEMENT
php artisan serve                    # Démarre sur localhost:8000
php artisan serve --port=8080        # Port personnalisé
php artisan serve --host=0.0.0.0     # Accessible depuis réseau

# BASE DE DONNÉES
php artisan migrate                  # Exécute migrations
php artisan migrate:fresh            # Supprime tout et recrée
php artisan migrate:fresh --seed     # Avec données de test
php artisan migrate:rollback         # Annule dernière migration
php artisan migrate:status           # État des migrations
php artisan db:seed                  # Peupler la base

# CACHE
php artisan cache:clear              # Vider cache applicatif
php artisan config:clear             # Vider cache configuration
php artisan route:clear              # Vider cache routes
php artisan view:clear               # Vider cache vues

# DÉVELOPPEMENT
php artisan tinker                   # Console interactive PHP
php artisan route:list               # Lister toutes les routes
php artisan make:controller NameController  # Créer controller
php artisan make:model ModelName     # Créer model
php artisan make:migration create_table_name  # Créer migration
php artisan make:seeder NameSeeder   # Créer seeder

# LOGS
php artisan log:clear                # Vider les logs
tail -f storage/logs/laravel.log     # Suivre logs en temps réel
```

### Tinker - Console Interactive

Tinker est un REPL (Read-Eval-Print Loop) puissant pour interagir avec votre application:

```bash
php artisan tinker

# Créer un utilisateur
>>> $user = new User();
>>> $user->first_name = 'john';
>>> $user->last_name = 'doe';
>>> $user->email = 'john@example.com';
>>> $user->password = Hash::make('password123');
>>> $user->save();

# Trouver un utilisateur
>>> $user = User::where('email', 'ivanpetrov@mail.com')->first();
>>> $user->name;
=> "Ivan Petrov"

# Mettre à jour le mot de passe
>>> $user->password = Hash::make('NewPassword123!');
>>> $user->save();

# Compter les workouts
>>> Workout::where('user_id', 1)->count();
=> 45

# Récupérer statistiques
>>> User::find(1)->workouts()->where('is_template', false)->sum('duration');
=> 3375  // Total minutes d'entraînement

# Tester une requête complexe
>>> DB::table('workout_exercises')
      ->join('exercises', 'workout_exercises.exercise_id', '=', 'exercises.id')
      ->where('workout_id', 5)
      ->select('exercises.name', 'workout_exercises.sets', 'workout_exercises.reps')
      ->get();

# Supprimer tous les tokens d'un utilisateur
>>> User::find(1)->tokens()->delete();
```

### Tests

```bash
# Exécuter tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter=AuthenticationTest
php artisan test tests/Feature/AuthTest.php

# Avec couverture de code
php artisan test --coverage
php artisan test --coverage-html=coverage  # Rapport HTML

# Tests unitaires seulement
php artisan test --testsuite=Unit

# Tests Feature seulement
php artisan test --testsuite=Feature
```

**Structure des Tests**:
```
tests/
├── Feature/              # Tests d'intégration
│   ├── AuthTest.php      # Tests authentification
│   ├── WorkoutTest.php   # Tests workouts
│   └── NutritionTest.php # Tests nutrition
│
├── Unit/                 # Tests unitaires
│   ├── UserTest.php      # Tests model User
│   └── ServiceTest.php   # Tests services
│
└── TestCase.php          # Classe de base pour tests
```

**Exemple de Test**:
```php
// tests/Feature/AuthTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase {
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials() {
        // Arrange: Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        // Act: Tenter de se connecter
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        // Assert: Vérifier la réponse
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['user', 'token'],
                     'message'
                 ]);
    }

    public function test_user_cannot_login_with_invalid_password() {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(401)
                 ->assertJson(['success' => false]);
    }
}
```

---

## 🚀 Déploiement

### Déploiement sur Fly.io

Notre backend est déployé sur [Fly.io](https://fly.io), une plateforme cloud moderne.

**URL Production**: `https://fitness-pro-backend.fly.dev`

#### Configuration Fly.io

**1. Fichier de Configuration** ([fly.toml](fly.toml:1)):
```toml
app = "fitness-pro-backend"
primary_region = "cdg"  # Paris

[build]
  [build.args]
    PHP_VERSION = "8.2"

[env]
  APP_ENV = "production"
  APP_DEBUG = "false"
  LOG_CHANNEL = "stack"
  LOG_LEVEL = "info"

[http_service]
  internal_port = 8080
  force_https = true
  auto_stop_machines = true
  auto_start_machines = true
  min_machines_running = 0
  processes = ["app"]

  [[http_service.checks]]
    interval = "15s"
    timeout = "10s"
    grace_period = "30s"
    method = "GET"
    path = "/api/health"
```

#### Commandes de Déploiement

```bash
# 1. S'authentifier à Fly.io
fly auth login

# 2. Créer l'application (première fois uniquement)
cd backend
fly launch
# Répondre aux questions interactives

# 3. Configurer la base de données PostgreSQL
fly postgres create
# Noter les credentials retournées

# 4. Attacher la base de données à l'app
fly postgres attach fitness-pro-db

# 5. Configurer les secrets (variables d'environnement sensibles)
fly secrets set APP_KEY="base64:..."
fly secrets set DB_CONNECTION=pgsql
fly secrets set DB_HOST=fitness-pro-db.internal
fly secrets set DB_PORT=5432
fly secrets set DB_DATABASE=fitness_pro
fly secrets set DB_USERNAME=postgres
fly secrets set DB_PASSWORD=xxxxx

# 6. Déployer l'application
fly deploy
# Build Docker image, push, et démarre les machines

# 7. Exécuter les migrations en production
fly ssh console
php artisan migrate --force

# 8. Vérifier l'état
fly status
fly logs

# 9. Pour redéployer après changements
git add .
git commit -m "Update backend"
fly deploy
```

#### Commandes de Gestion

```bash
# Voir les logs en temps réel
fly logs

# SSH dans la machine
fly ssh console

# Vérifier l'état de l'application
fly status

# Lister les secrets configurés
fly secrets list

# Mettre à jour un secret
fly secrets set MAIL_MAILER=smtp

# Redémarrer l'application
fly apps restart fitness-pro-backend

# Ouvrir l'app dans le navigateur
fly open
```

#### Base de Données Production

```bash
# Se connecter à PostgreSQL
fly postgres connect -a fitness-pro-db

# Depuis le terminal psql:
\l              # Lister les bases de données
\c fitness_pro  # Se connecter à la base
\dt             # Lister les tables
\d users        # Décrire la table users

# Exécuter requête SQL
SELECT id, email, created_at FROM users;

# Backup de la base
fly postgres backup create

# Lister les backups
fly postgres backup list
```

### Environnement de Production

**Variables d'Environnement Critiques**:
```env
# Application
APP_ENV=production
APP_DEBUG=false  # IMPORTANT: false en production
APP_KEY=base64:...  # Généré avec php artisan key:generate

# Base de données
DB_CONNECTION=pgsql
DB_HOST=fitness-pro-db.internal
DB_PORT=5432
DB_DATABASE=fitness_pro
DB_USERNAME=postgres
DB_PASSWORD=xxxxx

# Frontend URL (pour CORS)
FRONTEND_URL=https://fitness-pro-frontend.vercel.app

# Sanctum
SANCTUM_STATEFUL_DOMAINS=fitness-pro-frontend.vercel.app
SANCTUM_EXPIRATION=1440

# CORS
CORS_ALLOWED_ORIGINS=https://fitness-pro-frontend.vercel.app
CORS_SUPPORTS_CREDENTIALS=true

# Mail (production)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.xxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@fitnesspro.com
MAIL_FROM_NAME="FitnessPro"
```

### Optimisations Production

```bash
# 1. Cacher la configuration
php artisan config:cache

# 2. Cacher les routes
php artisan route:cache

# 3. Cacher les vues
php artisan view:cache

# 4. Optimiser l'autoloader Composer
composer install --optimize-autoloader --no-dev

# 5. Activer OPcache PHP (dans php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Important pour performance
opcache.save_comments=1
opcache.fast_shutdown=1
```

---

## 🛠️ Dépannage

### Problèmes Courants et Solutions

#### 1. CORS Errors

**Symptôme**: Erreur dans le navigateur:
```
Access to XMLHttpRequest at 'http://localhost:8000/api/...'
from origin 'http://localhost:4200' has been blocked by CORS policy
```

**Solutions**:
```bash
# 1. Vérifier la configuration CORS
cat config/cors.php

# 2. Vérifier les variables d'environnement
grep CORS .env

# 3. S'assurer que le frontend est dans la liste
CORS_ALLOWED_ORIGINS=http://localhost:4200,http://127.0.0.1:4200

# 4. Vider le cache de configuration
php artisan config:clear

# 5. Redémarrer le serveur
php artisan serve
```

#### 2. Erreur d'Authentification 401

**Symptôme**: Toutes les requêtes authentifiées retournent 401

**Solutions**:
```bash
# 1. Vérifier que le token est bien envoyé
# Dans le navigateur → DevTools → Network → Headers
# Doit contenir: Authorization: Bearer 1|xxxxx

# 2. Vérifier la configuration Sanctum
grep SANCTUM .env

# 3. Vider les tokens en base
php artisan tinker
>>> DB::table('personal_access_tokens')->truncate();

# 4. Forcer une nouvelle connexion
# Se reconnecter depuis le frontend

# 5. Vérifier les logs
tail -f storage/logs/laravel.log
```

#### 3. Database Connection Error

**Symptôme**:
```
SQLSTATE[HY000] [14] unable to open database file
```

**Solutions**:
```bash
# 1. Vérifier que le fichier SQLite existe
ls -la database/database.sqlite

# 2. Si non, le créer
touch database/database.sqlite

# 3. Donner les permissions
chmod 664 database/database.sqlite
chmod 775 database/

# 4. Vérifier les chemins dans .env
DB_CONNECTION=sqlite
DB_DATABASE=/chemin/absolu/vers/database.sqlite

# 5. Re-tester la connexion
php artisan migrate:status
```

#### 4. Token Mismatch / Session Issues

**Symptôme**: Erreurs de session ou CSRF token mismatch

**Solutions**:
```bash
# 1. Vider tous les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Régénérer la clé d'application
php artisan key:generate

# 3. Redémarrer le serveur
php artisan serve
```

#### 5. Slow Queries / Performance

**Diagnostic**:
```bash
# 1. Activer le query logging
# Dans AppServiceProvider.php
DB::listen(function($query) {
    Log::info('Query', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time
    ]);
});

# 2. Analyser les logs
tail -f storage/logs/laravel.log | grep "Query"

# 3. Vérifier les indexes
php artisan tinker
>>> DB::select("SELECT * FROM sqlite_master WHERE type='index'");
```

**Solutions**:
```bash
# 1. Ajouter des indexes manquants
php artisan make:migration add_indexes_to_tables
# Dans la migration:
Schema::table('workouts', function (Blueprint $table) {
    $table->index('user_id');
    $table->index(['user_id', 'date']);
});

# 2. Utiliser eager loading
# ❌ Bad (N+1 problem)
$workouts = Workout::all();
foreach ($workouts as $workout) {
    echo $workout->user->name;  // Requête pour chaque workout
}

# ✅ Good (1 query)
$workouts = Workout::with('user')->get();
foreach ($workouts as $workout) {
    echo $workout->user->name;  // Pas de requête supplémentaire
}

# 3. Utiliser le cache pour données fréquentes
use Illuminate\Support\Facades\Cache;

$exercises = Cache::remember('exercises_all', 3600, function () {
    return Exercise::all();
});
```

### Debugging Tools

#### Laravel Telescope (Développement)

```bash
# Installer Telescope (dev uniquement)
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Accéder à l'interface
# http://localhost:8000/telescope

# Telescope permet de voir:
# - Toutes les requêtes HTTP
# - Requêtes SQL avec durées
# - Logs en temps réel
# - Jobs en queue
# - Emails envoyés
# - Cache operations
```

#### Logs Structurés

```php
// Utiliser des contextes riches pour faciliter le debugging
Log::info('User login', [
    'user_id' => $user->id,
    'email' => $user->email,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'timestamp' => now()->toISOString()
]);

// En cas d'erreur, inclure le maximum de contexte
try {
    // Code risqué
} catch (\Exception $e) {
    Log::error('Workout creation failed', [
        'user_id' => $user->id,
        'data' => $data,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    throw $e;
}
```

#### Commandes de Diagnostic

```bash
# Vérifier la configuration complète
php artisan config:show

# Lister toutes les routes avec middleware
php artisan route:list --columns=uri,name,action,middleware

# Informations système
php artisan about

# Vérifier les permissions
php artisan storage:link
ls -la storage/

# Tester la connexion base de données
php artisan db:show
php artisan db:table users  # Structure de la table

# Vérifier l'état des migrations
php artisan migrate:status
```

---

## 📚 Ressources Supplémentaires

### Documentation Officielle

- **Laravel 12**: https://laravel.com/docs/12.x
- **Laravel Sanctum**: https://laravel.com/docs/12.x/sanctum
- **Eloquent ORM**: https://laravel.com/docs/12.x/eloquent
- **Database Migrations**: https://laravel.com/docs/12.x/migrations
- **Validation**: https://laravel.com/docs/12.x/validation

### Outils & Packages Utilisés

- **Composer**: Gestionnaire de dépendances PHP
- **Laravel**: Framework PHP moderne
- **Sanctum**: Authentification API
- **SQLite**: Base de données légère (dev)
- **PostgreSQL**: Base de données production
- **Fly.io**: Plateforme de déploiement

### Commandes Utiles Résumées

```bash
# DÉVELOPPEMENT
php artisan serve                # Démarrer serveur local
php artisan tinker               # Console interactive
php artisan route:list           # Lister routes
php artisan migrate              # Exécuter migrations
php artisan db:seed              # Peupler base de données

# CACHE
php artisan cache:clear          # Vider cache
php artisan config:clear         # Vider config cache
php artisan route:clear          # Vider routes cache

# PRODUCTION
fly deploy                       # Déployer sur Fly.io
fly logs                         # Voir logs production
fly ssh console                  # SSH dans machine
fly postgres connect             # Se connecter à la base

# DEBUGGING
tail -f storage/logs/laravel.log # Suivre logs
php artisan about                # Info système
php artisan db:show              # Info base de données
```

---

## 🎓 Concepts Clés à Retenir

### 1. Architecture MVC
- **Models** = Données et relations
- **Controllers** = Points d'entrée HTTP
- **Services** = Logique métier

### 2. Eloquent ORM
- Abstraction de la base de données
- Relations puissantes (hasMany, belongsTo, etc.)
- Protection contre SQL injection

### 3. Laravel Sanctum
- Authentification JWT pour SPAs
- Tokens hashés en SHA-256
- Expiration automatique

### 4. API RESTful
- GET = Récupérer
- POST = Créer
- PUT/PATCH = Mettre à jour
- DELETE = Supprimer

### 5. Middleware
- Filtres de requêtes
- auth:sanctum = Authentification requise
- CORS = Autorisation cross-origin

### 6. Sécurité
- Bcrypt pour mots de passe
- Validation des entrées
- Protection CSRF
- Rate limiting

---

**Ce backend est le cœur de FitnessPro, gérant toute la logique métier et la persistance des données. Pour l'interface utilisateur, consultez le [README du frontend](../frontend/README.md).**
