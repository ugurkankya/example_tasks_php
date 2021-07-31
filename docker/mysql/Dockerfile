FROM mysql:8.0

RUN apt-get -y update \
    && apt-get upgrade -y \
    && apt-get clean \
    && rm -rf /tmp/* /var/lib/apt/lists/* /var/cache/apt/archives/*

EXPOSE 3306

CMD ["mysqld"]
