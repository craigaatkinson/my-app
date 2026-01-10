<?php
/**
 * Database Initialization Script
 *
 * Creates the SQLite database and initial schema
 * Usage: php scripts/init-database.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "═══════════════════════════════════════════════\n";
echo "  Database Initialization\n";
echo "═══════════════════════════════════════════════\n\n";

$dbPath = __DIR__ . '/../database/atransport.sqlite';

// Create database directory if it doesn't exist
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
    echo "✓ Created database directory\n";
}

// Check if database already exists
if (file_exists($dbPath)) {
    echo "⚠️  Database already exists at: {$dbPath}\n";
    echo "Do you want to recreate it? This will delete all existing data! (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if (strtolower($line) !== 'yes') {
        echo "✗ Database initialization cancelled.\n";
        exit(0);
    }

    unlink($dbPath);
    echo "✓ Removed existing database\n";
}

try {
    // Create new database
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Created new SQLite database at: {$dbPath}\n\n";

    // Create initial tables
    echo "Creating initial tables...\n";

    // Example tables based on AEMS structure
    $tables = [
        'customers' => "
            CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id TEXT UNIQUE,
                company_name TEXT NOT NULL,
                contact_name TEXT,
                email TEXT,
                phone TEXT,
                address TEXT,
                city TEXT,
                state TEXT,
                zip TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'load_data' => "
            CREATE TABLE IF NOT EXISTS load_data (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_number TEXT UNIQUE,
                customer_id TEXT,
                pickup_date DATE,
                delivery_date DATE,
                origin TEXT,
                destination TEXT,
                rate DECIMAL(10,2),
                status TEXT DEFAULT 'pending',
                paid INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
            )
        ",
        'rates' => "
            CREATE TABLE IF NOT EXISTS rates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                origin TEXT,
                destination TEXT,
                rate DECIMAL(10,2),
                effective_date DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        "
    ];

    foreach ($tables as $tableName => $sql) {
        $db->exec($sql);
        echo "  ✓ Created table: {$tableName}\n";
    }

    // Create indexes
    echo "\nCreating indexes...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email)",
        "CREATE INDEX IF NOT EXISTS idx_load_data_customer ON load_data(customer_id)",
        "CREATE INDEX IF NOT EXISTS idx_load_data_status ON load_data(status)",
        "CREATE INDEX IF NOT EXISTS idx_load_data_dates ON load_data(pickup_date, delivery_date)"
    ];

    foreach ($indexes as $sql) {
        $db->exec($sql);
        echo "  ✓ Created index\n";
    }

    echo "\n✓ Database initialization complete!\n";
    echo "\n📊 Database Information:\n";
    echo "   Location: {$dbPath}\n";
    echo "   Size: " . round(filesize($dbPath) / 1024, 2) . " KB\n";

    // Show created tables
    echo "\n📋 Tables Created:\n";
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   • {$row['name']}\n";
    }

    echo "\n💡 Next Steps:\n";
    echo "   1. Place your CSV files in 'database/data/' directory\n";
    echo "   2. Run: php scripts/import-csv.php\n";
    echo "   3. Or import specific file: php scripts/import-csv.php database/data/your-file.csv\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n═══════════════════════════════════════════════\n";
