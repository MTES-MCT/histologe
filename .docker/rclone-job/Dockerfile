FROM alpine:3.19

RUN apk add --no-cache \
    ca-certificates \
    bash \
    curl \
 && update-ca-certificates

COPY --from=rclone/rclone:latest /usr/local/bin/rclone /usr/bin/rclone

COPY .docker/rclone-job/sync-s3.sh /sync-s3.sh
RUN chmod +x /sync-s3.sh

ENTRYPOINT ["/sync-s3.sh"]
