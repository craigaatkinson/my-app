# Database Directory

This directory contains SQLite database files, CSV import data, migrations, and seed files.

## Directory Structure

```
database/
├── atransport.sqlite          # SQLite database file (auto-generated)
├── data/                      # CSV files for importing data
│   └── *.csv                  # Place your CSV files here
├── migrations/                # Database migration scripts
│   └── *.sql                  # SQL migration files
└── seeds/                     # Database seed files
    └── *.sql                  # SQL seed data
```

## CSV File Location

**Place all your CSV files in `database/data/`**

This follows AEMS project conventions and keeps your data organized.

Example CSV files you might store here:
- `customers.csv` - Customer data
- `load_data.csv` - Transportation load data
- `rates.csv` - Rate information
- `invoices.csv` - Invoice data

## Importing CSV Data

Use the provided PHP scripts in the project root to import CSV data:

```bash
# Import all CSV files
php scripts/import-csv.php

# Import specific CSV file
php scripts/import-csv.php database/data/customers.csv
```

## Database Management

```bash
# Create new database
touch database/atransport.sqlite

# Access SQLite shell
sqlite3 database/atransport.sqlite

# Backup database
cp database/atransport.sqlite database/backups/atransport_$(date +%Y%m%d).sqlite

# View database schema
sqlite3 database/atransport.sqlite ".schema"
```

## Environment Configuration

Make sure your `.env` file has the correct SQLite configuration:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/atransport.sqlite
```
