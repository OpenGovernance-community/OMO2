#!/bin/bash

set -e

: "${DB_NAME:?Etherpad DB_NAME is required}"
: "${DB_USER:?Etherpad DB_USER is required}"
: "${DB_PASS:?Etherpad DB_PASS is required}"

if [[ ! "$DB_NAME" =~ ^[A-Za-z0-9_]+$ || ! "$DB_USER" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo 'Invalid Etherpad database name or user' >&2
    exit 1
fi

password_hex=$(printf '%s' "$DB_PASS" | od -An -v -tx1 | tr -d ' \n')

if declare -F docker_process_sql >/dev/null; then
    sql_client=(docker_process_sql)
else
    sql_client=(mariadb --protocol=socket -uroot "-p${MARIADB_ROOT_PASSWORD:?MariaDB root password is required}")
fi

password_hash=$("${sql_client[@]}" --skip-column-names --batch \
    -e "SELECT PASSWORD(CONVERT(UNHEX('$password_hex') USING utf8mb4))" 2>/dev/null) || {
    echo 'Could not derive Etherpad password hash' >&2
    exit 1
}
if [[ ! "$password_hash" =~ ^\*[0-9A-F]{40}$ ]]; then
    echo 'Invalid Etherpad password hash' >&2
    exit 1
fi

"${sql_client[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'%';
SET PASSWORD FOR '$DB_USER'@'%' = '$password_hash';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'%';
SQL
