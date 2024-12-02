#!/bin/bash

# Ensure DATABASE_URL is set
if [ -z "$DATABASE_URL" ]; then
    echo "Error: DATABASE_URL environment variable is not set."
    exit 1
fi

# Extract MySQL credentials from DATABASE_URL
DB_PROTOCOL=$(echo "$DATABASE_URL" | sed -n 's#^\(.*\)://.*#\1#p')
DB_USER=$(echo "$DATABASE_URL" | sed -n 's#^[^:]*://\([^:]*\):.*@\([^:/]*\).*#\1#p')
DB_PASSWORD=$(echo "$DATABASE_URL" | sed -n 's#^[^:]*://[^:]*:\([^@]*\)@.*#\1#p')
DB_HOST=$(echo "$DATABASE_URL" | sed -n 's#^[^:]*://[^@]*@\(.*\):[0-9]*/.*#\1#p')
DB_PORT=$(echo "$DATABASE_URL" | sed -n 's#.*://.*@.*:\([0-9]*\)/.*#\1#p')
DB_NAME=$(echo "$DATABASE_URL" | sed -n 's#.*/\([^/?]*\).*#\1#p')

# Ensure the protocol is MySQL
if [ "$DB_PROTOCOL" != "mysql" ]; then
    echo "Error: DATABASE_URL does not specify a MySQL database."
    exit 1
fi

# Create the dump file
DUMP_FILE="mysql_dump_oreplay_$(date +%Y%m%d%H%M%S).sql"

echo "Creating dump for database '$DB_NAME' on host '$DB_HOST:$DB_PORT' using user '$DB_USER' ..."
mysqldump -u "$DB_USER" -p"$DB_PASSWORD" -h "$DB_HOST" -P "$DB_PORT" "$DB_NAME" > "$DUMP_FILE"

# Check if the dump was successful
if [ $? -eq 0 ]; then
    echo "Database dump created successfully: $DUMP_FILE"
    echo "Please, immediately download the file and remove it from the server"
    echo "kubectl cp oreplay/cakeapi-nginx-deployment-7cb85d7f75-rg5t8:$DUMP_FILE ./$DUMP_FILE"
else
    echo "Error: Failed to create database dump."
    exit 1
fi
