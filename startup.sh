#!/bin/bash

set -e

echo "=== Startup Script Started ===" > /tmp/startup.log

# Ensure we're in the right directory
cd /home/site/wwwroot
echo "Working directory: $(pwd)" >> /tmp/startup.log

# Create nginx configuration directory
mkdir -p /etc/nginx/sites-available
mkdir -p /etc/nginx/sites-enabled

# Remove default config
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true

# Create the PHP app configuration
cat > /etc/nginx/sites-available/app << 'EOF'
server {
    listen 8080 default_server;
    server_name _;
    root /home/site/wwwroot;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
EOF

# Enable the site
ln -sf /etc/nginx/sites-available/app /etc/nginx/sites-enabled/app
echo "Nginx config created" >> /tmp/startup.log

# Test nginx configuration
if nginx -t 2>&1 >> /tmp/startup.log; then
    echo "Nginx config test passed" >> /tmp/startup.log
else
    echo "Nginx config test FAILED" >> /tmp/startup.log
fi

# Start PHP-FPM
echo "Starting PHP-FPM..." >> /tmp/startup.log
php-fpm8.2 --daemonize --fpm-config /etc/php/8.2/fpm/php-fpm.conf 2>&1 >> /tmp/startup.log || \
php-fpm --daemonize 2>&1 >> /tmp/startup.log || \
true

sleep 1

# Start nginx
echo "Starting nginx..." >> /tmp/startup.log
exec nginx -g "daemon off;"
