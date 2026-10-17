#!/bin/sh
set -eu

attempt=0
until php -r '
$db = @new mysqli(getenv("MYSQL_HOST"), getenv("MYSQL_USER"), getenv("MYSQL_PASSWORD"), getenv("MYSQL_DB"), (int)(getenv("MYSQL_PORT") ?: 3306));
exit($db->connect_errno ? 1 : 0);
'; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "Database did not become ready in time" >&2
        exit 1
    fi
    sleep 2
done

php /var/www/html/TTTT/bin/migrate.php
exec "$@"

