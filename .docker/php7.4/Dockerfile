FROM php:7.4.10-apache

RUN apt-get update -y \
	&& apt-get install -y \
	git \
	zlib1g-dev \
	zip \
	unzip \
	libzip-dev \
	&& apt-get clean -y

RUN docker-php-ext-install \
	pdo \
	pdo_mysql \
	opcache \
	bcmath \
	zip
