#!/usr/bin/env sh

cd $(dirname $0)

if [ "$1" = 'nginx-php-fpm' ]; then

    supervisord --nodaemon --configuration="/docker/configuration/supervisord.conf"

fi