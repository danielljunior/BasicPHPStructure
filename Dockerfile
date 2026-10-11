FROM renatomb/alpine-apache-php-git:php8.3

# Install PHP extensions and tools using Alpine's package manager
RUN apk add --no-cache \
    php83-pdo_mysql \
    php83-mysqli \
    php83-gd \
    php83-intl \
    php83-zip \
    php83-opcache \
    nodejs \
    npm \
    vim \
    curl

# Make Apache serve from /app (to match the volume mapping)
# If the image already uses /app as DocumentRoot, this sed is harmless.
RUN sed -i 's|/var/www/localhost/htdocs|/app|g' /etc/apache2/httpd.conf \
 && mkdir -p /app

WORKDIR /app
