# FitnessPro - Backend API

A robust Laravel-based REST API powering the FitnessPro fitness tracking application. This backend provides comprehensive fitness management capabilities including user authentication, workout planning, nutrition tracking, goal setting, and performance analytics.

## 🏗️ Architecture Overview

### Technology Stack
- **Framework**: Laravel 12.x (PHP 8.2+)
- **Authentication**: Laravel Sanctum (SPA-optimized)
- **Database**: MySQL/PostgreSQL with optimized indexes
- **API Architecture**: RESTful API with comprehensive error handling
- **Cache**: Redis/File-based caching for performance
- **Queue**: Background job processing for heavy operations

### Core Features
- **Authentication & Authorization**: Secure user management with role-based access
- **Exercise Management**: Comprehensive exercise database with search and filtering
- **Workout System**: Template creation, session logging, and progress tracking
- **Nutrition Tracking**: Meal logging, calorie counting, and diet management
- **Goal Setting**: SMART goals with progress tracking and achievements
- **Calendar Integration**: Workout scheduling and task management
- **Dashboard Analytics**: Performance metrics and progress visualization
- **Gamification**: Achievement system with scoring and leaderboards

## 🚀 Quick Start

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL/PostgreSQL database
- Redis (optional, for caching)
- Node.js & npm (for asset compilation)

### Installation

1. **Clone and setup**
   ```bash
   cd backend
   composer install
   npm install
   ```

2. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database setup**
   ```bash
   # Configure database in .env file
   php artisan migrate
   php artisan db:seed
   ```

4. **Start development server**
   ```bash
   # Option 1: Laravel's built-in server
   php artisan serve

   # Option 2: Full development environment
   composer run dev
   ```

### Environment Variables
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fitness_pro
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Application
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:4200

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:4200

# Cache (optional)
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## 📊 Database Schema

### Core Tables
- **users**: User accounts with profiles and preferences
- **exercises**: Exercise database with muscle groups and equipment
- **workouts**: Workout templates and logged sessions
- **workout_exercises**: Pivot table for workout-exercise relationships
- **goals**: User fitness goals with progress tracking
- **calendar_tasks**: Scheduled workouts and fitness tasks
- **meal_entries**: Nutrition logging and calorie tracking
- **user_diets**: Personalized diet plans and regimes
- **achievements**: Gamification system for user motivation
- **user_scores**: Points and leaderboard system

### Key Relationships
```
Users (1:M) → Workouts
Users (1:M) → Goals
Users (1:M) → MealEntries
Workouts (M:M) → Exercises (via WorkoutExercises)
Users (1:M) → Achievements (via UserAchievements)
```

## 🔌 API Endpoints

### Authentication (`/api/auth`)
```
POST   /login              # User authentication
POST   /register           # User registration
POST   /logout             # User logout
GET    /me                 # Current user info
POST   /password/email     # Password reset request
POST   /password/reset     # Password reset confirmation
```

### Exercises (`/api/exercises`)
```
GET    /                   # List all exercises
GET    /search             # Search exercises
GET    /{id}               # Get specific exercise
GET    /body-parts         # Get body part categories
GET    /categories         # Get exercise categories
POST   /{id}/favorite      # Toggle exercise favorite (auth)
```

### Workouts (`/api/workouts`)
```
# Templates
GET    /templates          # List workout templates
POST   /templates          # Create workout template
GET    /templates/{id}     # Get specific template
PUT    /templates/{id}     # Update template
DELETE /templates/{id}     # Delete template

# Sessions
GET    /logs               # List workout sessions
POST   /logs               # Log workout session
POST   /start              # Start workout session
POST   /logs/{id}/complete # Complete workout

# Statistics
GET    /stats              # Workout statistics
GET    /stats/weekly       # Weekly breakdown
GET    /stats/monthly      # Monthly analysis
```

### Nutrition (`/api/nutrition`)
```
GET    /daily-summary/{date}    # Daily nutrition summary
POST   /water-intake           # Log water intake
GET    /meals/{date}           # Get meal entries
POST   /meals                  # Add meal entry
PUT    /meals/{id}             # Update meal entry
DELETE /meals/{id}             # Delete meal entry
GET    /goals                  # Get nutrition goals
POST   /goals                  # Set nutrition goals
POST   /diet/generate          # Generate personalized diet
```

### Goals (`/api/goals`)
```
GET    /                   # List user goals
POST   /                   # Create new goal
GET    /{id}               # Get specific goal
PUT    /{id}               # Update goal
DELETE /{id}               # Delete goal
POST   /{id}/progress      # Update goal progress
POST   /{id}/complete      # Mark goal complete
```

### Calendar (`/api/calendar`)
```
GET    /tasks              # List calendar tasks
POST   /tasks              # Create task
GET    /today              # Today's tasks
GET    /week               # Week's tasks
GET    /month/{month}      # Monthly tasks
POST   /tasks/{id}/complete # Mark task complete
```

### Dashboard (`/api/dashboard`)
```
GET    /                   # Dashboard overview
GET    /stats              # General statistics
GET    /performance        # Performance metrics
GET    /recent-activity    # Recent user activity
GET    /monthly            # Monthly overview
GET    /progress           # Progress tracking
```

