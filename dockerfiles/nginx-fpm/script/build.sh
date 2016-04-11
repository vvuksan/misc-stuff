#!/usr/bin/env sh

cd $(dirname $0)

apt-get update
apt-get install -y \
    nginx \
    php5-fpm \
    supervisor

rm -rf /var/cache/apt/*

rm -rf /var/www/*

# Update php configuration.

sed -i \
    -e 's/group =.*/group = nginx/' \
    -e 's/user =.*/user = nginx/' \
    -e 's/listen\.owner.*/listen\.owner = nginx/' \
    -e 's/listen\.group.*/listen\.group = nginx/' \
    -e 's/error_log =.*/error_log = \/dev\/stdout/' \
    /etc/php5/fpm/php-fpm.conf

sed -i "s/^listen = .*/listen = 127.0.0.1:9000/g" /etc/php5/fpm/pool.d/www.conf    
    
sed -i \
    -e '/open_basedir =/s/^/\;/' \
    /etc/php5/fpm/php.ini
