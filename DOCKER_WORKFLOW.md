# Docker Development Workflow Guide

## 🚀 Quick Answer

**Your code changes ARE automatically reflected in Docker!**

Because of volume mounting in `docker-compose.yml`, your code is **live-synced** between your computer and the Docker container. You edit files on your computer, and they're instantly available inside the container.

## 📂 What's Automatically Updated (No Rebuild Needed)

These changes appear **instantly** in your running Docker containers:

✅ **PHP Code Changes**
- Any `.php` file in `src/`, `public/`, `config/`
- Changes take effect immediately (refresh browser)

✅ **HTML/CSS/JavaScript**
- Files in `public/` directory
- Instant updates (refresh browser)

✅ **Database Files**
- SQLite database in `database/`
- CSV files you add to `database/data/`

✅ **Configuration Files**
- `.env` file changes (may need container restart)
- Most config files in `config/`

## 🔄 When You MUST Rebuild Docker

Rebuild Docker containers when you change:

❌ **Dependencies**
```bash
# When you modify composer.json
composer require new-package

# You must rebuild
docker-compose down
docker-compose up --build
```

❌ **Dockerfile Changes**
```bash
# When you edit php/Dockerfile
# (adding PHP extensions, system packages, etc.)

# You must rebuild
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

❌ **Docker Compose Changes**
```bash
# When you edit docker-compose.yml
# (adding services, changing ports, environment variables)

# Restart with new config
docker-compose down
docker-compose up -d
```

❌ **Nginx Configuration**
```bash
# When you edit nginx/nginx.conf

# Restart nginx service only
docker-compose restart nginx
```

## 🔧 Daily Development Workflow

### Starting Your Work Session

```bash
# 1. Make sure Docker is running (Docker Desktop on Mac)

# 2. Start your containers
docker-compose up -d

# 3. Verify everything is running
docker-compose ps

# 4. View logs (if needed)
docker-compose logs -f
```

### During Development

```bash
# Your normal workflow - JUST EDIT FILES!
# - Edit PHP files in VSCode
# - Edit CSS/JS in public/
# - Add CSV files to database/data/
# - Everything updates automatically!

# Refresh your browser to see changes
# Visit: http://localhost
```

### Running Commands Inside Docker

```bash
# Access PHP container shell
docker-compose exec php bash

# Run PHP scripts
docker-compose exec php php scripts/import-csv.php

# Run Composer commands
docker-compose exec php composer install
docker-compose exec php composer require new-package

# Initialize database
docker-compose exec php composer run db:init

# Import CSV files
docker-compose exec php composer run db:import

# Access SQLite database
docker-compose exec php sqlite3 /var/www/html/database/atransport.sqlite
```

### Ending Your Work Session

```bash
# Option 1: Stop containers (preserves state)
docker-compose stop

# Option 2: Stop and remove containers (clean slate next time)
docker-compose down

# Option 3: Stop and remove everything including volumes
docker-compose down -v  # ⚠️ This deletes your database!
```

## 🆕 When You Add New Dependencies

### Adding PHP Packages

```bash
# 1. Stop containers
docker-compose down

# 2. Add package to composer.json or run:
composer require vendor/package

# 3. Rebuild and restart
docker-compose up --build -d

# 4. Verify package is installed
docker-compose exec php composer show
```

### Adding New PHP Extensions

```bash
# 1. Edit php/Dockerfile
# Add extension installation:
RUN docker-php-ext-install extension_name

# 2. Rebuild with no cache
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# 3. Verify extension is loaded
docker-compose exec php php -m | grep extension_name
```

## 🔍 Common Scenarios

### Scenario 1: "I changed a PHP file but don't see updates"

```bash
# Solution: Just refresh your browser
# PHP is interpreted, so changes are instant

# If still not working, check PHP errors
docker-compose logs php

# Clear any opcode cache (if enabled)
docker-compose restart php
```

### Scenario 2: "I installed a new Composer package"

```bash
# You need to rebuild!
docker-compose down
docker-compose up --build -d
```

### Scenario 3: "I changed .env file"

```bash
# Solution: Restart containers to pick up new environment
docker-compose restart

