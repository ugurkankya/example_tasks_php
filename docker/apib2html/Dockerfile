# see https://github.com/danielgtaylor/aglio

FROM alpine:3.14

RUN apk upgrade --no-cache \
    && apk add --no-cache npm nodejs \
    && npm install --global aglio \
    && npm cache clean --force \
    && apk del npm \
    && adduser -D -u 1000 node

USER node

ENTRYPOINT ["aglio"]