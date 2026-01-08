FROM alpine:3.19

RUN apk add --no-cache \
    bash \
    curl \
    ca-certificates \
    coreutils \
    tar \
    gzip \
 && update-ca-certificates

ENV TMPDIR=/tmp
RUN mkdir -p /tmp

RUN curl -fsSL https://cli-dl.scalingo.com/install.sh | bash && scalingo --version

COPY .docker/scalingo-job/sync-db.sh /sync-db.sh

RUN chmod +x /sync-db.sh

ENTRYPOINT ["/sync-db.sh"]
