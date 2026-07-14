FROM debian:bookworm-slim AS assets

ARG TAILWIND_VERSION=3.4.17
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && ARCH=$(dpkg --print-architecture) \
    && case "$ARCH" in amd64) T=x64 ;; arm64) T=arm64 ;; *) echo "unsupported arch $ARCH" && exit 1 ;; esac \
    && curl -fsSL -o /usr/local/bin/tailwindcss \
       "https://github.com/tailwindlabs/tailwindcss/releases/download/v${TAILWIND_VERSION}/tailwindcss-linux-${T}" \
    && chmod +x /usr/local/bin/tailwindcss

WORKDIR /build
COPY tailwind.config.js ./
COPY src ./src
COPY public ./public
RUN tailwindcss -c tailwind.config.js -i src/tailwind.input.css -o /build/tailwind.css --minify

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
COPY --from=assets /build/tailwind.css /var/www/html/public/assets/tailwind.css
RUN mkdir -p /var/www/html/logs /var/www/html/public/assets/menu \
    && chown -R www-data:www-data /var/www/html/logs /var/www/html/public/assets/menu
