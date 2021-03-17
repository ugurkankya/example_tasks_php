PHP Example Tasks REST API
------------------------------------

[![Actions Build Status](https://github.com/thomasbley/example_tasks_php/workflows/build/badge.svg?branch=master)](https://github.com/thomasbley/example_tasks_php/actions)

[![Travis Build Status](https://travis-ci.com/thomasbley/example_tasks_php.svg?branch=master)](https://travis-ci.com/thomasbley/example_tasks_php)

As a registered user, I want to see a list of open tasks for my day, so that I can do them one by one and get notified
on completion.

#### Setup

    # setup composer
    mkdir -m 0777 tasks/src/vendor
    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm composer_dev

    # build code-server
    docker-compose build --build-arg UID=$(id -u) code-server

    # start containers
    docker-compose up
    docker-compose up -d

    # setup database
    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm shell update_database.php

    # generate bearer token for customer id "42" with email "foo.bar@example.com"
    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm shell generate_token.php \
        42 foo.bar@example.com

    # access/error logs
    docker-compose logs -f

    # start php container shell
    docker-compose exec php sh
    docker-compose exec -u $(id -u) php sh

    # start mysql client
    docker-compose exec mysql mysql -u root -proot tasks

    # show mysql query log
    docker-compose exec mysql sh -c "tail -f /tmp/mysql.log"

    # remove containers/images/volumes
    docker-compose down
    docker images purge
    docker volume prune
    docker container prune
    docker system prune -a

#### Static code analyzers

    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm psalm
    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm psalm_taint
    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm phpcsfixer
    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm phploc

#### Tests

    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm phpunit

#### Monitoring

    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm fpm_status

#### Convert docs/api.md to docs/swaggerui/api_spec.json

    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm apib2swagger

#### URLs

    http://127.0.0.1:8000/?folder=/var/www (VS-Code IDE, hit Crtl+Shift+t to run tests, takes a few seconds to load for first start)

    http://127.0.0.1:8080/v1/tasks (API endpoint)

    http://127.0.0.1:8080/docs/ (SwaggerUI)
    http://127.0.0.1:8080/coverage/ (code coverage)
    http://127.0.0.1:8025/ (MailHog, catches all outgoing emails)

#### Command line tests

    docker-compose -f docker-compose.yml -f docker-compose-tasks.yml run -u $(id -u) --rm shell generate_token.php \
        42 foo.bar@example.com

    export TOKEN=...
    export BASE=http://127.0.0.1:8080

    curl -i -X POST -d '{"title":"test","duedate":"2020-05-22"}' -H "Authorization: ${TOKEN}" "${BASE}/v1/tasks"
    curl -i -X GET -H "Authorization: ${TOKEN}" "${BASE}/v1/tasks"
    curl -i -X PUT -d '{"title":"test","duedate":"2020-05-22","completed":true}' -H "Authorization: ${TOKEN}" \
        "${BASE}/v1/tasks/1"
    curl -i -X GET -H "Authorization: ${TOKEN}" "${BASE}/v1/tasks?completed=1"
    curl -i -X GET -H "Authorization: ${TOKEN}" "${BASE}/v1/tasks/1"
    curl -i -X DELETE -H "Authorization: ${TOKEN}" "${BASE}/v1/tasks/1"

Design principes

    no full-stack framework (frameworkless), best performance, less complexity, more flexibility
    no learning of frameworks, no upgrading of frameworks, no dependancy on frameworks, you build it, you own it
    no magic functions, no reflection, no annotations, no yaml configs, enable auto-complete for _everything_ in the IDE
    minimum memory footprint, minimum amount of code
    use composer for auto-loading and libraries
    use plain old (typed) php objects for models, less memory usage, better performance
    use phar files for tools
    skip static variables and methods, better testability
    skip method parameter defaults, less complexity
    superglobals are only used in bootstrap (index.php)
    use minimized alpine containers whenever possible
    test code coverage >99%
    SOLID, DRY, KISS, you build it you own it
