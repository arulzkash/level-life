# 1. Gunakan Image PHP Official
FROM php:8.2-cli

# 2. Install Library System yang dibutuhkan Laravel
# PERHATIKAN: Setiap baris paket diakhiri dengan backslash (\)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    ca-certificates

# 3. Install PHP Extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 4. Install Node.js & NPM
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 5. Set Folder Kerja
WORKDIR /var/www/html

# 6. Copy semua file project
COPY . .

# 7. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# 8. Build Aset Vue
RUN npm install && npm run build

# 9. Set Permission
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# 10. Expose Port & Jalankan
ENV PORT=10000
EXPOSE 10000

CMD sh -c "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000"