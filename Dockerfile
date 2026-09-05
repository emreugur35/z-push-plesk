FROM php:8.2-apache-bookworm

# Install required system dependencies, logrotate, cron & PHP extension build dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libc-client2007e-dev \
    libkrb5-dev \
    libxml2-dev \
    libzip-dev \
    logrotate \
    cron \
    unzip \
    curl \
    wget \
    && rm -rf /var/lib/apt/lists/*

# Configure & Install PHP extensions required by Z-Push
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) \
        imap \
        pcntl \
        posix \
        sysvsem \
        sysvshm \
        bcmath \
        xml \
        zip

# Enable Apache mod_rewrite and mod_headers
RUN a2enmod rewrite headers

# Z-Push Version
ENV ZPUSH_VERSION=2.7.6

# Download & Extract Z-Push source code
RUN wget -O /tmp/z-push.tar.gz https://github.com/Z-Hub/Z-Push/archive/refs/tags/${ZPUSH_VERSION}.tar.gz \
    && tar xzf /tmp/z-push.tar.gz -C /tmp/ \
    && mkdir -p /usr/share/z-push \
    && cp -r /tmp/Z-Push-${ZPUSH_VERSION}/src/* /usr/share/z-push/ \
    && rm -rf /tmp/z-push.tar.gz /tmp/Z-Push-${ZPUSH_VERSION}

# Create state and log directories
RUN mkdir -p /var/lib/z-push /var/log/z-push \
    && chown -R www-data:www-data /var/lib/z-push /var/log/z-push /usr/share/z-push

# Copy Apache VirtualHost configuration for Z-Push
COPY apache-zpush.conf /etc/apache2/sites-available/000-default.conf

# Copy logrotate configuration
COPY z-push.logrotate /etc/logrotate.d/z-push
RUN chmod 0644 /etc/logrotate.d/z-push

# Copy Z-Push configuration templates & entrypoint
COPY config.php /usr/share/z-push/config.php
COPY imap.conf.php /usr/share/z-push/backend/imap/config.php
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Copy PHP runtime overrides (keep error output out of HTTP responses; see file for why)
COPY zpush-php.ini /usr/local/etc/php/conf.d/zz-zpush.ini

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
