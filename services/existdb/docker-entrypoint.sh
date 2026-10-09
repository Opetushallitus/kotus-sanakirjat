#!/usr/bin/env bash
set -euo pipefail

EXIST_HOME="${EXIST_HOME:-/opt/exist-db}"
EXIST_DATA_DIR="${EXIST_DATA_DIR:-/exist/data}"
EXIST_PORT="${EXIST_PORT:-8086}"
INIT_MARKER="${EXIST_DATA_DIR}/.container-admin-password-set"

mkdir -p "$EXIST_DATA_DIR"

EXIST_PID=""

term_handler() {
  if [ -n "$EXIST_PID" ] && kill -0 "$EXIST_PID" 2>/dev/null; then
    kill -TERM "$EXIST_PID"
    wait "$EXIST_PID"
  fi
  exit 0
}
trap term_handler TERM INT

"$@" &
EXIST_PID=$!

# First boot only (empty/fresh data dir - eXist's own default admin account has
# no password until this runs): wait for eXist to come up, then set the real
# admin password via its security manager XQuery function. Safe to skip on
# every later restart - the marker lives on the persistent data volume.
if [ ! -f "$INIT_MARKER" ]; then
  echo "entrypoint: first boot, waiting for eXist-db to accept connections on :${EXIST_PORT}..."
  ready=false
  for _ in $(seq 1 90); do
    if curl -fsS "http://localhost:${EXIST_PORT}/exist/status" >/dev/null 2>&1; then
      ready=true
      break
    fi
    sleep 2
  done

  if [ "$ready" != "true" ]; then
    echo "entrypoint: eXist-db did not become ready in time, skipping admin password setup this boot" >&2
  elif [ -z "${EXISTDB_ADMIN_PASS:-}" ]; then
    echo "entrypoint: EXISTDB_ADMIN_PASS not set, leaving the default admin account as-is" >&2
  else
    echo "entrypoint: setting the admin password"
    "$EXIST_HOME/bin/client.sh" -q -u admin -P "" \
      -x "sm:passwd('admin', '${EXISTDB_ADMIN_PASS}')"
    touch "$INIT_MARKER"
  fi
fi

wait "$EXIST_PID"
