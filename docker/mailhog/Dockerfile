FROM alpine:3.14

RUN apk upgrade --no-cache \
  && wget -qO /usr/local/bin/mailhog https://github.com/mailhog/MailHog/releases/latest/download/MailHog_linux_amd64 \
  && chmod +x /usr/local/bin/mailhog \
  && adduser -D -u 1000 mailhog

USER mailhog

EXPOSE 1025 8025

CMD ["mailhog"]
