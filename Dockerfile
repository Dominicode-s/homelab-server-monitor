FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite-dev \
    oniguruma-dev \
    libzip-dev \
    libpng-dev \
    libxml2-dev \
    curl \
    git \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_sqlite \
    zip \
    exif \
    bcmath \
    pcntl \
    opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create Laravel cache/storage directories before composer install
RUN mkdir -p /var/www/html/bootstrap/cache /var/www/html/storage/framework/{sessions,views,cache,testing} /var/www/html/storage/database && \
    chmod -R 755 /var/www/html/bootstrap/cache /var/www/html/storage

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node.js dependencies and build assets
RUN npm install --no-audit --no-fund && npm run build

# Set correct ownership after composer install
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    touch /var/www/html/storage/database/database.sqlite && \
    chmod 644 /var/www/html/storage/database/database.sqlite

# Copy Nginx config
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy Supervisor config (Alpine expects it at /etc/supervisord.conf)
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# Cache views and routes (NOT config — docker-compose overrides env vars at runtime)
RUN php artisan view:cache && php artisan route:cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
