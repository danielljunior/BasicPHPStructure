FROM webdevops/php-apache:8.3-alpine

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

COPY httpd-vhost.conf /etc/apache2/conf.d/default.conf

WORKDIR /app
