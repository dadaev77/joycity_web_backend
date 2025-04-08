FROM yiisoftware/yii2-php:8.1-apache

RUN rm -rf /etc/localtime
RUN ln -s /usr/share/zoneinfo/UTC /etc/localtime

RUN apt update
RUN apt install -y git cron nano vim htop openssh-client

RUN useradd -m -s /bin/bash server
RUN echo 'server:password' | chpasswd

RUN echo 'export APACHE_RUN_USER=server' >> /etc/apache2/envvars
RUN echo 'export APACHE_RUN_GROUP=server' >> /etc/apache2/envvars

COPY /srv/docker/joycity/joy_server/entrypoint.sh /usr/local/bin/backend-entrypoint
RUN chmod +x /usr/local/bin/backend-entrypoint

COPY /srv/docker/joycity/joy_server/.ssh /home/server/.ssh
RUN chmod 600 /home/server/.ssh/id_ed25519

RUN mkdir -p /app
RUN chown server:server /app
RUN chown server:server /home/server/.ssh -R

USER server

# COPY /srv/docker/joycity/joy_server/apache2/default.conf /etc/apache2/sites-enabled/000-default.conf
# COPY /srv/docker/joycity/joy_server/apache2/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf

WORKDIR /app
COPY . .
RUN composer update
RUN composer install

USER root

ENTRYPOINT ["/bin/sh", "-c", "backend-entrypoint && /bin/bash docker-php-entrypoint && /bin/bash apache2-foreground"]
