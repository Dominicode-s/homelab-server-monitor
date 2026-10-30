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
    git

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

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Create Laravel storage/framework directories with correct ownership
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache,testing} /var/www/html/storage/database && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache && \
    touch /var/www/html/storage/database/database.sqlite && \
    chmod 644 /var/www/html/storage/database/database.sqlite

# Copy Nginx config
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy Supervisor config (Alpine expects it at /etc/supervisord.conf)
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
