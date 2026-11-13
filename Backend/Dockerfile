# syntax=docker/dockerfile:1
# Imagem de produção da API: FrankenPHP (Caddy + PHP no mesmo processo).
# Desenvolvimento usa o Dockerfile.dev, pelo compose.yaml.
FROM dunglas/frankenphp:1-php8.4

# opcache: keeps compiled PHP in memory between requests
RUN install-php-extensions pdo_mysql zip bcmath opcache \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY . .

# no dev dependencies (phpunit etc.) and an optimized class map.
# The copied bootstrap caches list development packages, so they are rebuilt.
RUN rm -f bootstrap/cache/*.php storage/logs/*.log \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && composer check-platform-reqs --no-dev

# Plain HTTP on $PORT; TLS belongs to the proxy or hosting platform in front.
COPY <<'EOF' /etc/frankenphp/Caddyfile
{
	frankenphp
	admin off
	auto_https off
}

:{$PORT:8000} {
	root * /app/public
	encode zstd gzip

	# Swagger UI is a static folder in public/api/docs; a request for the
	# folder itself would otherwise reach Laravel, which has no route for it.
	@docs path /api/docs /api/docs/
	rewrite @docs /api/docs/index.html

	# Existing files in public/ are served directly; the rest goes to Laravel.
	php_server
}
EOF

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SESSION_SECURE_COOKIE=true \
    PORT=8000

EXPOSE 8000

# public/storage -> storage/app/public, so uploaded product images are served;
# optimize caches config, routes and events with the real environment.
# shortcut: the scheduler (orders:cancel-unpaid) runs in the background and is not restarted if it dies; give it its own container when that matters.
CMD ["sh", "-c", "php artisan storage:link --force && php artisan optimize && (php artisan schedule:work &) && exec frankenphp run --config /etc/frankenphp/Caddyfile"]
