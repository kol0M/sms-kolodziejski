FROM php:8.0-fpm

# Install required system packages and dependencies
RUN apt update \
  && apt install -y zlib1g-dev g++ git libicu-dev zip libzip-dev zip libssl-dev \
  && docker-php-ext-install intl opcache pdo pdo_mysql \
  && pecl install apcu \
  && docker-php-ext-enable apcu \
  && docker-php-ext-configure zip \
  && docker-php-ext-install zip

# Install php-imap extension
RUN apt-get install -y libc-client-dev libkrb5-dev \
  && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
  && docker-php-ext-install imap

# Set the working directory
WORKDIR /var/www/sms-kolodziejski

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
  && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

RUN apt-get install -y openssl ca-certificates