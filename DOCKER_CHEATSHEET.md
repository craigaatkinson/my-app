# Docker Quick Reference Cheat Sheet

## 🚀 Daily Commands (Most Used)

```bash
# Start everything
docker-compose up -d

# Stop everything
docker-compose down

# Restart everything
docker-compose restart

# View logs (live)
docker-compose logs -f

# Check status
docker-compose ps
```

## 💻 Running Commands Inside Container

```bash
# Access bash shell
docker-compose exec php bash

# Run PHP script
docker-compose exec php php script.php

# Composer commands
docker-compose exec php composer install
docker-compose exec php composer require package-name
docker-compose exec php composer update

# Database operations
docker-compose exec php composer run db:init
docker-compose exec php composer run db:import
docker-compose exec php sqlite3 database/atransport.sqlite
```

## 🔄 When You Need to Rebuild

```bash
# After changing composer.json
docker-compose down
docker-compose up --build -d

# After changing Dockerfile (clean rebuild)
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# After changing docker-compose.yml
docker-compose down
docker-compose up -d
```

## 🔍 Debugging Commands

```bash
# View logs for specific service
docker-compose logs php
docker-compose logs nginx

# Follow logs in real-time
docker-compose logs -f php

# Check if containers are healthy
docker-compose ps

# Inspect container
docker inspect my-app-php-1

# List all containers (including stopped)
docker ps -a

# View resource usage
docker stats
```

## 🧹 Cleanup Commands

```bash
# Remove stopped containers
docker-compose down

# Remove containers and volumes (⚠️ DELETES DATABASE)
docker-compose down -v

# Remove all unused Docker resources
docker system prune

# Remove all (including images)
docker system prune -a

# Remove specific volume
docker volume rm my-app_db_data
```

## 🆘 Emergency Reset

```bash
# Nuclear option - completely reset everything
docker-compose down -v
docker-compose rm -f
docker-compose build --no-cache
docker-compose up -d

# Then recreate database
docker-compose exec php composer run db:init
docker-compose exec php composer run db:import
```

## 📊 Quick Status Checks

```bash
# What's running?
docker-compose ps

# What ports are exposed?
docker-compose port nginx 80

# How much disk space?
docker system df

# List all volumes
docker volume ls

# Inspect volume
docker volume inspect my-app_db_data
```

## ⚡ Pro Tips

```bash
# Start and view logs simultaneously
docker-compose up

# Restart single service
docker-compose restart php

# Stop single service
docker-compose stop nginx

# View last 50 log lines
docker-compose logs --tail=50 php

# Run command without entering container
docker-compose exec -T php php -v

# Copy files from container to host
docker cp my-app-php-1:/var/www/html/file.txt ./

# Copy files from host to container
docker cp ./file.txt my-app-php-1:/var/www/html/
```

## 🎯 Common Workflows

### Add New Composer Package
```bash
composer require vendor/package    # On your Mac
docker-compose down
docker-compose up --build -d
```

### Import New CSV File
```bash
# Just place file in database/data/ and run:
docker-compose exec php composer run db:import
```

### Update Nginx Config
```bash
# Edit nginx/nginx.conf on your Mac, then:
docker-compose restart nginx
```

### View Database
```bash
docker-compose exec php sqlite3 database/atransport.sqlite
.tables
SELECT * FROM customers LIMIT 5;
.quit
```

### Check PHP Version/Extensions
```bash
docker-compose exec php php -v
docker-compose exec php php -m
docker-compose exec php php -i | grep sqlite
```

## 🔐 Environment Variables

```bash
# View container environment
docker-compose exec php env

# View specific variable
docker-compose exec php printenv DB_CONNECTION

# Test .env file loading
docker-compose config
```

## 📦 Volume Commands

```bash
# List volumes
docker volume ls

# Inspect volume
docker volume inspect my-app_db_data

# Backup volume
docker run --rm -v my-app_db_data:/data -v $(pwd):/backup alpine tar czf /backup/backup.tar.gz -C /data .

# Restore volume
docker run --rm -v my-app_db_data:/data -v $(pwd):/backup alpine tar xzf /backup/backup.tar.gz -C /data
```

## 🌐 Network Commands

```bash
# List networks
docker network ls

# Inspect network
docker network inspect my-app_app-network

# Connect container to network
docker network connect my-app_app-network container-name
```

## ⏱️ Performance Monitoring

```bash
# Real-time resource usage
docker stats

# Resource usage for specific container
docker stats my-app-php-1

# Container processes
docker-compose top

# Disk usage
docker system df -v
```

## 🔗 Access URLs

```
Application: http://localhost
Nginx:       http://localhost:80
Health:      http://localhost/health (if configured)
```

## 📱 Mobile/Remote Access

```bash
# Find your Mac's IP
ifconfig | grep "inet " | grep -v 127.0.0.1

# Access from phone/tablet on same network
http://YOUR_MAC_IP/
```

## 🛑 Stop Specific Services

```bash
# Stop PHP only
docker-compose stop php

# Stop Nginx only
docker-compose stop nginx

# Start specific service
docker-compose start php
```

## 🔧 Configuration Files

| File | Purpose | Rebuild Needed? |
|------|---------|----------------|
| `.env` | Environment variables | Restart |
| `composer.json` | PHP dependencies | Yes |
| `docker-compose.yml` | Container config | Restart |
| `php/Dockerfile` | PHP container build | Yes |
| `nginx/nginx.conf` | Web server config | Restart nginx |
| `public/*.php` | Application code | No |

## 💡 Remember

- **Edit files on your Mac** - they automatically sync to container
- **Rebuild only for dependencies** - not for code changes
- **Use `docker-compose exec`** to run commands inside container
- **Check logs first** when troubleshooting
- **`docker-compose down -v`** deletes your database!
