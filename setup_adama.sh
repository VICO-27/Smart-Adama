#!/bin/bash
set -e

echo "========================================"
echo "    Smart Adama Setup & Launch Script   "
echo "========================================"

# Check if PostgreSQL and Redis are running
echo "Checking dependencies..."
if ! command -v psql &> /dev/null; then
    echo "Warning: PostgreSQL client (psql) not found. Assuming Postgres is running."
fi

if ! command -v redis-cli &> /dev/null; then
    echo "Warning: redis-cli not found. Assuming Redis is running."
fi

# 1. Backend Setup
echo ""
echo "--- [1/4] Setting up Backend (Laravel) ---"
cd backend

if [ ! -f .env ]; then
    echo "Copying .env.example to .env..."
    cp .env.example .env
    php artisan key:generate
fi

echo "Installing Composer dependencies..."
composer install --no-interaction

echo "Running migrations and seeders..."
php artisan migrate:fresh --seed --force

echo "Running backend test suite..."
php artisan test

cd ..

# 2. Frontend Setup
echo ""
echo "--- [2/4] Setting up Frontend (Vue) ---"
cd frontend

echo "Installing NPM dependencies..."
npm install

if [ ! -f .env ]; then
    echo "Creating default frontend .env..."
    echo "VITE_API_BASE_URL=http://localhost:8000" > .env
fi

# Run any build step just to verify it compiles (optional, but good for health check)
# echo "Building frontend to verify integrity..."
# npm run build

cd ..

# 3. Running Services
echo ""
echo "--- [3/4] Ready to Launch ---"
echo "Starting services (Backend API, Queue Worker, Frontend Vite server)..."
echo "Press Ctrl+C to stop."
echo ""

# The backend composer.json has a "dev" script using concurrently. Let's run it.
cd backend
composer run dev
