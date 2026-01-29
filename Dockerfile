# 1. Base Image Apache (Multi-thread)
FROM php:8.2-apache

# 2. Install System Dependencies
# Tambahkan libzip-dev untuk zip, libpq-dev untuk postgres
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo_pgsql pgsql zip opcache

# 3. Config Apache Document Root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
RUN a2enmod rewrite

# -----------------------------------------------------------
# 4. TUNING PERFORMA PHP (The "Tank" Part)
# -----------------------------------------------------------

# A. Naikkan Batas Memori & Waktu (Biar gak gampang Timeout 500)
# Kita kasih waktu 300 detik (5 menit) sebelum menyerah.
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "max_execution_time=300" > /usr/local/etc/php/conf.d/timeout.ini \
    && echo "max_input_time=300" > /usr/local/etc/php/conf.d/timeout-input.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/upload.ini \
    && echo "post_max_size=20M" > /usr/local/etc/php/conf.d/post.ini

# B. Aktifkan OPcache (Wajib buat Production)
# Menyimpan script PHP di RAM biar CPU gak perlu compile ulang tiap request
RUN { \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# -----------------------------------------------------------

# 5. Install Node.js (Untuk Build Vue)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 6. Setup Folder Kerja
WORKDIR /var/www/html
COPY . .

# 7. Install Composer & Dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 8. Build Frontend (Vue)
RUN npm install && npm run build && rm -rf node_modules

# 9. Set Permission (Penting buat Cache Laravel)
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# 10. COMMAND STARTUP "AUTO-OPTIMIZE"
# Tiap kali deploy, dia akan otomatis cache config, route, view, dan event.
# Ini meringankan beban CPU Render secara drastis saat aplikasi jalan.
CMD sh -c "php artisan migrate --force && \
           php artisan config:cache && \
           php artisan route:cache && \
           php artisan view:cache && \
           php artisan event:cache && \
           apache2-foreground"