# Multi-stage build for Laravel application with PHP 8.2 and Node.js 20.19.5
FROM ubuntu:22.04

# Avoid interactive prompts during build
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies and PHP 8.2
RUN apt-get update && apt-get install -y \
    software-properties-common \
    curl \
    zip \
    unzip \
    git \
    vim \
    nano \
    wget \
    ca-certificates \
    gnupg \
    lsb-release \
    supervisor \
    && add-apt-repository -y ppa:ondrej/php \
    && apt-get update && apt-get install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-cli \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-mysql \
    php8.2-curl \
    php8.2-intl \
    php8.2-zip \
    php8.2-gd \
    php8.2-redis \
    php8.2-sqlite3 \
    php8.2-tokenizer \
    php8.2-dom \
    php8.2-fileinfo \
    php8.2-opcache \
    nginx \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js 20.19.5 (matching local version)
RUN curl -fsSL https://nodejs.org/dist/v20.19.5/node-v20.19.5-linux-x64.tar.xz | tar -xJ -C /usr/local --strip-components=1 --no-same-owner

# Update PATH for Node.js
ENV PATH="/usr/local/bin:${PATH}"

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure PHP-FPM
RUN sed -i 's/listen = \/run\/php\/php8.2-fpm.sock/listen = 127.0.0.1:9000/' /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i 's/;clear_env = no/clear_env = no/' /etc/php/8.2/fpm/pool.d/www.conf \
    && mkdir -p /run/php \
    && mkdir -p /var/log/nginx

# Configure PHP settings for Laravel
RUN echo "upload_max_filesize = 100M" >> /etc/php/8.2/fpm/conf.d/99-laravel.ini \
    && echo "post_max_size = 100M" >> /etc/php/8.2/fpm/conf.d/99-laravel.ini \
    && echo "max_execution_time = 300" >> /etc/php/8.2/fpm/conf.d/99-laravel.ini \
    && echo "memory_limit = 512M" >> /etc/php/8.2/fpm/conf.d/99-laravel.ini \
    && echo "opcache.enable=1" >> /etc/php/8.2/fpm/conf.d/99-laravel.ini \
    && echo "opcache.memory_consumption=128" >> /etc/php/8.2/fpm/conf.d/99-laravel.ini \
    && echo "opcache.interned_strings_buffer=8" >> /etc/php/8.2/fpm/conf.d/99-laravel.ini \
    && echo "opcache.max_accelerated_files=4000" >> /etc/php/8.2/fpm/conf.d/99-laravel.ini \
    && echo "opcache.revalidate_freq=60" >> /etc/php/8.2/fpm/conf.d/99-laravel.ini

# Copy PHP CLI settings
RUN cp /etc/php/8.2/fpm/conf.d/99-laravel.ini /etc/php/8.2/cli/conf.d/99-laravel.ini

# Configure Xdebug for development
RUN apt-get update && apt-get install -y php8.2-xdebug \
    && echo "zend_extension=xdebug.so" > /etc/php/8.2/mods-available/xdebug.ini \
    && echo "xdebug.mode=develop,debug" >> /etc/php/8.2/mods-available/xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /etc/php/8.2/mods-available/xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /etc/php/8.2/mods-available/xdebug.ini \
    && echo "xdebug.client_port=9003" >> /etc/php/8.2/mods-available/xdebug.ini \
    && phpenmod xdebug \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies with Composer (without scripts to avoid errors)
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts

# Copy package.json files for Node.js dependencies
COPY package.json package-lock.json ./

# Install Node.js dependencies
RUN npm ci --only=production

# Copy the rest of the application files
COPY . /var/www

# Set proper permissions for Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Run composer scripts now that all files are in place
RUN composer dump-autoload --optimize

# Build assets (after all files are copied)
RUN npm run build

# Create Laravel command aliases
RUN echo 'alias pa="php artisan"' >> ~/.bashrc \
    && echo 'alias paclear="php artisan optimize:clear && chown -R www-data:www-data /var/www/bootstrap/cache && chmod -R 775 /var/www/bootstrap/cache"' >> ~/.bashrc

# Create global command scripts
RUN echo '#!/bin/bash' > /usr/local/bin/pa \
    && echo 'cd /var/www && php artisan "$@"' >> /usr/local/bin/pa \
    && chmod +x /usr/local/bin/pa \
    && echo '#!/bin/bash' > /usr/local/bin/paclear \
    && echo 'cd /var/www && php artisan optimize:clear && chown -R www-data:www-data /var/www/bootstrap/cache && chmod -R 775 /var/www/bootstrap/cache' >> /usr/local/bin/paclear \
    && chmod +x /usr/local/bin/paclear

# Create supervisor configuration for Laravel services
RUN mkdir -p /etc/supervisor/conf.d && echo '[supervisord]' > /etc/supervisor/conf.d/laravel.conf \
    && echo 'nodaemon=true' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'user=root' >> /etc/supervisor/conf.d/laravel.conf \
    && echo '' >> /etc/supervisor/conf.d/laravel.conf \
    && echo '[program:nginx]' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'command=nginx -g "daemon off;"' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'autostart=true' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'autorestart=true' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'stdout_logfile=/var/log/nginx.log' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'stderr_logfile=/var/log/nginx.log' >> /etc/supervisor/conf.d/laravel.conf \
    && echo '' >> /etc/supervisor/conf.d/laravel.conf \
    && echo '[program:php-fpm]' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'command=/usr/sbin/php-fpm8.2 -F' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'autostart=true' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'autorestart=true' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'stdout_logfile=/var/log/php-fpm.log' >> /etc/supervisor/conf.d/laravel.conf \
    && echo 'stderr_logfile=/var/log/php-fpm.log' >> /etc/supervisor/conf.d/laravel.conf

# Expose port 80
EXPOSE 80

# Start services with Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
