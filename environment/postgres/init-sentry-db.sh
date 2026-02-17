#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DATABASE" <<-EOSQL
    SELECT 'CREATE DATABASE sentry'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'sentry')\gexec
    
    GRANT ALL PRIVILEGES ON DATABASE sentry TO $POSTGRES_USER;
EOSQL

