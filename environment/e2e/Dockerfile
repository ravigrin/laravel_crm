FROM php:7.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libfreetype6-dev \
    locales \
    zip \
    vim \
    unzip \
    git \
    curl

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install mbstring zip exif pcntl

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Add user for test
RUN groupadd -g 1000 test
RUN useradd -u 1000 -ms /bin/bash -g test test

# Set working directory
WORKDIR /tests

# Add composer files and copy source tests to workdir
COPY tests/e2e/composer.lock tests/e2e/composer.json /tests/
COPY --chown=test:test ./tests/e2e /tests

RUN composer install --no-dev

# Change current user to test
USER test

# Expose port 9000 and start php-fpm server
EXPOSE 9000

CMD ["php-fpm"]


