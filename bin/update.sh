#!/bin/bash

set -euo pipefail

DIRECTORY=$(dirname "${0}")

curl -L# -o ${DIRECTORY}/composer https://github.com/composer/composer/releases/latest/download/composer.phar
curl -L# -o ${DIRECTORY}/phpstan https://github.com/phpstan/phpstan/releases/latest/download/phpstan.phar
curl -L# -o ${DIRECTORY}/phploc https://phar.phpunit.de/phploc.phar
curl -L# -o ${DIRECTORY}/phpunit https://phar.phpunit.de/phpunit-9.phar
curl -L# -o ${DIRECTORY}/psalm https://github.com/vimeo/psalm/releases/latest/download/psalm.phar
curl -L# -o ${DIRECTORY}/php-cs-fixer https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/latest/download/php-cs-fixer.phar

