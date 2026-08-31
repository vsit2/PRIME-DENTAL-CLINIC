# ==============================================================================
# PRIME DENTAL CLINIC - Dockerfile for Render / Cloud Deployment
# Bundles Nginx & PHP 8.2-FPM with PDO MySQL Extension
# ==============================================================================

FROM php:8.2-fpm-alpine

# Install Nginx, Supervisor, and required dependencies
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql mysqli

# Configure Nginx
COPY <<EOF /etc/nginx/nginx.conf
user www-data;
worker_processes auto;
pid /run/nginx.pid;
error_log /var/log/nginx/error.log warn;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    sendfile on;
    keepalive_timeout 65;
    client_max_body_size 64M;

    server {
        listen 80;
        listen [::]:80;
        server_name _;
        root /var/www/html;
        index index.php index.html;

        location / {
            try_files \$uri \$uri/ /index.php?\$query_string;
        }

        location ~ \.php$ {
            try_files \$uri =404;
            fastcgi_split_path_info ^(.+\.php)(/.+)$;
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
            fastcgi_param PATH_INFO \$fastcgi_path_info;
        }

        location ~ /\.ht {
            deny all;
        }

        location ~ /\.git {
            deny all;
        }
    }
}
EOF

# Configure Supervisor to run both PHP-FPM and Nginx
COPY <<EOF /etc/supervisor/conf.d/supervisord.conf
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisord.log
pidfile=/run/supervisord.pid

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
priority=5
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=/bin/bash -c "envsubst '\$PORT' < /etc/nginx/nginx.conf > /etc/nginx/nginx.conf.tmp && mv /etc/nginx/nginx.conf.tmp /etc/nginx/nginx.conf && exec nginx -g 'daemon off;'"
autostart=true
autorestart=true
priority=10
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

# Create startup entrypoint script for Render PORT substitution & permissions
COPY <<EOF /entrypoint.sh
#!/bin/bash
set -e

# Support Render custom PORT environment variable (default to 80 if not set)
PORT=\${PORT:-80}
sed -i "s/listen 80;/listen \${PORT};/g" /etc/nginx/nginx.conf
sed -i "s/listen \[::\]:80;/listen \[::\]:\${PORT};/g" /etc/nginx/nginx.conf

# Ensure permissions
chown -R www-data:www-data /var/www/html /var/log/nginx /run

# Execute supervisord
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
EOF

RUN chmod +x /entrypoint.sh

# Set working directory & copy application code
WORKDIR /var/www/html
COPY . /var/www/html

# Fix ownership
RUN chown -R www-data:www-data /var/www/html

# Render uses dynamic port (usually 80 or 10000)
EXPOSE 80 10000

# Start services via entrypoint
ENTRYPOINT ["/entrypoint.sh"]