## 🔧 Development

### Project Structure
```
backend/
├── app/
│   ├── Http/Controllers/     # API controllers
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic services
│   ├── Traits/              # Reusable model traits
│   └── Rules/               # Custom validation rules
├── database/
│   ├── migrations/          # Database migrations
│   ├── seeders/            # Database seeders
│   └── factories/          # Model factories
├── routes/
│   └── api.php             # API route definitions
├── config/                 # Configuration files
└── tests/                  # Test suites
```

### Key Controllers
- **AuthController**: User authentication and session management
- **ExerciseController**: Exercise database and favorites
- **WorkoutController**: Workout templates and session logging
- **NutritionController**: Meal tracking and diet management
- **GoalController**: Goal setting and progress tracking
- **CalendarController**: Workout scheduling and task management
- **DashboardController**: Analytics and performance metrics

### Models & Relationships
- **User**: Central user model with comprehensive relationships
- **Exercise**: Exercise database with categorization
- **Workout**: Workout templates and logged sessions
- **Goal**: SMART goals with progress tracking
- **MealEntry**: Nutrition logging with calorie calculations
- **Achievement**: Gamification system for user engagement

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Generate test coverage
php artisan test --coverage
```

### Code Quality
```bash
# Format code
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse

# Clear caches
php artisan cache:clear
php artisan config:clear
```

## 🚀 Deployment

### Production Setup
1. **Environment configuration**
   ```bash
   # Set production environment variables
   APP_ENV=production
   APP_DEBUG=false

   # Configure database and cache
   # Set up queue workers
   # Configure Redis for sessions
   ```

2. **Optimization**
   ```bash
   # Cache configuration and routes
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache

   # Optimize autoloader
   composer install --optimize-autoloader --no-dev
   ```

3. **Queue workers** (for background jobs)
   ```bash
   php artisan queue:work --daemon
   ```

### Docker Support
```bash
# Build production image
docker build -f Dockerfile.prod -t fitness-pro-backend .

# Run with docker-compose
docker-compose -f docker-compose.prod.yml up -d
```

### Health Checks
- `GET /api/health` - Complete system health check
- `GET /api/status` - Basic API status
- `GET /api/test` - Connectivity test

## 🔐 Security Features

### Authentication
- **Laravel Sanctum**: SPA-optimized authentication
- **Token Management**: Secure token generation and validation
- **Session Security**: CSRF protection and secure cookies
- **Rate Limiting**: API endpoint protection

### Data Protection
- **Input Validation**: Comprehensive request validation
- **SQL Injection Prevention**: Eloquent ORM protection
- **XSS Protection**: Output sanitization
- **CORS Configuration**: Frontend domain whitelisting

### Privacy
- **Data Encryption**: Sensitive data encryption at rest
- **Secure Headers**: Security-focused HTTP headers
- **Audit Logging**: User action tracking
- **GDPR Compliance**: Data export and deletion features

## 📈 Performance

### Database Optimization
- **Indexes**: Optimized indexes for frequent queries
- **Query Optimization**: Efficient Eloquent relationships
- **Connection Pooling**: Database connection management
- **Pagination**: Large dataset handling

### Caching Strategy
- **Query Caching**: Database query result caching
- **API Response Caching**: Expensive computation caching
- **Session Storage**: Redis-based session management
- **Static Assets**: CDN integration ready

### Monitoring
- **Laravel Telescope**: Development debugging (dev only)
- **Performance Metrics**: Response time tracking
- **Error Logging**: Comprehensive error reporting
- **Resource Monitoring**: Memory and CPU usage tracking

## 🤝 API Integration

### Frontend Integration
- **CORS Configuration**: Angular frontend support
- **Authentication Flow**: Sanctum SPA authentication
- **Error Handling**: Standardized error responses
- **Request Validation**: Comprehensive input validation

### Response Format
```json
{
  "success": true,
  "data": { /* response data */ },
  "message": "Operation successful",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

### Error Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": { /* validation errors */ },
  "code": "ERROR_CODE"
}
```

## 🛠️ Troubleshooting

### Common Issues
1. **CORS Errors**: Check `SANCTUM_STATEFUL_DOMAINS` configuration
2. **Database Connection**: Verify database credentials and connectivity
3. **Authentication Issues**: Ensure Sanctum middleware is properly configured
4. **Performance Issues**: Check database indexes and query optimization

### Debug Mode
```bash
# Enable debug logging
APP_DEBUG=true

# Check logs
tail -f storage/logs/laravel.log

# Clear application cache
php artisan cache:clear
php artisan config:clear
```

### Development Tools
- **Laravel Telescope**: Request/response debugging
- **Laravel Tinker**: Interactive PHP shell
- **Database Debugging**: Query logging and analysis
- **API Testing**: Built-in test endpoints

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [API Documentation](../docs/api.md) (if available)
- [Deployment Guide](../DEPLOYMENT_GUIDE.md)

---

**Note**: This backend is designed to work seamlessly with the FitnessPro Angular frontend. For complete application setup, refer to the main project README.md in the root directory.