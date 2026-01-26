#!/bin/sh
set -eu

IMAGE_VERSION="1.0.2"

log() {
  level="$1"
  shift
  echo "$(date -u '+%Y-%m-%dT%H:%M:%SZ') [$level] $*"
}

send_report() {
  status="$1"    # success | fail | terminated
  rc="${2:-}"
  stats_line="${3:-}"
  message="${4:-}"

  if [ -z "${SIGNAL_LOGEMENT_PROD_URL:-}" ] || [ -z "${SEND_ERROR_EMAIL_TOKEN:-}" ]; then
    log WARN "Report skipped (missing SIGNAL_LOGEMENT_PROD_URL or SEND_ERROR_EMAIL_TOKEN)"
    return 0
  fi

  TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
  HOSTNAME=$(hostname)
  TITLE="Synchronisation buckets S3 (${status})"

  curl -fsS -X POST "${SIGNAL_LOGEMENT_PROD_URL}/webhook/cron-report-mail" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer ${SEND_ERROR_EMAIL_TOKEN}" \
    -d "{\"title\":\"$TITLE\",\"timestamp\":\"$TIMESTAMP\",\"host\":\"$HOSTNAME\",\"message\":\"$message\",\"exit_code\":$rc,\"stats\":\"$stats_line\"}" \
    || log WARN "Report call failed (curl exit=$?)"
}

extract_last_stats_line() {
  grep -E ' INFO  :' \
    | tail -n 1 \
    | sed -E 's/^.* INFO  :[[:space:]]*//'
}

log INFO "Image version: ${IMAGE_VERSION}"
log INFO "Starting rclone S3 sync job"

: "${RCLONE_SRC:?Missing RCLONE_SRC (ex: ovh_s3:bucket)}"
: "${RCLONE_DST:?Missing RCLONE_DST (ex: scaleway_s3:bucket)}"

: "${RCLONE_CONFIG_OVH_S3_ACCESS_KEY_ID:?Missing OVH access key}"
: "${RCLONE_CONFIG_OVH_S3_SECRET_ACCESS_KEY:?Missing OVH secret key}"
: "${RCLONE_CONFIG_OVH_S3_ENDPOINT:?Missing OVH endpoint}"

: "${RCLONE_CONFIG_SCALEWAY_S3_ACCESS_KEY_ID:?Missing Scaleway access key}"
: "${RCLONE_CONFIG_SCALEWAY_S3_SECRET_ACCESS_KEY:?Missing Scaleway secret key}"
: "${RCLONE_CONFIG_SCALEWAY_S3_ENDPOINT:?Missing Scaleway endpoint}"
: "${RCLONE_MAX_DURATION:?Missing Rclone max duration}"

RCLONE_MAX_DURATION="${RCLONE_MAX_DURATION:-12h}"

export RCLONE_CONFIG_OVH_S3_TYPE="s3"
export RCLONE_CONFIG_OVH_S3_PROVIDER="Other"
export RCLONE_CONFIG_OVH_S3_REGION="gra"
export RCLONE_CONFIG_OVH_S3_LOCATION_CONSTRAINT="gra"
export RCLONE_CONFIG_OVH_S3_ENV_AUTH="false"

export RCLONE_CONFIG_SCALEWAY_S3_TYPE="s3"
export RCLONE_CONFIG_SCALEWAY_S3_PROVIDER="Other"
export RCLONE_CONFIG_SCALEWAY_S3_REGION="fr-par"
export RCLONE_CONFIG_SCALEWAY_S3_ENV_AUTH="false"

log INFO "Source: ${RCLONE_SRC}"
log INFO "Destination: ${RCLONE_DST}"
log INFO "Max duration: ${RCLONE_MAX_DURATION}"

PREFIX_YYYY_MM="$(date -u '+%Y/%m')"
SRC_WITH_PREFIX="${RCLONE_SRC%/}/${PREFIX_YYYY_MM}/"
DST_WITH_PREFIX="${RCLONE_DST%/}/${PREFIX_YYYY_MM}/"

log INFO "Monthly prefix (UTC): ${PREFIX_YYYY_MM}"
log INFO "Source (monthly): ${SRC_WITH_PREFIX}"
log INFO "Destination (monthly): ${DST_WITH_PREFIX}"


# Notifie si le script est interrompu (Ctrl+C ou Ctrl+Z, ... ou arrêt Docker)
on_term() {
  # shellcheck disable=SC2317
  log WARN "Received termination signal"
  # shellcheck disable=SC2317
  send_report "terminated" 143 "" "Job interrompu par signal (TERM/INT)."
  # shellcheck disable=SC2317
  exit 143
}
trap on_term INT TERM

set +e
RCLONE_OUTPUT="$(
  rclone sync "${SRC_WITH_PREFIX}" "${DST_WITH_PREFIX}" \
    --stats="1m" \
    --stats-one-line \
    --retries 10 \
    --retries-sleep 10s \
    --low-level-retries 20 \
    --log-level INFO \
    --max-duration "${RCLONE_MAX_DURATION}" 2>&1
)"
rc=$?
set -e

LAST_STATS_LINE="$(printf "%s\n" "$RCLONE_OUTPUT" | extract_last_stats_line)"
[ -n "${LAST_STATS_LINE:-}" ] || LAST_STATS_LINE="(stats line not found)"

log INFO "Final rclone stats: ${LAST_STATS_LINE}"

case "$rc" in
  0)
    log INFO "rclone sync finished successfully"
    send_report "success" "$rc" "$LAST_STATS_LINE" "Synchronisation des buckets S3 terminée avec succès."
    ;;
  10)
    log WARN "rclone sync stopped due to max-duration (partial sync)"
    send_report "partial" "$rc" "$LAST_STATS_LINE" "Synchronisation S3 partielle (max-duration atteinte, la suite au prochain cycle)."
    rc=0
    ;;
  *)
    log ERROR "rclone sync failed with exit code: $rc"
    send_report "fail" "$rc" "$LAST_STATS_LINE" "Échec de la synchronisation des buckets S3 (Code erreur : $rc)."
    ;;
esac

exit "$rc"
