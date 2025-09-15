
# ----------------------------
# Fichier de tests CURL pour API
# ----------------------------

# ----------------------------
# Test Registration
# ----------------------------
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "first_name": "Ivan",
    "last_name": "Petrov",
    "email": "ivan@test.com",
    "password": "password123",
    "password_confirmation": "password123",
    "acceptTerms": true
  }'

echo -e "\n------------------------\n"

# ----------------------------
# Test Login (exemple)
# ----------------------------
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "ivan@test.com",
    "password": "password123"
  }'

echo -e "\n------------------------\n"
