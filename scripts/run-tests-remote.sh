#!/usr/bin/env bash
#
# Run the PHPUnit suite on Hetzner01, because this machine has no PHP.
#
# The working tree is tarred (without node_modules, vendor and .git), shipped
# to /root/patrimoine-test and run inside the prebuilt patrimoine-phpunit:8.4
# image. vendor/ stays on the server between runs; pass --composer to refresh
# it after a composer.json change.
#
# Usage:
#   scripts/run-tests-remote.sh                       # whole suite
#   scripts/run-tests-remote.sh --filter ManageNav    # anything phpunit takes
#   scripts/run-tests-remote.sh --composer            # reinstall dependencies
#
set -euo pipefail

HOST="${PATRIMOINE_TEST_HOST:-root@5.161.47.69}"
KEY="${PATRIMOINE_TEST_KEY:-$HOME/.ssh/id_ed25519_claude}"
REMOTE=/root/patrimoine-test

cd "$(dirname "$0")/.."

COMPOSER=0
ARGS=()

for arg in "$@"; do
    if [ "$arg" = "--composer" ]; then
        COMPOSER=1
    else
        ARGS+=("$arg")
    fi
done

echo "→ packing working tree"
tar --exclude=node_modules \
    --exclude=vendor \
    --exclude=.git \
    --exclude=storage/logs \
    -czf /tmp/patrimoine-src.tgz .

echo "→ shipping to $HOST"
scp -q -i "$KEY" /tmp/patrimoine-src.tgz "$HOST:/root/patrimoine-src.tgz"

# public/build ships too: the layout resolves the Vite manifest, so a missing
# build fails every page test with "Vite manifest not found".
#
# The source is replaced wholesale; vendor/ and the .env survive because they
# are never in the tarball and rsync-style deletion is not used.
ssh -i "$KEY" "$HOST" "
    set -e
    mkdir -p $REMOTE
    find $REMOTE -mindepth 1 -maxdepth 1 \
        ! -name vendor ! -name .env ! -name node_modules -exec rm -rf {} +
    tar -xzf /root/patrimoine-src.tgz -C $REMOTE
"

if [ "$COMPOSER" = "1" ]; then
    echo "→ composer install"
    ssh -i "$KEY" "$HOST" "
        docker run --rm -v $REMOTE:/app -w /app patrimoine-phpunit:8.4 \
            composer install --no-interaction --no-progress
    "
fi

echo "→ phpunit"
# -d memory_limit=-1 is mandatory: dompdf blows the 128M default and PHPUnit
# dies mid-run with 'Premature end of PHP process'.
ssh -i "$KEY" "$HOST" "
    docker run --rm -v $REMOTE:/app -w /app patrimoine-phpunit:8.4 \
        sh -lc 'php -d memory_limit=-1 vendor/bin/phpunit ${ARGS[*]:-}'
"
