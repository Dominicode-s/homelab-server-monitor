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

# Setup storage permissions
RUN chmod -R 755 storage bootstrap/cache

# Copy Nginx config
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create SQLite database directory
RUN mkdir -p /var/www/html/storage/database && \
    touch /var/www/html/storage/database/database.sqlite && \
    chmod 644 /var/www/html/storage/database/database.sqlite

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
