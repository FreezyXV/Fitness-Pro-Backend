# 🏋️ FitnessPro Backend - Complete & Educational Documentation

> **Complete guide to understand the architecture, operation, and development of the FitnessPro Laravel backend**
>
> This documentation is designed to be **accessible to everyone**, from beginners to experienced developers.

**Version française disponible:** [READMEFR.md](./READMEFR.md)

---

## 📚 Table of Contents

1. [Introduction - What is a Backend?](#1-introduction)
2. [Global Architecture](#2-architecture)
3. [Technologies Used and Why](#3-technologies)
4. [Installation and Configuration](#4-installation)
5. [Complete Project Structure](#5-structure)
6. [Request Flow - From API Call to Response](#6-request-flow)
7. [Authentication System](#7-authentication)
8. [Database & Models](#8-database)
9. [Services & Business Logic](#9-services)
10. [Controllers & Routes](#10-controllers)
11. [Middleware & Security](#11-middleware)
12. [Notifications & Jobs](#12-notifications)
13. [Testing](#13-testing)
14. [Deployment](#14-deployment)
15. [Best Practices](#15-best-practices)
16. [Troubleshooting & FAQ](#16-troubleshooting)

---

<a name="1-introduction"></a>
## 1. Introduction - What is a Backend?

### 🎯 Simple Analogy: The Restaurant

Imagine a web application like **a restaurant**:

```
┌───────────────────────────────────────────────────────────────┐
│                    🍽️ RESTAURANT                              │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  🧑‍💼 DINING ROOM (Frontend)        👨‍🍳 KITCHEN (Backend)      │
│  ├─ Takes orders                 ├─ Prepares dishes         │
│  ├─ Presents menu                ├─ Manages recipes         │
│  ├─ Displays dishes              ├─ Stores ingredients      │
│  └─ Customer interaction         └─ Quality control         │
│                                                               │
│  👤 CUSTOMER                      📊 STORAGE                 │
│  └─ Makes requests               └─ Database                │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

**The Backend is the kitchen:**
- Receives orders from the frontend (HTTP requests)
- Processes the business logic (recipes)
- Accesses the database (ingredients/storage)
- Returns prepared data (dishes)

### 📊 What Does the FitnessPro Backend Do?

```
┌────────────────────────────────────────────────────────────┐
│                 FITNESSPRO BACKEND                         │
└────────────────────────────────────────────────────────────┘

📱 FRONTEND (Angular)          🖥️ BACKEND (Laravel)          📊 DATABASE (PostgreSQL)
      │                              │                              │
      ├─ User clicks "Login"         │                              │
      │                              │                              │
      ├──POST /api/auth/login───────>│                              │
      │  { email, password }          │                              │
      │                              │                              │
      │                              ├─ Validate credentials         │
      │                              │                              │
      │                              ├──SELECT * FROM users────────>│
      │                              │   WHERE email = ?            │
      │                              │                              │
      │                              │<─────User data──────────────┤
      │                              │                              │
      │                              ├─ Verify password             │
      │                              │                              │
      │                              ├─ Generate JWT token          │
      │                              │                              │
      │<─────{ token, user }─────────┤                              │
      │                              │                              │
      ├─ Store token                 │                              │
      │                              │                              │
      ├─ Navigate to Dashboard       │                              │
```

**The backend handles:**
- ✅ User authentication (login, register, password reset)
- ✅ Workout management (create, update, complete sessions)
- ✅ Goals tracking (progress, achievements)
- ✅ Nutrition calculations (calories, macros)
- ✅ Calendar & notifications
- ✅ Analytics & statistics

---

<a name="2-architecture"></a>
## 2. Global Architecture

### 🏗️ Layered Architecture

FitnessPro backend follows a **clean layered architecture**:

```
┌────────────────────────────────────────────────────────────┐
│                   ARCHITECTURE LAYERS                      │
└────────────────────────────────────────────────────────────┘

📱 CLIENT (Frontend Angular)
      │
      │ HTTP Request (JSON)
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 🛣️  ROUTES (routes/api.php)                                 │
│     Maps URLs to Controllers                                │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 🔌 MIDDLEWARE                                               │
│     ├─ Authentication (Sanctum)                             │
│     ├─ Rate Limiting                                        │
│     ├─ Logging                                              │
│     └─ CORS                                                 │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 🎮 CONTROLLER (HTTP/Controllers)                            │
│     ├─ Receives request                                     │
│     ├─ Validates input (Form Requests)                      │
│     ├─ Calls Service                                        │
│     └─ Returns JSON response                                │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 🧠 SERVICE (Services/)                                      │
│     ├─ Business logic                                       │
│     ├─ Orchestrates multiple operations                     │
│     ├─ Calls Repositories                                   │
│     └─ Triggers Notifications/Jobs                          │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 📦 REPOSITORY (Repositories/)                               │
│     ├─ Data access layer                                    │
│     ├─ Query building                                       │
│     └─ Database abstraction                                 │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 🗄️  MODEL (Eloquent Models)                                 │
│     ├─ Represents database table                            │
│     ├─ Relationships                                        │
│     └─ Attributes/Casts                                     │
└─────────────────────────────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────────────────────────────┐
│ 💾 DATABASE (PostgreSQL/SQLite)                             │
│     └─ Data persistence                                     │
└─────────────────────────────────────────────────────────────┘
```

### 🔄 Application Startup Flow

```
┌────────────────────────────────────────────────────────────┐
│           APPLICATION STARTUP                              │
└────────────────────────────────────────────────────────────┘

1️⃣ SERVER START
   PHP-FPM / Nginx starts
        ↓
   Laravel bootstrap
        ↓

2️⃣ CONFIGURATION LOADING
   📄 Load .env file
        ↓
   🔧 Load config files (app, database, auth, etc.)
        ↓
   🔑 APP_KEY validation
        ↓

3️⃣ SERVICE PROVIDERS REGISTRATION
   Register providers:
      ├─ AppServiceProvider
      ├─ AuthServiceProvider
      ├─ RouteServiceProvider
      ├─ RepositoryServiceProvider
      └─ WorkoutServiceProvider
        ↓

4️⃣ MIDDLEWARE REGISTRATION
   Stack middleware:
      ├─ CORS
      ├─ Authentication
      ├─ Rate Limiting
      └─ Custom middleware
        ↓

5️⃣ ROUTES LOADING
   Load routes/api.php
        ↓
   Map endpoints to controllers
        ↓

6️⃣ DATABASE CONNECTION
   Connect to PostgreSQL/SQLite
        ↓
   Verify connection
        ↓

7️⃣ APPLICATION READY
   ✅ API listening on port 8000
   ✅ Ready to handle requests
```

---

<a name="3-technologies"></a>
## 3. Technologies Used and Why

### 🛠️ Complete Technical Stack

```
┌────────────────────────────────────────────────────────────┐
│                     TECHNICAL STACK                        │
└────────────────────────────────────────────────────────────┘

🐘 PHP 8.2
    ├─ Why PHP 8.2?
    │  ├─ Modern type system (readonly, enums)
    │  ├─ Performance improvements (JIT compiler)
    │  ├─ Better error handling
    │  ├─ Named arguments
    │  └─ Long-term support (LTS)
    │
    └─ Alternatives considered
       ├─ Node.js (less mature for enterprise)
       ├─ Python (Django/Flask - different ecosystem)
       └─ Java/Spring (more complex, slower development)

🔥 LARAVEL 12
    ├─ Why Laravel?
    │  ├─ Complete framework (batteries included)
    │  ├─ Eloquent ORM (intuitive database queries)
    │  ├─ Built-in authentication (Sanctum)
    │  ├─ Queue system for background jobs
    │  ├─ Email/notification system
    │  ├─ Excellent documentation
    │  └─ Large community & ecosystem
    │
    └─ Example benefit
       // Traditional SQL
       $users = DB::select('SELECT * FROM users WHERE active = 1');

       // ✅ Laravel Eloquent - readable & safe
       $users = User::where('active', true)->get();

🔐 LARAVEL SANCTUM
    ├─ Why Sanctum?
    │  ├─ Simple token authentication
    │  ├─ Perfect for SPA (Angular)
    │  ├─ No OAuth complexity
    │  ├─ Cookie-based session for web
    │  └─ API tokens for mobile
    │
    └─ Example
       // Automatic token authentication
       Route::middleware('auth:sanctum')->group(function () {
           Route::get('/user', fn() => auth()->user());
       });

🐘 POSTGRESQL (Production)
    ├─ Why PostgreSQL?
    │  ├─ Robust relational database
    │  ├─ JSONB support (flexible data)
    │  ├─ Advanced indexing
    │  ├─ Window functions for analytics
    │  ├─ Excellent performance
    │  └─ Industry standard
    │
    └─ Alternatives
       ├─ MySQL (similar, but less features)
       ├─ MongoDB (NoSQL, less structure)
       └─ SQLite (dev only, not scalable)

💾 SQLITE (Development)
    ├─ Why SQLite for dev?
    │  ├─ Zero configuration
    │  ├─ File-based (easy to reset)
    │  ├─ Perfect for testing
    │  ├─ Fast for development
    │  └─ Same SQL syntax as PostgreSQL
    │
    └─ Usage
       // .env for development
       DB_CONNECTION=sqlite
       DB_DATABASE=./database/database.sqlite

📧 LARAVEL NOTIFICATIONS
    ├─ Why Laravel Notifications?
    │  ├─ Multi-channel (email, SMS, database)
    │  ├─ Queue support (async sending)
    │  ├─ Easy templating
    │  └─ Built-in testing helpers
    │
    └─ Example
       // Send password reset email
       $user->notify(new ResetPasswordNotification($token));

🐳 DOCKER (Optional)
    ├─ Why Docker?
    │  ├─ Consistent environment
    │  ├─ Easy team onboarding
    │  ├─ Laravel Sail integration
    │  └─ Production-ready containers
    │
    └─ Deployment options
       ├─ Render.com (recommended)
       ├─ Fly.io
       ├─ AWS ECS
       └─ DigitalOcean App Platform
```

### 🔄 Compilation Flow

Laravel is **interpreted**, not compiled, but here's the request flow:

```
┌────────────────────────────────────────────────────────────┐
│             REQUEST PROCESSING FLOW                        │
└────────────────────────────────────────────────────────────┘

1️⃣ REQUEST ARRIVES
   HTTP Request → Nginx/Apache → PHP-FPM
          ↓

2️⃣ LARAVEL BOOTSTRAP
   ├─ Load autoloader (Composer)
   ├─ Create application instance
   ├─ Load configuration
   └─ Register service providers
          ↓

3️⃣ MIDDLEWARE STACK
   ├─ CORS check
   ├─ Authentication verification
   ├─ Rate limiting
   └─ Logging
          ↓

4️⃣ ROUTING
   ├─ Match URL to route
   ├─ Apply route middleware
   └─ Resolve controller
          ↓

5️⃣ CONTROLLER EXECUTION
   ├─ Validate request (FormRequest)
   ├─ Call service method
   └─ Format response
          ↓

6️⃣ RESPONSE SENT
   JSON response → PHP-FPM → Nginx → Client

⏱️ Total time: ~50-200ms depending on database queries
```

---

<a name="4-installation"></a>
## 4. Installation and Configuration

### 📋 Prerequisites

```bash
# Required versions
PHP:         8.2 or higher
Composer:    2.x
PostgreSQL:  14+ (production)
SQLite:      3.x (development)

# Check installed versions
php --version       # should display PHP 8.2.x
composer --version  # should display Composer 2.x.x
psql --version      # should display PostgreSQL 14.x
```

### 🚀 Step-by-Step Installation

```bash
# 1️⃣ Clone the repository
git clone https://github.com/your-username/fitness-pro.git
cd fitness-pro/backend

# 2️⃣ Install PHP dependencies
composer install
# This will:
# - Download all packages (~50MB vendor/)
# - Install Laravel, Sanctum, PHPUnit, etc.
# - Configure autoloading
# Duration: 1-3 minutes depending on your connection

# 3️⃣ Environment configuration
cp .env.example .env

# 4️⃣ Generate application key
php artisan key:generate

# 5️⃣ Database setup (SQLite for development)
touch database/database.sqlite

# 6️⃣ Run migrations
php artisan migrate

# 7️⃣ Seed database with demo data
php artisan db:seed

# 8️⃣ Start the development server
php artisan serve
# The API will be accessible at:
# 🌐 http://localhost:8000
```

### ⚙️ Environment Configuration

**`.env` for Development:**
```env
APP_NAME=FitnessPro
APP_ENV=local
APP_KEY=base64:... # Generated by php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# Frontend URL (for CORS and password reset links)
FRONTEND_URL=http://localhost:4200
SANCTUM_STATEFUL_DOMAINS=localhost:4200
SESSION_DOMAIN=localhost

# Database (SQLite for development)
DB_CONNECTION=sqlite
DB_DATABASE=./database/database.sqlite

# Queue (database driver for simplicity)
QUEUE_CONNECTION=database

# Mail (Mailtrap for testing)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@fitnesspro.app
MAIL_FROM_NAME="FitnessPro"

# Seeding configuration
RUN_DB_SEEDERS=false
DB_SEEDER_CLASS=ProductionSeeder
```

**`.env` for Production:**
```env
APP_NAME=FitnessPro
APP_ENV=production
APP_KEY=base64:... # Different key for production!
APP_DEBUG=false
APP_URL=https://api.fitnesspro.com

FRONTEND_URL=https://fitnesspro.com
SANCTUM_STATEFUL_DOMAINS=fitnesspro.com
SESSION_DOMAIN=.fitnesspro.com

# Database (PostgreSQL on Neon)
DB_CONNECTION=pgsql
DB_HOST=your-neon-host.neon.tech
DB_PORT=5432
DB_DATABASE=fitnesspro
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

# Queue (can upgrade to Redis/SQS for scale)
QUEUE_CONNECTION=database

# Mail (Production SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@fitnesspro.com
MAIL_FROM_NAME="FitnessPro"

RUN_DB_SEEDERS=false
```

### 🏃 Running the Application

```bash
# Start development server
php artisan serve

# The application will be accessible at:
# 🌐 http://localhost:8000

# What happens in the background:
# 1. PHP built-in server starts
# 2. Laravel bootstraps
# 3. Routes are registered
# 4. Middleware stack is ready
# 5. Database connection is established
# 6. API is ready to receive requests

# Useful options
php artisan serve --host=0.0.0.0     # Accessible from network
php artisan serve --port=8080        # Change port

# View routes
php artisan route:list

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### 🔗 Verify Backend is Running

```bash
# Test the API health endpoint
curl http://localhost:8000/api/health

# Expected response:
# {"status":"ok","timestamp":"2025-11-04T10:30:00Z"}

# Test with frontend running:
# 1. Start backend: php artisan serve (port 8000)
# 2. Start frontend: ng serve (port 4200)
# 3. Frontend should connect to http://localhost:8000/api
```

---

<a name="5-structure"></a>
## 5. Complete Project Structure

### 📁 Detailed Tree Structure

```
backend/
├── 📄 composer.json              # PHP dependencies
├── 📄 artisan                    # Laravel CLI tool
├── 📄 .env.example               # Environment template
├── 📄 phpunit.xml                # Test configuration
│
├── 📁 app/                       # Application code
│   ├── 📁 Console/
│   │   └── Kernel.php            # Scheduled commands
│   │
│   ├── 📁 Http/
│   │   ├── 📁 Controllers/       # Request handlers
│   │   │   ├── AuthController.php
│   │   │   ├── WorkoutController.php
│   │   │   ├── GoalController.php
│   │   │   ├── NutritionController.php
│   │   │   └── DashboardController.php
│   │   │
│   │   ├── 📁 Middleware/        # Request filters
│   │   │   ├── WorkoutApiLogger.php
│   │   │   ├── WorkoutApiRateLimit.php
│   │   │   └── ValidateWorkoutOwnership.php
│   │   │
│   │   └── 📁 Requests/          # Validation rules
│   │       ├── Auth/
│   │       │   ├── LoginRequest.php
│   │       │   └── RegisterRequest.php
│   │       └── Workout/
│   │           └── CreateWorkoutRequest.php
│   │
│   ├── 📁 Models/                # Database entities
│   │   ├── User.php
│   │   ├── Workout.php
│   │   ├── Exercise.php
│   │   ├── Goal.php
│   │   └── CalendarTask.php
│   │
│   ├── 📁 Services/              # Business logic
│   │   ├── AuthService.php
│   │   ├── WorkoutService.php
│   │   ├── GoalService.php
│   │   ├── StatisticsService.php
│   │   └── StreakCalculatorService.php
│   │
│   ├── 📁 Repositories/          # Data access
│   │   ├── Contracts/
│   │   │   ├── WorkoutRepositoryInterface.php
│   │   │   └── GoalRepositoryInterface.php
│   │   ├── WorkoutRepository.php
│   │   └── GoalRepository.php
│   │
│   ├── 📁 Notifications/         # Email & push
│   │   └── ResetPasswordNotification.php
│   │
│   ├── 📁 Traits/                # Reusable code
│   │   ├── ApiResponseTrait.php
│   │   └── BelongsToUserTrait.php
│   │
│   └── 📁 Providers/             # Service bindings
│       ├── AppServiceProvider.php
│       └── RepositoryServiceProvider.php
│
├── 📁 bootstrap/                 # Framework bootstrap
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
│   ├── 📁 migrations/            # Schema changes
│   │   ├── 2024_01_01_create_users_table.php
│   │   ├── 2024_01_02_create_workouts_table.php
│   │   └── 2024_01_03_create_goals_table.php
│   │
│   ├── 📁 seeders/               # Sample data
│   │   ├── DatabaseSeeder.php
│   │   ├── ProductionSeeder.php
│   │   └── ExerciseSeeder.php
│   │
│   └── 📁 factories/             # Test data generators
│       └── UserFactory.php
│
├── 📁 routes/
│   ├── api.php                   # API endpoints
│   └── web.php                   # Web routes (minimal)
│
├── 📁 storage/
│   ├── app/
│   ├── framework/
│   └── logs/
│       └── laravel.log           # Application logs
│
└── 📁 tests/
    ├── Feature/                  # Integration tests
    │   ├── AuthTest.php
    │   └── WorkoutTest.php
    └── Unit/                     # Unit tests
        └── GoalServiceTest.php
```

### 📖 Key Files Explained

#### 🎯 **routes/api.php** - API Endpoints

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkoutController;

// Public routes (no authentication required)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/password/email', [AuthController::class, 'sendPasswordResetLink']);

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Workout routes
    Route::apiResource('workouts', WorkoutController::class);
    Route::post('/workouts/logs', [WorkoutController::class, 'completeLog']);

    // Goal routes
    Route::apiResource('goals', GoalController::class);
    Route::patch('/goals/{goal}/progress', [GoalController::class, 'updateProgress']);
});
```

**Why this structure?**
- Public routes accessible without token
- Protected routes require `auth:sanctum` middleware
- RESTful resource routes (index, store, show, update, destroy)
- Custom actions for specific business logic

---

<a name="6-request-flow"></a>
## 6. Request Flow - From API Call to Response

### 🎬 Complete Example: User Completes a Workout

```
┌────────────────────────────────────────────────────────────┐
│     COMPLETE FLOW: COMPLETE A WORKOUT SESSION             │
│     (Educational example with full detail)                 │
└────────────────────────────────────────────────────────────┘


STEP 1: 🖱️ USER CLICKS "COMPLETE WORKOUT" ON ANGULAR
──────────────────────────────────────────────────────────────
Frontend: src/app/features/workout/workout.component.ts

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


STEP 2: 🌐 HTTP REQUEST SENT
──────────────────────────────────────────────────────────────
POST http://localhost:8000/api/workouts/logs

Headers:
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
  Content-Type: application/json
  Accept: application/json

Body:
{
  "workout_id": 42,
  "exercises": [
    { "exercise_id": 1, "sets": 3, "reps": 12, "weight": 50 },
    { "exercise_id": 2, "sets": 4, "reps": 10, "weight": 60 }
  ]
}


STEP 3: 🛣️ ROUTING
──────────────────────────────────────────────────────────────
File: routes/api.php

Router matches:
  POST /api/workouts/logs → WorkoutController@completeLog

Middleware applied:
  ├─ CORS check ✅
  ├─ Sanctum authentication ✅
  ├─ Rate limiting ✅
  └─ API logging ✅


STEP 4: 🔐 AUTHENTICATION
──────────────────────────────────────────────────────────────
Middleware: Sanctum

1. Extract token from Authorization header
2. Query personal_access_tokens table
3. Find user (user_id = 1)
4. Inject user into request: $request->user()


STEP 5: 🎮 CONTROLLER RECEIVES REQUEST
──────────────────────────────────────────────────────────────
File: app/Http/Controllers/WorkoutController.php

public function completeLog(WorkoutCompleteRequest $request)
{
    // Request is already validated by FormRequest
    $validated = $request->validated();

    // Call service to handle business logic
    $result = $this->workoutService->completeWorkout(
        $request->user(),
        $validated
    );

    // Return formatted JSON response
    return $this->success($result, 'Workout completed successfully');
}


STEP 6: ✅ VALIDATION
──────────────────────────────────────────────────────────────
File: app/Http/Requests/Workout/WorkoutCompleteRequest.php

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

// Validation passes ✅


STEP 7: 🧠 SERVICE PROCESSES BUSINESS LOGIC
──────────────────────────────────────────────────────────────
File: app/Services/WorkoutService.php

public function completeWorkout(User $user, array $data): Workout
{
    DB::beginTransaction();

    try {
        // 1. Load workout
        $workout = $this->workoutRepository->find($data['workout_id']);

        // 2. Verify ownership
        if ($workout->user_id !== $user->id) {
            throw new UnauthorizedException();
        }

        // 3. Create completed workout log
        $log = Workout::create([
            'user_id' => $user->id,
            'name' => $workout->name,
            'is_template' => false,
            'completed_at' => now(),
        ]);

        // 4. Attach exercises with stats
        foreach ($data['exercises'] as $exercise) {
            $log->exercises()->attach($exercise['exercise_id'], [
                'sets' => $exercise['sets'],
                'reps' => $exercise['reps'],
                'weight' => $exercise['weight'] ?? null,
            ]);
        }

        // 5. Update goals progress
        $this->goalService->updateProgressForWorkout($user, $log);

        // 6. Update streak
        $this->streakService->calculateStreak($user);

        // 7. Send notification
        $user->notify(new WorkoutCompletedNotification($log));

        DB::commit();

        return $log->load('exercises');

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}


STEP 8: 📦 REPOSITORY QUERIES DATABASE
──────────────────────────────────────────────────────────────
File: app/Repositories/WorkoutRepository.php

public function find(int $id): Workout
{
    return Workout::with('exercises')
        ->findOrFail($id);
}

SQL Executed:
  SELECT * FROM workouts WHERE id = 42
  SELECT * FROM exercises WHERE id IN (1, 2)


STEP 9: 💾 DATABASE OPERATIONS
──────────────────────────────────────────────────────────────
PostgreSQL executes:

BEGIN TRANSACTION;

-- Create workout log
INSERT INTO workouts (user_id, name, is_template, completed_at, created_at)
VALUES (1, 'Morning Routine', false, '2025-11-04 10:30:00', NOW());

-- Attach exercises
INSERT INTO workout_exercises (workout_id, exercise_id, sets, reps, weight)
VALUES (43, 1, 3, 12, 50), (43, 2, 4, 10, 60);

-- Update goal progress
UPDATE goals SET progress_percentage = 75 WHERE id = 5;

-- Update streak
UPDATE users SET current_streak = 7 WHERE id = 1;

COMMIT;


STEP 10: 📬 NOTIFICATION SENT
──────────────────────────────────────────────────────────────
File: app/Notifications/WorkoutCompletedNotification.php

public function via($notifiable): array
{
    return ['database', 'mail'];
}

// Stored in notifications table
INSERT INTO notifications (type, notifiable_id, data, created_at)
VALUES ('WorkoutCompleted', 1, '{"workout": "Morning Routine"}', NOW());

// Queued email (sent asynchronously)
INSERT INTO jobs (queue, payload, attempts, created_at)
VALUES ('default', '{"notification": "..."}', 0, NOW());


STEP 11: 📤 RESPONSE SENT TO FRONTEND
──────────────────────────────────────────────────────────────
Controller returns:

HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "message": "Workout completed successfully",
  "data": {
    "id": 43,
    "user_id": 1,
    "name": "Morning Routine",
    "completed_at": "2025-11-04T10:30:00Z",
    "exercises": [
      {
        "id": 1,
        "name": "Push-ups",
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


STEP 12: 🎉 FRONTEND RECEIVES & DISPLAYS
──────────────────────────────────────────────────────────────
Angular updates UI:
  ✅ Workout marked as complete
  ✅ Goals progress updated
  ✅ Streak counter incremented
  ✅ Success notification shown

Total time: ~150ms
```

### 📊 Summary Diagram

```
USER ACTION
   ↓
HTTP Request (with JWT token)
   ↓
NGINX/Apache → PHP-FPM
   ↓
Laravel Bootstrap
   ↓
Middleware Stack (CORS, Auth, Rate Limit, Logging)
   ↓
Router → Controller
   ↓
FormRequest Validation
   ↓
Service (Business Logic)
   ├─> Repository (Database Queries)
   ├─> Goal Service (Update Progress)
   ├─> Streak Service (Calculate Streak)
   └─> Notification (Queue Email)
   ↓
Database Transaction (BEGIN → COMMIT)
   ↓
Controller Formats Response (ApiResponseTrait)
   ↓
JSON Response → Frontend
   ↓
USER SEES RESULT
```

---

<a name="7-authentication"></a>
## 7. Authentication System

### 🔐 Laravel Sanctum Architecture

```
┌────────────────────────────────────────────────────────────┐
│           SANCTUM AUTHENTICATION SYSTEM                    │
└────────────────────────────────────────────────────────────┘

📱 FRONTEND (Angular)                  🖥️ BACKEND (Laravel)
┌──────────────────────┐              ┌────────────────────────┐
│                      │              │                        │
│  LoginComponent      │──1.login────>│  AuthController        │
│  ├─ email            │   (POST)     │  ├─ Validate          │
│  └─ password         │              │  ├─ Check credentials │
│                      │              │  └─ Create token      │
│                      │              │                        │
│                      │<─2.token─────│  Token created:       │
│  AuthService         │   (200 OK)   │  {                     │
│  ├─ Store token      │              │   "plainTextToken"    │
│  └─ Set header       │              │  }                     │
│                      │              │                        │
│  localStorage        │              │  Database              │
│  └─ auth_token       │              │  └─ personal_access_   │
│                      │              │     tokens             │
│                      │              │                        │
│  ALL REQUESTS        │──3.request──>│                        │
│      ↓               │   + token    │  Middleware            │
│  Add Header:         │              │  auth:sanctum          │
│  Authorization:      │              │  ├─ Verify token      │
│  Bearer <token>      │              │  └─ Load user         │
│                      │              │                        │
│                      │<─4.data──────│  Protected data        │
└──────────────────────┘              └────────────────────────┘
```

### 🔄 Complete Authentication Flow

```php
// ═══════════════════════════════════════════════════════════
// AUTH CONTROLLER - Login
// ═══════════════════════════════════════════════════════════

public function login(LoginRequest $request)
{
    // 1️⃣ Validate credentials
    $credentials = $request->validated();

    // 2️⃣ Attempt authentication
    if (!Auth::attempt($credentials)) {
        return $this->error('Invalid credentials', 401);
    }

    // 3️⃣ Get authenticated user
    $user = Auth::user();

    // 4️⃣ Create token
    $token = $user->createToken('auth-token')->plainTextToken;

    // 5️⃣ Return token + user data
    return $this->success([
        'token' => $token,
        'user' => $user,
    ], 'Login successful');
}
```

### 🔑 Password Reset Flow

```
┌────────────────────────────────────────────────────────────┐
│        PASSWORD RESET FLOW                                 │
└────────────────────────────────────────────────────────────┘

1️⃣ USER REQUESTS RESET
   Frontend: POST /api/auth/password/email
   Body: { "email": "user@example.com" }
        ↓

2️⃣ BACKEND GENERATES TOKEN
   File: app/Services/AuthService.php

   $token = Str::random(64);

   DB::table('password_reset_tokens')->insert([
       'email' => $email,
       'token' => Hash::make($token),
       'created_at' => now(),
   ]);
        ↓

3️⃣ EMAIL SENT
   File: app/Notifications/ResetPasswordNotification.php

   $resetUrl = "{$frontendUrl}/reset-password?token={$token}&email={$email}";

   Mail sends link to user
        ↓

4️⃣ USER CLICKS LINK
   Frontend opens: /reset-password?token=xxx&email=yyy
   Pre-fills email field (locked)
   User enters new password
        ↓

5️⃣ FRONTEND SUBMITS NEW PASSWORD
   POST /api/auth/password/reset
   Body: {
       "email": "user@example.com",
       "token": "xxx",
       "password": "newpassword",
       "password_confirmation": "newpassword"
   }
        ↓

6️⃣ BACKEND VALIDATES & UPDATES
   ├─ Verify token exists and not expired
   ├─ Verify token matches
   ├─ Hash new password
   ├─ Update user record
   └─ Delete reset token
        ↓

7️⃣ SUCCESS
   User can now login with new password
```

---

<a name="8-database"></a>
## 8. Database & Models

### 💾 Database Schema

```
┌────────────────────────────────────────────────────────────┐
│                   DATABASE SCHEMA                          │
└────────────────────────────────────────────────────────────┘

users
├─ id (primary key)
├─ name
├─ email (unique)
├─ password (hashed)
├─ height, weight
├─ current_streak
├─ remember_token
└─ timestamps

      │
      │ 1:N
      ↓

workouts
├─ id (primary key)
├─ user_id (foreign key → users)
├─ name
├─ description
├─ is_template (boolean)
├─ completed_at (nullable)
└─ timestamps

      │
      │ N:M
      ↓

workout_exercises (pivot)
├─ workout_id (foreign key → workouts)
├─ exercise_id (foreign key → exercises)
├─ sets
├─ reps
├─ weight
├─ tempo
└─ rest

      │
      ↓

exercises
├─ id (primary key)
├─ name
├─ equipment
├─ body_part
├─ difficulty
├─ video_url
└─ timestamps
```

### 📋 Eloquent Models

```php
// ═══════════════════════════════════════════════════════════
// USER MODEL
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
    // RELATIONSHIPS
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
// WORKOUT MODEL
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
    // RELATIONSHIPS
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

### 🔄 Eloquent Relationships

```php
// One-to-Many (User → Workouts)
$user = User::find(1);
$workouts = $user->workouts; // All workouts for this user

// Many-to-Many (Workout ↔ Exercises)
$workout = Workout::find(1);
$exercises = $workout->exercises; // All exercises in this workout

// Access pivot data
foreach ($workout->exercises as $exercise) {
    echo $exercise->pivot->sets;   // 3
    echo $exercise->pivot->reps;   // 12
    echo $exercise->pivot->weight; // 50
}

// Attach/Detach
$workout->exercises()->attach($exerciseId, [
    'sets' => 3,
    'reps' => 12,
    'weight' => 50,
]);

$workout->exercises()->detach($exerciseId);
```

---

<a name="9-services"></a>
## 9. Services & Business Logic

### 🧠 Service Layer Pattern

Services contain **all business logic**, keeping controllers thin.

```php
// ═══════════════════════════════════════════════════════════
// WORKOUT SERVICE - Complete Example
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
    // CREATE WORKOUT
    // ─────────────────────────────────────────────────────

    public function createWorkout(User $user, array $data): Workout
    {
        DB::beginTransaction();

        try {
            // Create workout
            $workout = Workout::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_template' => $data['is_template'] ?? false,
            ]);

            // Attach exercises
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
    // COMPLETE WORKOUT
    // ─────────────────────────────────────────────────────

    public function completeWorkout(User $user, array $data): Workout
    {
        DB::beginTransaction();

        try {
            // Load template
            $template = $this->workoutRepository->find($data['workout_id']);

            // Create completed log
            $log = Workout::create([
                'user_id' => $user->id,
                'name' => $template->name,
                'is_template' => false,
                'completed_at' => now(),
            ]);

            // Attach exercises with actual stats
            foreach ($data['exercises'] as $exercise) {
                $log->exercises()->attach($exercise['exercise_id'], [
                    'sets' => $exercise['sets'],
                    'reps' => $exercise['reps'],
                    'weight' => $exercise['weight'] ?? null,
                ]);
            }

            // Update related goals
            $this->goalService->updateProgressForWorkout($user, $log);

            // Update streak
            $this->streakService->calculateStreak($user);

            DB::commit();

            return $log->load('exercises');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────
    // GET USER WORKOUTS
    // ─────────────────────────────────────────────────────

    public function getUserWorkouts(User $user, array $filters = [])
    {
        $query = Workout::where('user_id', $user->id);

        // Apply filters
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
## 10. Controllers & Routes

### 🎮 Controller Pattern

Controllers are **thin** - they delegate business logic to services.

```php
// ═══════════════════════════════════════════════════════════
// WORKOUT CONTROLLER - Thin & Clean
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
    // INDEX - List workouts
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
    // STORE - Create workout
    // ─────────────────────────────────────────────────────

    public function store(CreateWorkoutRequest $request): JsonResponse
    {
        $workout = $this->workoutService->createWorkout(
            $request->user(),
            $request->validated()
        );

        return $this->success($workout, 'Workout created', 201);
    }

    // ─────────────────────────────────────────────────────
    // SHOW - Get single workout
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
    // UPDATE - Update workout
    // ─────────────────────────────────────────────────────

    public function update(UpdateWorkoutRequest $request, int $id): JsonResponse
    {
        $workout = $this->workoutService->updateWorkout(
            $request->user(),
            $id,
            $request->validated()
        );

        return $this->success($workout, 'Workout updated');
    }

    // ─────────────────────────────────────────────────────
    // DESTROY - Delete workout
    // ─────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        $this->workoutService->deleteWorkout(
            auth()->user(),
            $id
        );

        return $this->success(null, 'Workout deleted');
    }
}
```

### 📋 Base Controller with ApiResponseTrait

```php
// ═══════════════════════════════════════════════════════════
// BASE CONTROLLER - Standardized responses
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
// API RESPONSE TRAIT - Consistent JSON responses
// ═══════════════════════════════════════════════════════════

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
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
## 11. Middleware & Security

### 🛡️ Middleware Stack

```php
// ═══════════════════════════════════════════════════════════
// WORKOUT API LOGGER - Log all requests
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

        Log::info('API Request', [
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
// RATE LIMIT - Prevent abuse
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
                'message' => 'Too many requests. Please slow down.',
            ], 429);
        }

        RateLimiter::hit($key, 60); // 60 requests per minute

        return $next($request);
    }
}
```

### 🔒 Security Best Practices

- ✅ **CORS** configured in `config/cors.php`
- ✅ **CSRF** protection via Sanctum
- ✅ **Rate limiting** on all API routes
- ✅ **SQL injection** prevented by Eloquent/prepared statements
- ✅ **XSS** prevented by JSON responses (no HTML rendering)
- ✅ **Password hashing** using bcrypt
- ✅ **JWT tokens** with expiration

---

<a name="12-notifications"></a>
## 12. Notifications & Jobs

### 📧 Email Notifications

```php
// ═══════════════════════════════════════════════════════════
// RESET PASSWORD NOTIFICATION
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
            ->subject('Reset Password - FitnessPro')
            ->line('You requested to reset your password.')
            ->action('Reset Password', $url)
            ->line('This link will expire in 60 minutes.')
            ->line('If you did not request this, no action is needed.');
    }
}
```

### ⏰ Queue Jobs

```php
// ═══════════════════════════════════════════════════════════
// SEND WORKOUT REMINDER JOB
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

// Dispatch the job
SendWorkoutReminderJob::dispatch($user);

// Process queued jobs
php artisan queue:work
```

---

<a name="13-testing"></a>
## 13. Testing

### 🧪 Feature Tests

```php
// ═══════════════════════════════════════════════════════════
// WORKOUT TEST - Integration test
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
                'name' => 'Morning Routine',
                'description' => 'Quick 30min workout',
                'is_template' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Workout created',
            ]);

        $this->assertDatabaseHas('workouts', [
            'user_id' => $user->id,
            'name' => 'Morning Routine',
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

### 🎯 Unit Tests

```php
// ═══════════════════════════════════════════════════════════
// GOAL SERVICE TEST - Unit test
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

### 🏃 Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/WorkoutTest.php

# Run with coverage
php artisan test --coverage

# Filter by test name
php artisan test --filter=test_user_can_create_workout
```

---

<a name="14-deployment"></a>
## 14. Deployment

### ▲ Render.com Deployment

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

### 🐳 Docker Deployment

```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libpq-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

CMD php artisan serve --host=0.0.0.0 --port=8000
```

### 🚀 Deployment Checklist

```bash
# 1. Environment variables
✅ APP_ENV=production
✅ APP_DEBUG=false
✅ APP_KEY (generate new)
✅ Database credentials
✅ FRONTEND_URL
✅ Mail configuration

# 2. Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Run migrations
php artisan migrate --force

# 4. Seed production data (first time only)
php artisan db:seed --class=ProductionSeeder --force

# 5. Clear caches
php artisan cache:clear
php artisan config:clear
```

---

<a name="15-best-practices"></a>
## 15. Best Practices

### 📝 Code Style

```php
// ═══════════════════════════════════════════════════════════
// BEST PRACTICES
// ═══════════════════════════════════════════════════════════

// ✅ Type hints everywhere
public function createWorkout(User $user, array $data): Workout
{
    // ...
}

// ✅ Use dependency injection
public function __construct(
    private WorkoutService $workoutService
) {}

// ✅ Use transactions for multiple database operations
DB::beginTransaction();
try {
    // Multiple operations
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}

// ✅ Use Eloquent scopes
$workouts = Workout::templates()->completed()->get();

// ✅ Use resources for API responses
return WorkoutResource::collection($workouts);

// ✅ Use FormRequests for validation
public function store(CreateWorkoutRequest $request)
{
    $validated = $request->validated();
}

// ❌ Avoid logic in controllers
// Controllers should delegate to services

// ❌ Avoid raw SQL
// Use Eloquent or Query Builder
```

### 🏗️ Architecture Guidelines

1. **Controllers** → Thin, delegate to services
2. **Services** → Business logic, orchestration
3. **Repositories** → Data access layer
4. **Models** → Relationships, scopes, accessors
5. **Traits** → Reusable code across models
6. **FormRequests** → Validation rules
7. **Resources** → API response formatting
8. **Jobs** → Asynchronous tasks
9. **Notifications** → Email, SMS, database

---

<a name="16-troubleshooting"></a>
## 16. Troubleshooting & FAQ

### 🐛 Common Issues

#### ❌ Error: Class not found

**Cause:** Autoloader cache outdated

**Solution:**
```bash
composer dump-autoload
```

#### ❌ CORS Error

**Cause:** Frontend URL not in allowed origins

**Solution:** Check `config/cors.php`:
```php
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:4200')],
```

#### ❌ 401 Unauthorized

**Cause:** Token expired or invalid

**Solution:**
```bash
# Clear Sanctum tokens
php artisan sanctum:prune-expired --hours=24
```

#### ❌ Database connection failed

**Cause:** Wrong credentials or database not running

**Solution:**
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check .env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=fitnesspro
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### ❌ Migrations failed

**Cause:** Database already has tables

**Solution:**
```bash
# Fresh migration (⚠️ destroys data)
php artisan migrate:fresh

# Or reset
php artisan migrate:reset
php artisan migrate
```

### 💡 Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# View routes
php artisan route:list

# Generate app key
php artisan key:generate

# Run seeders
php artisan db:seed

# Create migration
php artisan make:migration create_table_name

# Create model with migration
php artisan make:model ModelName -m

# Create controller
php artisan make:controller ControllerName

# Create service
php artisan make:class Services/ServiceName

# Run queue worker
php artisan queue:work

# View logs
tail -f storage/logs/laravel.log
```

### 📚 Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

---

## 🎉 Conclusion

You now have a complete understanding of the FitnessPro backend!

**Key points:**
- ✅ Layered architecture (Controller → Service → Repository → Model)
- ✅ Laravel Sanctum authentication
- ✅ RESTful API design
- ✅ Database relationships with Eloquent
- ✅ Service layer for business logic
- ✅ Queue system for background jobs
- ✅ Comprehensive testing
- ✅ Production-ready deployment

---

**Version:** 2.1.0
**Last updated:** November 2025
**Author:** Ivan Petrov
