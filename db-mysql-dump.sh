#!/bin/bash

# Ensure DATABASE_URL is set
if [ -z "$DATABASE_URL" ]; then
    echo "Error: DATABASE_URL environment variable is not set."
    exit 1
fi

# Extract MySQL credentials from DATABASE_URL
DB_PROTOCOL=$(echo $DATABASE_URL | awk -F '://' '{print $1}')
DB_USER=$(echo $DATABASE_URL | awk -F '[:@]' '{print $2}')
DB_PASSWORD=$(echo $DATABASE_URL | awk -F '[:@]' '{print $3}')
DB_HOST=$(echo $DATABASE_URL | awk -F '[@:]' '{print $4}')
DB_PORT=$(echo $DATABASE_URL | awk -F '[:/]' '{print $5}')
DB_NAME=$(echo $DATABASE_URL | awk -F '/' '{print $4}')

# Ensure the protocol is MySQL
if [ "$DB_PROTOCOL" != "mysql" ]; then
    echo "Error: DATABASE_URL does not specify a MySQL database."
    exit 1
fi

# Create the dump file
DUMP_FILE="mysql_dump_$(date +%Y%m%d%H%M%S).sql"

echo "Creating dump for database '$DB_NAME' on host '$DB_HOST:$DB_PORT'..."
mysqldump -u "$DB_USER" -p"$DB_PASSWORD" -h "$DB_HOST" -P "$DB_PORT" "$DB_NAME" > "$DUMP_FILE"

# Check if the dump was successful
if [ $? -eq 0 ]; then
    echo "Database dump created successfully: $DUMP_FILE"
else
    echo "Error: Failed to create database dump."
    exit 1
fi
