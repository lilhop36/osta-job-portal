# OSTA Job Portal — Deployment Checklist
# Run this before going live

echo "=== OSTA Job Portal Pre-Deployment Check ==="
echo ""

# 1. Environment
echo "[1] Checking .env file..."
if [ ! -f .env ]; then
    echo "  ERROR: .env file not found"
    exit 1
fi
source .env
if [ "$APP_ENV" = "production" ]; then
    echo "  OK: APP_ENV=production"
else
    echo "  WARNING: APP_ENV is not production (current: $APP_ENV)"
fi
if [ "$APP_DEBUG" = "false" ]; then
    echo "  OK: APP_DEBUG=false"
else
    echo "  WARNING: APP_DEBUG is not false"
fi

# 2. PHP version
echo ""
echo "[2] Checking PHP version..."
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "  PHP: $PHP_VERSION"
php -v | head -1

# 3. Required extensions
echo ""
echo "[3] Checking required PHP extensions..."
for ext in pdo_mysql mbstring json curl openssl gd zip bcmath intl; do
    if php -m | grep -qi "^$ext$"; then
        echo "  OK: $ext"
    else
        echo "  MISSING: $ext"
    fi
done

# 4. Composer
echo ""
echo "[4] Checking Composer..."
if command -v composer &> /dev/null; then
    composer --version
    echo "  Running composer install..."
    composer install --no-dev --optimize-autoloader --no-interaction
else
    echo "  ERROR: Composer not found"
fi

# 5. Database
echo ""
echo "[5] Checking database connection..."
php -r "
require 'vendor/autoload.php';
require 'config/database.php';
try {
    \$pdo->query('SELECT 1');
    echo '  OK: Database connection successful' . PHP_EOL;
} catch (Exception \$e) {
    echo '  ERROR: ' . \$e->getMessage() . PHP_EOL;
}
"

# 6. Migrations
echo ""
echo "[6] Checking migrations..."
php src/Database/MigrationRunner.php status 2>/dev/null || echo "  MigrationRunner not available"

# 7. File permissions
echo ""
echo "[7] Checking directories..."
for dir in uploads logs uploads/resumes uploads/cover_letters uploads/documents; do
    if [ -d "$dir" ]; then
        echo "  OK: $dir exists"
    else
        echo "  Creating: $dir"
        mkdir -p "$dir"
    fi
done

# 8. Security
echo ""
echo "[8] Security checks..."
if grep -q "APP_DEBUG=true" .env 2>/dev/null; then
    echo "  WARNING: APP_DEBUG is true in production"
fi
if grep -q "DB_PASS=.*password" .env 2>/dev/null; then
    echo "  WARNING: Default database password detected"
fi

echo ""
echo "=== Pre-deployment check complete ==="
