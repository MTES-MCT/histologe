#!/bin/sh

set -eu

IMAGE_VERSION="1.0.1"

log() {
  level="$1"
  shift
  echo "$(date -u '+%Y-%m-%dT%H:%M:%SZ') [$level] $*"
}

log INFO "Image version: ${IMAGE_VERSION}"

: "${SCALINGO_API_TOKEN:?Missing SCALINGO_API_TOKEN}"
SCALINGO_APP="${METABASE_SYNC_SCALINGO_APP:-histologe-preprod}"

command -v scalingo >/dev/null 2>&1 || { log ERROR "scalingo CLI not found"; exit 127; }

log INFO "Starting Scalingo job"
log INFO "App: ${SCALINGO_APP}"
log INFO "Remote command: sh /app/scripts/sync-db.sh"

log INFO "Version installée : $(scalingo --version)"

# LOCAL MODE
if [ "${METABASE_SYNC_LOCAL_MODE:-0}" = "1" ]; then
  log WARN "Running in LOCAL MODE"
  log INFO "Executing safe Scalingo command: scalingo apps"
  exec scalingo apps
fi

# PROD MODE
log INFO "Executing remote sync-db script on Scalingo"
exec scalingo -a "$SCALINGO_APP" run -d -- sh /app/scripts/sync-db.sh
