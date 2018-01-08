#!/bin/bash

ssh -t ubuntu@paulsf "mkdir -p /home/ubuntu/css.paulsf.com"

rsync -rv --exclude '.git' --exclude 'deploy.sh' --exclude '.DS_store' --exclude 'apache/' ./ paulsf:/home/ubuntu/css.paulsf.com/

ssh -t ubuntu@paulsf "sudo rm -Rf /var/www/css.paulsf.com/* && sudo mv /home/ubuntu/css.paulsf.com/* /var/www/css.paulsf.com/ && sudo chmod 755 -R /var/www/css.paulsf.com && sudo chown -R www-data:ubuntu /var/www/css.paulsf.com"
