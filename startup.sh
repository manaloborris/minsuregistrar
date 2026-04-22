#!/bin/bash

# Set working directory
cd /home/site/wwwroot || exit 1

# Make script executable
chmod +x /home/site/wwwroot/startup.sh

# Create nginx configuration directory if it doesn't exist
mkdir -p /etc/nginx/sites-available
mkdir -p /etc/nginx/sites-enabled
mkdir -p /etc/nginx/conf.d

# Remove default nginx config
rm -f /etc/nginx/sites-enabled/default
rm -f /etc/nginx/conf.d/default.conf

# Create custom nginx configuration for PHP routing
cat > /etc/nginx/sites-available/php-app << 'NGINX_CONFIG'
server {
    listen 8080 default_server;
    listen [::]:8080 default_server;
    
    server_name _;
    root /home/site/wwwroot;
    
    # Log configuration
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log warn;
    
    # Index files
    index index.php index.html index.htm;
    
    # Gzip compression
    gzip on;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/json;
    gzip_vary on;
    
    # Route all requests to index.php (Laravel/LavaLite style routing)
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Handle PHP files
    location ~ \.php$ {
        try_files $uri /index.php;
        
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_param REQUEST_URI $request_uri;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
    
    # Deny access to hidden files and directories
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }
}
NGINX_CONFIG

# Enable the site
ln -sf /etc/nginx/sites-available/php-app /etc/nginx/sites-enabled/php-app 2>/dev/null || true

# Test nginx configuration
nginx -t

# Start PHP-FPM in the background
echo "Starting PHP-FPM..."
php-fpm8.2 --daemonize --fpm-config /etc/php/8.2/fpm/php-fpm.conf 2>&1 || \
php-fpm --daemonize 2>&1 || \
php -S 127.0.0.1:9000 -t /home/site/wwwroot &

# Wait a bit for PHP-FPM to start
sleep 2

# Start nginx in foreground
echo "Starting nginx..."
exec nginx -g "daemon off;"
