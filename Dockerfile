FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libjpeg-dev libpng-dev libwebp-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install mysqli gd \
    && a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
      /etc/apache2/sites-available/000-default.conf \
      /etc/apache2/apache2.conf

COPY . /var/www/html
RUN mkdir -p /var/www/html/logs /var/www/html/public/assets/menu \
    && chown -R www-data:www-data /var/www/html/logs /var/www/html/public/assets/menu
