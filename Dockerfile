FROM php:7.4-apache

# 1. Install ekstensi MySQL yang dibutuhkan PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 2. Aktifkan modul rewrite Apache (wajib jika ada file .htaccess)
RUN a2enmod rewrite

# 3. Salin source code ke dalam web root Apache
COPY . /var/www/html/

# 4. Berikan izin akses folder ke user Apache (www-data)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