# Or for full reload
docker-compose down
docker-compose up -d
```

### Scenario 4: "I updated nginx.conf"

```bash
# Solution: Restart nginx service
docker-compose restart nginx

# Or reload nginx config without restart
docker-compose exec nginx nginx -s reload
```

### Scenario 5: "I added a new CSV file"

```bash
# No rebuild needed! Just import it
docker-compose exec php php scripts/import-csv.php

# Or import specific file
docker-compose exec php php scripts/import-csv.php database/data/your-file.csv
```

## 🎯 Best Practices

### ✅ DO:

1. **Keep Docker running during development**
   ```bash
   docker-compose up -d  # Start in background
   ```

2. **Edit files on your computer (not in container)**
   - Use VSCode on your Mac
   - Changes automatically sync to container

3. **Run scripts via docker-compose exec**
   ```bash
   docker-compose exec php php script.php
   ```

4. **Check logs when things go wrong**
   ```bash
   docker-compose logs -f php
   docker-compose logs -f nginx
   ```

5. **Restart services, not rebuild, for most changes**
   ```bash
   docker-compose restart php
   ```

### ❌ DON'T:

1. **Don't edit files inside the container**
   - Changes will be lost when container restarts
   - Always edit on your Mac

2. **Don't rebuild for every change**
   - Only rebuild for Dockerfile/composer.json changes
   - Regular code edits are instant

3. **Don't use `docker-compose down -v` casually**
   - This deletes your database!
   - Use `docker-compose down` instead

4. **Don't forget to rebuild after dependency changes**
   - New composer packages need rebuild
   - New PHP extensions need rebuild

## 🔄 Complete Rebuild (Nuclear Option)

If everything is broken, start fresh:

```bash
# 1. Stop and remove everything
docker-compose down -v

# 2. Remove all Docker images
docker-compose rm -f

# 3. Rebuild from scratch
docker-compose build --no-cache

# 4. Start fresh
docker-compose up -d

# 5. Recreate database
docker-compose exec php composer run db:init
docker-compose exec php composer run db:import
```

## 📊 Monitoring Your Containers

```bash
# View running containers
docker-compose ps

# View container resource usage
docker stats

# View real-time logs
docker-compose logs -f

# View logs for specific service
docker-compose logs -f php
docker-compose logs -f nginx

# Check container health
docker-compose ps
# Look for "healthy" in STATUS column
```

## 🚨 Troubleshooting

### Problem: "Cannot connect to Docker daemon"

```bash
# Solution: Start Docker Desktop
open -a Docker

# Wait for Docker to fully start, then:
docker-compose up -d
```

### Problem: "Port 80 already in use"

```bash
# Solution: Find what's using port 80
lsof -i :80

# Kill the process or change port in docker-compose.yml
# Change: "80:80" to "8080:80"
# Then access via http://localhost:8080
```

### Problem: "Container exits immediately"

```bash
# Solution: Check container logs
docker-compose logs php

# Usually means PHP error or missing dependency
# Fix error and restart
docker-compose restart php
```

### Problem: "Changes not appearing"

```bash
# 1. Verify volumes are mounted
docker-compose exec php ls -la /var/www/html

# 2. Check file permissions
ls -la

# 3. Restart PHP service
docker-compose restart php

# 4. Hard refresh browser (Cmd+Shift+R on Mac)
```

## 📝 Summary Cheat Sheet

| Action | Command | Rebuild Needed? |
|--------|---------|----------------|
| Edit PHP file | Just edit & refresh | ❌ No |
| Edit CSS/JS | Just edit & refresh | ❌ No |
| Add CSV file | Edit & import | ❌ No |
| Change .env | Restart containers | ❌ No |
| Install composer package | `composer require` | ✅ Yes |
| Edit Dockerfile | Modify file | ✅ Yes |
| Change docker-compose.yml | Edit file | ⚠️ Restart |
| Edit nginx.conf | Modify file | ⚠️ Restart nginx |

## 🎓 Remember

**The Golden Rule:**
> If you change **WHAT'S INSTALLED** → Rebuild
> If you change **YOUR CODE** → No rebuild needed!

Your Docker setup uses **volume mounting**, which means:
- Your Mac filesystem is "mounted" into the container
- Changes on your Mac appear instantly in the container
- You get the best of both worlds: containerized environment + live development!
