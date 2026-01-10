# SQLite Database Setup Guide

This guide will help you set up and use SQLite with CSV import functionality.

## 📁 CSV File Location

**All CSV files should be placed in:** `database/data/`

This is the standard location for data files in this project.

## 🚀 Quick Start

### 1. Initialize the Database

```bash
# Using composer script
composer run db:init

# Or directly
php scripts/init-database.php
```

This creates:
- SQLite database at `database/atransport.sqlite`
- Initial tables: `customers`, `load_data`, `rates`
- Necessary indexes

### 2. Prepare Your CSV Files

Place your CSV files in `database/data/` directory:

```
database/
└── data/
    ├── customers.csv
    ├── load_data.csv
    ├── rates.csv
    └── example_customers.csv (sample file)
```

**CSV File Requirements:**
- First row must contain column headers
- Headers will be sanitized (lowercase, underscores for spaces)
- UTF-8 encoding recommended
- Proper CSV format (comma-separated, quoted strings if needed)

### 3. Import CSV Data

```bash
# Import all CSV files from database/data/
composer run db:import

# Or import specific file
php scripts/import-csv.php database/data/customers.csv
```

## 📊 Example CSV Format

**customers.csv:**
```csv
customer_id,company_name,contact_name,email,phone,address,city,state,zip
1,ABC Logistics,John Smith,john@abclogistics.com,555-0101,123 Main St,Dallas,TX,75201
2,XYZ Transport,Jane Doe,jane@xyztransport.com,555-0102,456 Oak Ave,Houston,TX,77002
```

**load_data.csv:**
```csv
invoice_number,customer_id,pickup_date,delivery_date,origin,destination,rate,status
INV-001,1,2024-01-15,2024-01-18,Dallas,Houston,450.00,completed
INV-002,2,2024-01-20,2024-01-23,Houston,Austin,520.00,pending
```

## 🔧 Working with SQLite

### Access Database via CLI

```bash
# Open SQLite shell
sqlite3 database/atransport.sqlite

# Useful SQLite commands
.tables                  # List all tables
.schema customers        # Show table schema
SELECT * FROM customers; # Query data
.quit                    # Exit
```

### Database Backup

```bash
# Manual backup
cp database/atransport.sqlite database/backups/atransport_$(date +%Y%m%d).sqlite

# View database size
du -h database/atransport.sqlite
```

### Reset Database

```bash
# Remove database and start fresh
rm database/atransport.sqlite
composer run db:init
composer run db:import
```

## 🐳 Using with Docker

The Docker setup is already configured for SQLite:

```bash
# Start containers (MySQL is commented out)
docker-compose up -d

# Initialize database inside container
docker-compose exec php composer run db:init

# Import CSV files inside container
docker-compose exec php composer run db:import

# Access SQLite shell inside container
docker-compose exec php sqlite3 /var/www/html/database/atransport.sqlite
```

## 🔍 Troubleshooting

### "Database file not found"

Make sure the database directory exists:
```bash
mkdir -p database/data
composer run db:init
```

### "PDO SQLite extension not loaded"

Check PHP extensions:
```bash
php -m | grep -i sqlite

# If missing, install (Ubuntu/Debian)
sudo apt-get install php-sqlite3

# Or (macOS with Homebrew)
brew install php
```

### "CSV import fails"

- Check CSV file encoding (should be UTF-8)
- Ensure first row contains headers
- Verify file has proper CSV format
- Check file permissions

### "Permission denied"

```bash
chmod 755 database
chmod 644 database/atransport.sqlite
```

## 📝 Configuration

Your `.env` file is configured for SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/atransport.sqlite
```

To switch back to MySQL, uncomment the MySQL settings in `.env` and `docker-compose.yml`.

## 🔄 Switching Between MySQL and SQLite

### Switch to MySQL

1. Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Atransport
DB_USERNAME=root
DB_PASSWORD=newell29
```

2. Uncomment MySQL service in `docker-compose.yml`

3. Uncomment `depends_on: db` in PHP service

4. Restart containers:
```bash
docker-compose down
docker-compose up -d
```

### Switch to SQLite

1. Edit `.env`:
```env
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/atransport.sqlite
```

2. Comment out MySQL dependency in `docker-compose.yml`

3. Restart containers

## 💡 Best Practices

1. **Always backup before major changes**
   ```bash
   cp database/atransport.sqlite database/backups/backup_$(date +%Y%m%d_%H%M%S).sqlite
   ```

2. **Keep CSV files in version control** (if appropriate)
   - Add to `.gitignore` if they contain sensitive data
   - Keep example files for documentation

3. **Test imports on a copy first**
   ```bash
   cp database/atransport.sqlite database/atransport_test.sqlite
   # Test import on the copy
   ```

4. **Regular maintenance**
   ```bash
   # Vacuum database to reclaim space
   sqlite3 database/atransport.sqlite "VACUUM;"

   # Analyze for query optimization
   sqlite3 database/atransport.sqlite "ANALYZE;"
   ```

## 📚 Additional Resources

- [SQLite Documentation](https://www.sqlite.org/docs.html)
- [PHP PDO SQLite](https://www.php.net/manual/en/ref.pdo-sqlite.php)
- [CSV Best Practices](https://tools.ietf.org/html/rfc4180)

## 🆘 Getting Help

If you encounter issues:

1. Check the error messages in the import script output
2. Verify your CSV file format
3. Check database permissions
4. Review the SQLite error logs
5. Consult `database/README.md` for more information
