#!/usr/bin/env bash

apt-get update
apt-get install php7.2-fpm php7.2-xml -y
apt-get autoremove --purge -y
apt-get autoclean

touch /var/run/php-uds.sock && sudo chown www-data:www-data /var/run/php-uds.sock

ln -sf /vagrant/env/php-fpm/7.2/network-socket.pool.conf /etc/php/7.2/fpm/pool.d/network-pool.conf
ln -sf /vagrant/env/php-fpm/7.2/unix-domain-socket.pool.conf /etc/php/7.2/fpm/pool.d/unix-domain-socket-pool.conf

cd /vagrant

echo -e "\e[0m--"
composer self-update
chmod -R 0777 /home/vagrant/.composer
chmod -R 0777 /tmp

service php7.2-fpm restart
