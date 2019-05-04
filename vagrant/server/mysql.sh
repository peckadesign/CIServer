#!/usr/bin/env bash

PASSWORD="root"

echo "mariadb-server-10.1 mysql-server/root_password password $PASSWORD" | debconf-set-selections
echo "mariadb-server-10.1 mysql-server/root_password_again password $PASSWORD" | debconf-set-selections

apt-get install -y software-properties-common dirmngr
apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xF1656F24C74CD1D8
add-apt-repository 'deb [arch=amd64,i386,ppc64el] http://mirror.vpsfree.cz/mariadb/repo/10.1/debian stretch main'
apt-get update

apt-get install -y mariadb-server

mysql --user=root --password="$PASSWORD" --execute="CREATE DATABASE vagrant";
mysql --user=root --password="$PASSWORD" --execute="GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '$PASSWORD';"
sed -ie "s/^bind-address/#bind-address/g" "/etc/mysql/mariadb.conf.d/50-server.cnf"

service mysql restart
