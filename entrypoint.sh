#!/bin/bash
set -e

# Default to 80 if PORT is not set (Render injects PORT=10000 dynamically)
PORT="${PORT:-80}"
echo "==> [Nilayam Heights] Configuring Apache on port: ${PORT}"

# Ensure a mounted persistent disk is writable by Apache's PHP workers.
UPLOAD_DIR="${HMS_PRIVATE_UPLOAD_DIR:-/var/lib/nilayam-hms/uploads}"
case "$UPLOAD_DIR" in
    /*) ;;
    *)
        echo "ERROR: HMS_PRIVATE_UPLOAD_DIR must be an absolute path." >&2
        exit 1
        ;;
esac
install -d -o www-data -g www-data -m 0750 "$UPLOAD_DIR"
export HMS_PRIVATE_UPLOAD_DIR="$UPLOAD_DIR"

# Replace Apache Listen port and VirtualHost port dynamically
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/*.conf

# Set Apache DocumentRoot to /var/www/html/HMS-2 so endpoints work cleanly from root
sed -i "s|DocumentRoot /var/www/html|DocumentRoot /var/www/html/HMS-2|g" /etc/apache2/sites-available/000-default.conf

# Add Alias for /HMS-2 to support backwards-compatible links
if ! grep -q "Alias /HMS-2" /etc/apache2/sites-available/000-default.conf; then
    sed -i '/DocumentRoot/a \    Alias /HMS-2 /var/www/html/HMS-2' /etc/apache2/sites-available/000-default.conf
fi

# Ensure correct Directory permissions for Apache
if ! grep -q "<Directory /var/www/html/HMS-2>" /etc/apache2/sites-available/000-default.conf; then
    cat <<EOF >> /etc/apache2/sites-available/000-default.conf

<Directory /var/www/html/HMS-2>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
EOF
fi

# Deny direct access to private documents and environment/database files.
if ! grep -q "HMS private file access rules" /etc/apache2/sites-available/000-default.conf; then
    cat <<'EOF' >> /etc/apache2/sites-available/000-default.conf

# HMS private file access rules
<Directory /var/www/html/HMS-2/pg/uploads>
    Require all denied
</Directory>
<Directory /var/www/html/HMS-2/uploads/society_kyc>
    Require all denied
</Directory>
<FilesMatch "(?i)^(\.env(\..*)?|.*\.(sql|bak|backup|log|ini))$">
    Require all denied
</FilesMatch>
EOF
fi

# Pass environment variables from container environment to PHP via Apache
if ! grep -q "PassEnv DB_HOST" /etc/apache2/sites-available/000-default.conf; then
    sed -i '/DocumentRoot/a \    PassEnv DB_HOST DB_USER DB_PORT DB_NAME DB_PASS APP_ENV APP_DEBUG HMS_PRIVATE_UPLOAD_DIR' /etc/apache2/sites-available/000-default.conf
fi

# ==============================================================================
# Database Strategy:
# If no external DB_HOST is set, or if set to localhost/127.0.0.1:
# Boot the built-in MariaDB database server and import an explicitly mounted seed file.
# ==============================================================================
if [ -z "$DB_HOST" ] || [ "$DB_HOST" = "localhost" ] || [ "$DB_HOST" = "127.0.0.1" ]; then
    echo "==> [Nilayam Heights] Starting built-in MariaDB server..."
    service mariadb start || service mysql start

    # Grant permissions for root on both socket and TCP 127.0.0.1
    mysql -e "CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY ''; GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;" 2>/dev/null || true
    mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD(''); FLUSH PRIVILEGES;" 2>/dev/null || true

    # Initialize hms database from an explicitly mounted seed file if needed.
    if ! mysql -u root -e "USE hms" 2>/dev/null; then
        if [ -z "${DB_SEED_FILE:-}" ] || [ ! -r "$DB_SEED_FILE" ]; then
            echo "ERROR: Built-in database initialization requires DB_SEED_FILE to point to a readable SQL seed file." >&2
            exit 1
        fi
        echo "==> [Nilayam Heights] Initializing 'hms' database from the configured seed file..."
        mysql -u root -e "CREATE DATABASE IF NOT EXISTS hms;"
        mysql -u root hms < "$DB_SEED_FILE"
        echo "==> [Nilayam Heights] Database import complete!"
    else
        echo "==> [Nilayam Heights] Database 'hms' already present."
    fi

    # Set default localhost bindings for Apache
    export DB_HOST="127.0.0.1"
    export DB_USER="root"
    export DB_PASS=""
    export DB_NAME="hms"
    export DB_PORT="3306"
else
    echo "==> [Nilayam Heights] Connecting to external cloud MySQL database at ${DB_HOST}:${DB_PORT:-3306}..."
fi

# Export all environment variables into Apache envvars so PHP getenv() sees them
echo "export DB_HOST=\"${DB_HOST}\"" >> /etc/apache2/envvars
echo "export DB_USER=\"${DB_USER}\"" >> /etc/apache2/envvars
echo "export DB_PASS=\"${DB_PASS}\"" >> /etc/apache2/envvars
echo "export DB_NAME=\"${DB_NAME}\"" >> /etc/apache2/envvars
echo "export DB_PORT=\"${DB_PORT}\"" >> /etc/apache2/envvars
echo "export HMS_PRIVATE_UPLOAD_DIR=\"${HMS_PRIVATE_UPLOAD_DIR:-/var/lib/nilayam-hms/uploads}\"" >> /etc/apache2/envvars
echo "export APP_ENV=\"${APP_ENV:-production}\"" >> /etc/apache2/envvars
echo "export APP_DEBUG=\"${APP_DEBUG:-false}\"" >> /etc/apache2/envvars

echo "==> [Nilayam Heights] Starting Apache foreground server..."
# Execute main container command (apache2-foreground)
exec "$@"
