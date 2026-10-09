# Real application source (KS - Kielitoimiston sanakirja) lives in ks-master/,
# a pristine, unmodified copy of the delivered source tree - diff it against
# the original delivery before changing anything there. Everything else in
# this file is infra: base image, PHP extensions/locale the app needs, Apache
# config (this app ships no .htaccess of its own), and the health/robots
# endpoints that aren't part of the app.
FROM php:8.3-apache

# ECS's containerPort for this service is fixed at 8080 (see infra/bin/infra.ts,
# WebEcsService) - the image's default of 80 needs to move to match.
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

# pdo_pgsql - inc/QuerySQL.php connects to the search-word index via PDO. The
# app shipped targeting MySQL (dsn "mysql:host=..."); the infra only
# provisions PostgreSQL (Aurora), so cfg/QuerySQL.php and inc/QuerySQL.php
# were deliberately changed to match (pgsql driver/DSN, real Aurora endpoint)
# - see the comments in those two files.
# gettext - inc/Locale.php's bindtextdomain()/textdomain() for the fi_FI UI
# strings. soap - inc/Exceptions.php unconditionally declares
# "class QueryXMLException extends SoapFault" - a class declaration, evaluated
# on every single request regardless of whether SOAP/XML is ever used, so
# without this extension the app fatal-errors on its very first include.
# curl/simplexml/dom/pdo (core) are already in the base image.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libxml2-dev libpq-dev \
    && docker-php-ext-install pdo_pgsql gettext soap \
    && rm -rf /var/lib/apt/lists/*

# inc/Locale.php calls setlocale(LC_MESSAGES, 'fi_FI') - without the actual
# locale generated on the OS this fails silently and gettext() falls back to
# untranslated strings. ISO-8859-1 gives the plain "fi_FI" alias setlocale()
# asks for; UTF-8 is generated too since bind_textdomain_codeset() forces
# UTF-8 output regardless of which one setlocale() actually matches.
RUN apt-get update \
    && apt-get install -y --no-install-recommends locales \
    && echo "fi_FI.UTF-8 UTF-8" >> /etc/locale.gen \
    && echo "fi_FI ISO-8859-1" >> /etc/locale.gen \
    && locale-gen \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY apache-health.conf /etc/apache2/conf-enabled/health.conf
COPY apache-ks.conf /etc/apache2/conf-enabled/ks.conf
COPY ks-master/ /var/www/html/
COPY html/ /var/www/html/

EXPOSE 8080
