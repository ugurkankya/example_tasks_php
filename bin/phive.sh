#!/bin/bash

set -euo pipefail

DIRECTORY=$(dirname "${0}")

php ${DIRECTORY}/phive.phar selfupdate
php ${DIRECTORY}/phive.phar update-repository-list

php ${DIRECTORY}/phive.phar install --temporary -c --force-accept-unsigned -t ${DIRECTORY}/ \
    composer/composer \
    FriendsOfPHP/PHP-CS-Fixer \
    phploc \
    phpstan/phpstan \
    phpunit \
    vimeo/psalm
     