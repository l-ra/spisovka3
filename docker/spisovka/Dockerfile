FROM php:7.1.9-apache

#defaults
ENV MYSQL_HOST=mysql MYSQL_DB=spisovka MYSQL_USER=spisovka MYSQL_PASSWORD=spisovka

RUN apt-get update \
    && apt-get install -y git 

RUN mkdir /repo \
    && cd /repo \
    && git clone https://github.com/l-ra/spisovka3.git \
    && cd spisovka3 \
    && git checkout l-ra-docker

RUN    cd /repo/spisovka3 \
    && cp -r app/ client/ help/ libs/ log/ public/ vendor/ /var/www/html \
    && cp index.ph /var/www/html/index.php \
    && chown -R www-data:www-data /var/www/html/* \
    && chmod g+w /var/www/html/client/configs \
                 /var/www/html/client/files/dokumenty \
                 /var/www/html/client/files/epodatelna \
                 /var/www/html/client/sessions \
                 /var/www/html/client/temp \
                 /var/www/html/log

ADD start-spisovka.sh /start-spisovka.sh

CMD ["/start-spisovka.sh"]   

