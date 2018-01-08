#!/bin/sh

chown -Rf www-data:ubuntu /var/www/css.paulsf.com/storage
chmod -Rf 775 /var/www/css.paulsf.com/storage
chown -Rf www-data:ubuntu /var/www/css.paulsf.com/bootstrap/cache
chmod -Rf 775 /var/www/css.paulsf.com/bootstrap/cache

