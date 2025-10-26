# Simple Laravel + Vue.js Docker container
FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Install essential packages
RUN apt-get update && apt-get install -y \
    software-properties-common \
    curl \
    unzip \
    supervisor \
    nginx \
    gettext-base \
    && add-apt-repository -y ppa:ondrej/php \
    && apt-get update && apt-get install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-cli \
    php8.2-mysql \
    php8.2-redis \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-xdebug \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure PHP-FPM
RUN sed -i 's/listen = \/run\/php\/php8.2-fpm.sock/listen = 127.0.0.1:9000/' /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i 's/;clear_env = no/clear_env = no/' /etc/php/8.2/fpm/pool.d/www.conf \
    && mkdir -p /run/php

# Configure Xdebug
RUN echo "zend_extension=xdebug.so" > /etc/php/8.2/mods-available/xdebug.ini \
    && echo "xdebug.mode=develop,debug" >> /etc/php/8.2/mods-available/xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /etc/php/8.2/mods-available/xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /etc/php/8.2/mods-available/xdebug.ini \
    && echo "xdebug.client_port=9003" >> /etc/php/8.2/mods-available/xdebug.ini \
    && echo "xdebug.log=/tmp/xdebug.log" >> /etc/php/8.2/mods-available/xdebug.ini \
    && phpenmod xdebug

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install dependencies and build
RUN composer install --no-dev --optimize-autoloader \
    && npm install \
    && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/public

# Create Laravel command aliases
RUN echo 'alias pa="php artisan"' >> ~/.bashrc \
    && echo 'alias pam="php artisan migrate"' >> ~/.bashrc \
    && echo 'alias pas="php artisan serve"' >> ~/.bashrc \
    && echo 'alias paclear="php artisan optimize:clear"' >> ~/.bashrc \
    && echo 'alias ll="ls -la"' >> ~/.bashrc

# Create global command scripts for easier access
RUN echo '#!/bin/bash' > /usr/local/bin/pa \
    && echo 'cd /var/www && php artisan "$@"' >> /usr/local/bin/pa \
    && chmod +x /usr/local/bin/pa

# Create supervisor configuration
RUN echo '[supervisord]' > /etc/supervisor/conf.d/services.conf \
    && echo 'nodaemon=true' >> /etc/supervisor/conf.d/services.conf \
    && echo '' >> /etc/supervisor/conf.d/services.conf \
    && echo '[program:nginx]' >> /etc/supervisor/conf.d/services.conf \
    && echo 'command=nginx -g "daemon off;"' >> /etc/supervisor/conf.d/services.conf \
    && echo 'autostart=true' >> /etc/supervisor/conf.d/services.conf \
    && echo 'autorestart=true' >> /etc/supervisor/conf.d/services.conf \
    && echo 'stdout_logfile=/var/log/nginx.log' >> /etc/supervisor/conf.d/services.conf \
    && echo 'stderr_logfile=/var/log/nginx.log' >> /etc/supervisor/conf.d/services.conf \
    && echo '' >> /etc/supervisor/conf.d/services.conf \
    && echo '[program:php-fpm]' >> /etc/supervisor/conf.d/services.conf \
    && echo 'command=/usr/sbin/php-fpm8.2 -F' >> /etc/supervisor/conf.d/services.conf \
    && echo 'autostart=true' >> /etc/supervisor/conf.d/services.conf \
    && echo 'autorestart=true' >> /etc/supervisor/conf.d/services.conf \
    && echo 'stdout_logfile=/var/log/php-fpm.log' >> /etc/supervisor/conf.d/services.conf \
    && echo 'stderr_logfile=/var/log/php-fpm.log' >> /etc/supervisor/conf.d/services.conf \
    && echo '' >> /etc/supervisor/conf.d/services.conf \
    && echo '[program:vite]' >> /etc/supervisor/conf.d/services.conf \
    && echo 'command=/bin/bash -c "cd /var/www && VITE_HMR_HOST=localhost npm run dev"' >> /etc/supervisor/conf.d/services.conf \
    && echo 'directory=/var/www' >> /etc/supervisor/conf.d/services.conf \
    && echo 'autostart=true' >> /etc/supervisor/conf.d/services.conf \
    && echo 'autorestart=true' >> /etc/supervisor/conf.d/services.conf \
    && echo 'user=www-data' >> /etc/supervisor/conf.d/services.conf \
    && echo 'stdout_logfile=/var/log/vite.log' >> /etc/supervisor/conf.d/services.conf \
    && echo 'stderr_logfile=/var/log/vite.log' >> /etc/supervisor/conf.d/services.conf

# Create Vite startup script
RUN echo '#!/bin/bash' > /usr/local/bin/start-vite \
    && echo 'cd /var/www' >> /usr/local/bin/start-vite \
    && echo 'export VITE_HMR_HOST=localhost' >> /usr/local/bin/start-vite \
    && echo 'npm run dev' >> /usr/local/bin/start-vite \
    && chmod +x /usr/local/bin/start-vite

# Copy nginx configuration
COPY docker/nginx/regulus.conf /etc/nginx/sites-available/default

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]