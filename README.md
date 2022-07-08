# docker-wordpress-starter

This is a WordPress starter docker-compose for installing WordPress with LEMP Stack.

Service included

- WordPress Latest version
- PHP 7.4 FPM
- Nginx 1.20
- MariaDB 10.5
- phpmyadmin latest version
- mailhog latest version

## Installation

1. Copy the `.env.example` file to `.env`
2. Config the `.env` file

```env
# App Information
APP_NAME=wordpress              # Application name (for settup the container name)

# Database Connection
MYSQL_ROOT_PASSWORD=123456      # Set root password for access mysql
DATABASE_NAME=wordpress         # Set database name
DATABASE_USER=wordpress         # Set user for connect the database
DATABASE_PASS=123456            # Set password for user

# WordPress Config
WORDPRESS_DEBUG=0               # Enable/Disable debug mode
```

3. After set the `.env` run command `docker compose up -d`
4. Access the website by http://localhost
5. Access phpmyadmin by http://localhost:8000/
6. Access Mailhog by http://localhost:8025/

### Optional

Config the `system/php/conf.d/uploads.ini`

```ini
file_uploads = On
memory_limit = 256M             # Set memory limit
upload_max_filesize = 300M      # Set upload max file size
post_max_size = 1000M           # Set post max size
max_execution_time = 1200       # Set max execution time
```
