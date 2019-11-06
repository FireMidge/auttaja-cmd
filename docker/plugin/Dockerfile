FROM php:7.1-alpine

RUN apk add git

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

ARG builduser

USER $builduser

WORKDIR /src

# TODO : Start PHP-FPM or whatever we're using, so that the container doesn't immediately exit