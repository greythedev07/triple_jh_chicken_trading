#!/bin/bash
set -e

# Create the main uploads directory if it doesn't exist
mkdir -p /app/uploads

# Set ownership to www-data (or the web server user)
chown -R www-data:www-data /app/uploads

# Ensure the web server user can write to it
chmod -R 775 /app/uploads

# Create required subdirectories
mkdir -p /app/uploads/{items,deliveries,pickups,qr_codes,gcash_screenshots}

# Set correct permissions for subdirectories
chmod -R 775 /app/uploads/*
