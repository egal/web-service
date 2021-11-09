FROM php:8.0.10-cli-buster

# Arguments defined in docker-compose.yml
ARG user='nonroot'
ARG uid=1000

# Установить системные зависимости
RUN apt-get update \
    && apt-get install -y \
        libpq-dev \
        libssl-dev \
        libcurl4-openssl-dev \
        curl \
        zip \
        unzip \
        procps \
        openssl \
        wget \
        git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Установить расширения PHP
RUN docker-php-ext-install \
    sockets \
    pcntl

# Install Swoole
RUN cd /tmp && git clone https://github.com/swoole/swoole-src.git \
    && cd swoole-src \
    && git checkout v4.6.7 \
    && phpize \
    && ./configure --enable-openssl --enable-swoole-curl --enable-http2 --enable-mysqlnd \
    && make && make install \
    && touch /usr/local/etc/php/conf.d/swoole.ini \
    && echo 'extension=swoole.so' > /usr/local/etc/php/conf.d/swoole.ini \
    && wget -O /usr/local/bin/dumb-init https://github.com/Yelp/dumb-init/releases/download/v1.2.2/dumb-init_1.2.2_amd64 \
    && chmod +x /usr/local/bin/dumb-init \
    && apt-get autoremove -y && rm -rf /var/lib/apt/lists/*

# Получить последнюю версию Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Добавьте wait к изображению
ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.8.0/wait /wait
RUN chmod +x /wait

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

RUN mkdir /app \
    && chmod -R 755 /app \
    && chown -R $user:$user /app

USER $user

WORKDIR /app

COPY --chown=$user composer.json .
COPY --chown=$user composer.lock .
RUN composer install --no-interaction --no-progress --no-autoloader --dev
COPY --chown=$user . .
RUN composer dump-autoload
# TODO: Избавиться от chmod
RUN chmod -R 755 /app

CMD /wait && ./server
