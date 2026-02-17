FROM php:8.3-fpm-bullseye

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       build-essential \
       locales \
       zip \
       vim \
       unzip \
       git \
       curl \
       libpq-dev \
       autoconf \
       pkg-config \
       libssl-dev \
       ca-certificates \
       libxml2-dev \
        libxslt1-dev \
        zlib1g-dev \
        libzip-dev \
       libsasl2-dev \
       libonig-dev \
        libzip-dev \
        zlib1g-dev \
       libjpeg62-turbo-dev \
       libpng-dev \
       libfreetype6-dev \
       libxml2-dev \
       re2c \
    && rm -rf /var/lib/apt/lists/*

# Clear apt cache (noop when lists already removed)
RUN apt-get clean || true

# Install PHP extensions
RUN docker-php-ext-install mbstring zip exif pcntl dom xml xmlwriter \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Install postgress
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Install MongoDB and Redis PHP extensions via PECL
RUN pecl install mongodb redis \
  && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini \
  && echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ARG APP_ENV=local
ENV APP_ENV=$APP_ENV

COPY ./environment/php/configuration/php.ini /usr/local/etc/php/php.ini
COPY ./environment/php/configuration/www.conf /usr/local/etc/php-fpm.d/www.conf

# Add user for laravel application
#RUN groupadd -g 1000 www
#RUN useradd -u 1000 -ms /bin/bash -g www www

#RUN chown www:www /var/www/

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www/
# Change current user to www
#USER www-data
USER root

# Copy existing application directory contents
# COPY composer.lock composer.json /var/www/
# COPY ./database /var/www/database
COPY ./environment/wait-for-it.sh /var/www/
COPY ./environment/php/fix-permissions.sh /usr/local/bin/fix-permissions.sh
RUN chmod +x /usr/local/bin/fix-permissions.sh
# Expose port 9000 and start php-fpm server
#EXPOSE 9000
# USER www-data
# Create entrypoint script that fixes permissions and starts php-fpm
RUN echo '#!/bin/bash\n/usr/local/bin/fix-permissions.sh\nexec php-fpm' > /entrypoint.sh && chmod +x /entrypoint.sh
CMD ["/entrypoint.sh"]
