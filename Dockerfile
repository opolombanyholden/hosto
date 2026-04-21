FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev libgd-dev libicu-dev \
    git unzip curl \
    && docker-php-ext-install pdo_pgsql pgsql zip gd intl bcmath \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
