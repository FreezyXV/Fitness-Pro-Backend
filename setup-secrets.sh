

echo "Configuration des secrets Fly.io pour fitness-pro..."

# Variables de base
fly secrets set APP_KEY="base64:zxAlMGWMB/PxVDEsHkZCbGyV2XxZ48VKazys5jtFvfI="
fly secrets set APP_URL="https://fitness-pro.fly.dev"
fly secrets set FRONTEND_URL="https://your-frontend-app.vercel.app"

# Base de données - REMPLACEZ PAR VOS VRAIES VALEURS
fly secrets set DB_CONNECTION="pgsql"
fly secrets set DB_HOST="your-postgres-host.fly.dev"
fly secrets set DB_PORT="5432"
fly secrets set DB_DATABASE="fitness_pro"
fly secrets set DB_USERNAME="postgres"
fly secrets set DB_PASSWORD="your-secure-password"

# Configuration CORS et Sanctum
fly secrets set SANCTUM_STATEFUL_DOMAINS="your-frontend-app.vercel.app"
fly secrets set CORS_ALLOWED_ORIGINS="https://your-frontend-app.vercel.app"

# Configuration sessions
fly secrets set SESSION_DOMAIN="fitness-pro.fly.dev"

echo "Configuration des secrets terminée!"
echo "Vérifiez avec: fly secrets list"