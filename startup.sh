#!/bin/bash
cd /home/site/wwwroot

# Configure nginx to handle routing
echo "server {
    listen 8080;
    server_name _;
    root /home/site/wwwroot;
    
    index index.php;
    
    # Send all requests to index.php if file/folder doesn't exist
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    # Handle PHP files
    location ~ \\.php\$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /home/site/wwwroot\$fastcgi_script_name;
        include fastcgi_params;
    }
}" > /etc/nginx/sites-available/default

# Start PHP and nginx
php-fpm -D
nginx -g "daemon off;"
