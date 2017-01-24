#!/usr/bin/env bash

echo "deb http://packages.dotdeb.org jessie all" > /etc/apt/sources.list.d/dotdeb.list
wget https://www.dotdeb.org/dotdeb.gpg -qO- | apt-key add -


apt-get install software-properties-common
apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xcbcb082a1bb943db
add-apt-repository 'deb [arch=amd64,i386,ppc64el] http://mirror.vpsfree.cz/mariadb/repo/5.5/ubuntu trusty main'

export DEBIAN_FRONTEND=noninteractive
debconf-set-selections <<< 'mariadb-server-5.5 mysql-server/root_password password ci'
debconf-set-selections <<< 'mariadb-server-5.5 mysql-server/root_password_again password ci'

echo "deb http://www.rabbitmq.com/debian/ testing main" > /etc/apt/sources.list.d/rabbitmq.list
wget https://www.rabbitmq.com/rabbitmq-signing-key-public.asc -q -O - | apt-key add -

apt-get update

apt-get upgrade -y --force-yes
apt-get install -y --force-yes \
	git \
	htop \
	vim \
	mariadb-server \
	rabbitmq-server \
	apache2 \
	php7.0 \
	libapache2-mod-php7.0 \
	php7.0-xdebug \
	php-curl \
	php-xml \
	php-zip \
	php-mysql \
	php-mbstring \
	php7.0-bcmath

a2enmod rewrite

php -r "readfile('https://getcomposer.org/installer');" > composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

if ! [ -L "/var/www" ]; then
	rm -rf "/var/www"
	ln -fs "/vagrant" "/var/www"
fi

rm -rf /etc/apache2/sites-enabled/*
if ! [ -L "/etc/apache2/sites-available" ]; then
	if ! [ -L "/etc/apache2/sites-available/ci.conf" ]; then
		ln -s "/vagrant/vagrant/server/apache/sites-available/ci.conf" "/etc/apache2/sites-available/ci.conf"
	fi
	a2ensite -q ci.conf
fi

if ! [ -L "/etc/apache2/conf-available/ci.conf" ]; then
	rm -f "/etc/apache2/conf-available/ci.conf"
	ln -s "/vagrant/vagrant/server/apache/conf-available/ci.conf" "/etc/apache2/conf-available/ci.conf"
fi
a2enconf -q ci.conf

if ! [ -L "/etc/php/7.0/cli/conf.d/ci.ini" ]; then
	rm -f "/etc/php/7.0/cli/conf.d/ci.ini"
	ln -s "/vagrant/vagrant/server/php/cli.ini" "/etc/php/7.0/cli/conf.d/ci.ini"
fi

if ! [ -L "/etc/php/7.0/apache2/conf.d/ci.ini" ]; then
	rm -f "/etc/php/7.0/apache2/conf.d/ci.ini"
	ln -s "/vagrant/vagrant/server/php/apache2.ini" "/etc/php/7.0/apache2/conf.d/ci.ini"
fi

if [ -f "/etc/php/7.0/mods-available/xdebug.ini" ]; then
	rm -f "/etc/php/7.0/mods-available/xdebug.ini"
	ln -s "/vagrant/vagrant/server/php/xdebug.ini" "/etc/php/7.0/mods-available/xdebug.ini"
fi

chmod -R 0777 "/vagrant/temp" "/vagrant/log"


mysql --user=root --password="ci" --execute="CREATE DATABASE IF NOT EXISTS ci";
mysql --user=root --password="ci" --execute="GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'ci';"
sed -ie "s/^bind-address/#bind-address/g" "/etc/mysql/my.cnf"
service mysql restart


rabbitmq-plugins enable rabbitmq_management
echo "[{rabbit, [{loopback_users, []}]}]." > /etc/rabbitmq/rabbitmq.config
