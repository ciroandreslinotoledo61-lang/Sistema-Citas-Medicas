FROM php:8.3-apache-bookworm
# Instalamos mbstring explicitamente para las validaciones de usuario.
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev \
    && docker-php-ext-install pdo_pgsql mbstring \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite
WORKDIR /var/www/app
COPY . /var/www/app
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/production.ini /usr/local/etc/php/conf.d/production.ini
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && mkdir -p /var/lib/php/sessions \
    && chown www-data:www-data /var/lib/php/sessions \
    && chmod 700 /var/lib/php/sessions \
    && php -r 'foreach (["curl", "pdo_pgsql", "mbstring", "ctype"] as $e) { if (!extension_loaded($e)) { fwrite(STDERR, "Falta extension: $e\n"); exit(1); } }'
EXPOSE 10000
CMD ["sh", "/var/www/app/deploy/iniciar.sh"]
