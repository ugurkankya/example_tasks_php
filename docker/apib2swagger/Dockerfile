# see https://github.com/kminami/apib2swagger

FROM alpine:3.14

RUN apk upgrade --no-cache \
    && apk add --no-cache npm nodejs \
    && npm install --global apib2swagger \
    && npm cache clean --force \
    && apk del npm \
    && adduser -D -u 1000 node

USER node

ENTRYPOINT ["apib2swagger"]