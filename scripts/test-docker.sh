#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
IMAGE="${GENELET_PHP_TEST_IMAGE:-genelet-php-test:8.3}"
MYSQL_CONTAINER="${GENELET_PHP_MYSQL_CONTAINER:-genelet-php-mysql-test}"
SOCKET_VOLUME="${GENELET_PHP_MYSQL_SOCKET_VOLUME:-genelet-php-mysql-socket}"

cleanup() {
    docker rm -f "$MYSQL_CONTAINER" >/dev/null 2>&1 || true
    docker volume rm "$SOCKET_VOLUME" >/dev/null 2>&1 || true
}
cleanup_container() {
    docker rm -f "$MYSQL_CONTAINER" >/dev/null 2>&1 || true
}
finish() {
    status=$?
    if [ "$status" -ne 0 ]; then
        docker logs "$MYSQL_CONTAINER" >&2 || true
    fi
    cleanup
    exit "$status"
}
trap finish EXIT

docker build -t "$IMAGE" -f "$ROOT/.docker/php-test/Dockerfile" "$ROOT"

cleanup_container
docker volume rm "$SOCKET_VOLUME" >/dev/null 2>&1 || true
docker volume create "$SOCKET_VOLUME" >/dev/null
docker run -d \
    --name "$MYSQL_CONTAINER" \
    -v "$SOCKET_VOLUME":/var/run/mysqld \
    -e MYSQL_ALLOW_EMPTY_PASSWORD=yes \
    -e MYSQL_DATABASE=test \
    mysql:8 >/dev/null

for _ in $(seq 1 90); do
    if docker logs "$MYSQL_CONTAINER" 2>&1 | grep -q "port: 3306"; then
        break
    fi
    sleep 1
done

docker exec "$MYSQL_CONTAINER" mysqladmin ping -uroot --silent >/dev/null

docker exec "$MYSQL_CONTAINER" mysql -uroot -e "
CREATE DATABASE IF NOT EXISTS test;
CREATE USER IF NOT EXISTS 'genelet_test'@'%' IDENTIFIED BY '';
CREATE USER IF NOT EXISTS 'genelet_test'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON test.* TO 'genelet_test'@'%';
GRANT ALL PRIVILEGES ON test.* TO 'genelet_test'@'localhost';
FLUSH PRIVILEGES;
"

docker run --rm \
    --network "container:$MYSQL_CONTAINER" \
    -v "$ROOT":/app \
    -v "$SOCKET_VOLUME":/var/run/mysqld \
    -w /app \
    "$IMAGE" \
    sh -lc 'git config --global --add safe.directory /app && composer install --no-interaction --no-progress && find src tests -name "*.php" -print0 | xargs -0 -n1 php -l && ./vendor/bin/phpunit --bootstrap vendor/autoload.php tests'
