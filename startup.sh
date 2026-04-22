#!/bin/bash

# Create nginx configuration for routing all requests to index.php
mkdir -p /etc/nginx/sites-available
mkdir -p /etc/nginx/sites-enabled

cat > /etc/nginx/sites-available/default << 'EOF'
server {
    listen 8080 default_server;
    listen [::]:8080 default_server;
    
    server_name _;
    root /home/site/wwwroot;
    
    index index.php;
    
    # Gzip settings
    gzip on;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript;
    
    # Route all requests to index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Handle PHP files
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_script_name;
    }
    
    # Deny access to hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
}
EOF

ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default 2>/dev/null || true

# Start PHP-FPM
php-fpm --daemonize --fpm-config /etc/php/8.2/fpm/php-fpm.conf

# Start nginx in foreground
exec nginx -g "daemon off;"
