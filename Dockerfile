# 1. Gunakan Image PHP dengan Apache (Otomatis Multi-Thread)
FROM php:8.2-apache

# 2. Install Library System (Wajib ada libpq-dev buat Postgres)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    curl \
    ca-certificates

# 3. Install PHP Extensions
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# 4. Config Apache: Arahkan Document Root ke /public Laravel
# Ini penting biar Apache tau folder mana yang harus dibuka
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 5. Aktifkan Mod Rewrite (Supaya routing Laravel jalan)
RUN a2enmod rewrite

# 6. Install Node.js & NPM
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 7. Set Folder Kerja
WORKDIR /var/www/html

# 8. Copy Project
COPY . .

# 9. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# 10. Build Aset Vue
RUN npm install && npm run build

# 11. Permission (PENTING: Apache jalan sebagai user www-data)
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# 12. Jalankan Apache
# Hapus "php artisan serve". Kita pakai "apache2-foreground"
# Render otomatis mapping port 80 internal ke port luar
CMD sh -c "php artisan migrate --force && apache2-foreground"