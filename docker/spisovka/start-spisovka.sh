#!/bin/sh

cat >/var/www/html/client/configs/database.neon <<__EOF__
parameters:

    database:
        driver   = mysqli
        host     = ${MYSQL_HOST}
        username = ${MYSQL_USER}
        password = ${MYSQL_PASSWORD}
        database = ${MYSQL_DB}
        charset  = utf8
        profiler = false
        cache    = true
__EOF__

exec apache2-foreground "$@"
