FROM ubuntu:20.04

ENV TZ=Europe/London
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Basic actions
RUN apt update \
    && apt full-upgrade -y \
    && apt install -y \
        curl \
        git \
        supervisor \
        gnupg \
        zip \
        unzip

# Install nginx-unit list
RUN curl -sL https://nginx.org/keys/nginx_signing.key | apt-key add - \
    && echo "deb https://packages.nginx.org/unit/ubuntu/ focal unit" >/etc/apt/sources.list.d/unit.list \
    && echo "deb-src https://packages.nginx.org/unit/ubuntu/ focal unit" >>/etc/apt/sources.list.d/unit.list

# Software installation
RUN apt update \
    && apt install -y \
        unit-php \
        php-sockets \
        php-xml \
        php-mbstring \
        php-zip \
        php-curl \
    && apt clean

# Получить последнюю версию Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Добавьте wait к изображению
ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.8.0/wait /wait
RUN chmod +x /wait

WORKDIR /app
COPY ./ /app
RUN composer install --no-interaction

# Arguments defined in docker-compose.yml
ARG user=dev
ARG uid=1000

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user && \
    chown -R $user:$user /home/$user && \
    chown -R $user:$user /app

RUN echo '\
{\n\
    "applications": {\n\
        "web-service": {\n\
            "type": "php",\n\
            "processes": {\n\
                "max": 50,\n\
                "spare": 50\n\
            },\n\
            "root": "/app/public",\n\
            "user": "dev",\n\
            "index": "index.php",\n\
            "script": "index.php",\n\
            "options": {\n\
                "file": "/etc/php/7.4/cli/php.ini"\n\
            }\n\
        }\n\
    },\n\
    "listeners": {\n\
        "*:8080": {\n\
            "pass": "applications/web-service"\n\
        }\n\
    }\n\
}\n\
' > /var/unit.json

RUN echo '\
[program:unit]\n\
command=/usr/sbin/unitd --no-daemon\n\
numprocs=1\n\
autostart=true\n\
autorestart=true\n\
killasgroup=true\n\
stopasgroup=true\n\
priority=10\n\
\n\
[program:unit_config]\n\
command=curl -X PUT -d @/var/unit.json --unix-socket /var/run/control.unit.sock http://localhost/config\n\
autostart=true\n\
autorestart=false\n\
priority=100\n\
startsecs=0\n\
' > /etc/supervisor/conf.d/unit.conf

CMD /wait && /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
