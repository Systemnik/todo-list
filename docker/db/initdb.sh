#!/bin/bash

psql -U"$POSTGRES_USER" -d"$POSTGRES_DB" < /app/tables.sql
