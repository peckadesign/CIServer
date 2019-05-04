#!/usr/bin/env bash

wget -O- https://www.rabbitmq.com/rabbitmq-release-signing-key.asc | apt-key add -
echo 'deb https://dl.bintray.com/rabbitmq-erlang/debian stretch erlang' > /etc/apt/sources.list.d/rabbitmq.list
echo 'deb https://dl.bintray.com/rabbitmq/debian stretch main' >> /etc/apt/sources.list.d/rabbitmq.list

apt-get update

apt-get install -y \
	rabbitmq-server

rabbitmq-plugins enable rabbitmq_management
echo "[{rabbit, [{loopback_users, []}]}]." > /etc/rabbitmq/rabbitmq.config
