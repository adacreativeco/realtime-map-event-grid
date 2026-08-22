# ==========================================
# Realtime Map Event Grid - Production Dockerfile
# Base: PHP 8.2 CLI Alpine (Ultra lightweight ~75MB)
# Drivers: SQLite, PostgreSQL/PostGIS, MySQL/MariaDB
# ==========================================

FROM php:8.2-cli-alpine

LABEL maintainer="ADA Creative Co. <git@adacreative.co>"
LABEL description="Realtime Map Event Grid (RTEG) - Spatial Event Ingestion & Visualization Engine"

# Install system dependencies & SQLite, PostgreSQL, MySQL libraries
RUN apk add --no-cache \
    sqlite-libs \
    sqlite-dev \
    postgresql-dev \
    curl \
    libpng-dev \
    oniguruma-dev

# Install PHP PDO extensions for all 3 database engines
RUN docker-php-ext-install pdo pdo_sqlite pdo_pgsql pdo_mysql curl mbstring

# Set working directory
WORKDIR /var/www/html

# Copy project source code
COPY . /var/www/html

# Make entrypoint executable
RUN chmod +x /var/www/html/docker-entrypoint.sh

# Expose server port
EXPOSE 8081

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:8081/api/v1/public/events.php?limit=1 || exit 1

# Start container via entrypoint
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
