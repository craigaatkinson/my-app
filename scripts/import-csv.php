<?php
/**
 * CSV Import Script for SQLite Database
 *
 * This script imports CSV files into SQLite tables.
 * Usage:
 *   php scripts/import-csv.php                           # Import all CSV files from database/data/
 *   php scripts/import-csv.php database/data/file.csv    # Import specific CSV file
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

class CSVImporter
{
    private PDO $db;
    private string $dataDir;

    public function __construct()
    {
        $this->dataDir = __DIR__ . '/../database/data';
        $this->initDatabase();
    }

    private function initDatabase(): void
    {
        $dbPath = __DIR__ . '/../database/atransport.sqlite';

        // Create database directory if it doesn't exist
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        // Connect to SQLite
        try {
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "✓ Connected to SQLite database: {$dbPath}\n";
        } catch (PDOException $e) {
            die("✗ Database connection failed: " . $e->getMessage() . "\n");
        }
    }

    public function importFile(string $csvFile): void
    {
        if (!file_exists($csvFile)) {
            echo "✗ File not found: {$csvFile}\n";
            return;
        }

        $tableName = $this->getTableNameFromFile($csvFile);
        echo "\n📁 Importing {$csvFile} into table '{$tableName}'...\n";

        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            echo "✗ Could not open file: {$csvFile}\n";
            return;
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            echo "✗ Could not read headers from {$csvFile}\n";
            fclose($handle);
            return;
        }

        // Clean headers (remove BOM, trim spaces, sanitize)
        $headers = array_map(function($header) {
            $header = trim($header);
            $header = str_replace("\xEF\xBB\xBF", '', $header); // Remove UTF-8 BOM
            $header = preg_replace('/[^a-zA-Z0-9_]/', '_', $header); // Sanitize
            return strtolower($header);
        }, $headers);

        echo "📋 Columns: " . implode(', ', $headers) . "\n";

        // Create or recreate table
        $this->createTable($tableName, $headers);

        // Import data
        $rowCount = 0;
        $this->db->beginTransaction();

        try {
            $placeholders = implode(',', array_fill(0, count($headers), '?'));
            $columnsList = implode(',', array_map(fn($h) => "`{$h}`", $headers));
            $sql = "INSERT INTO `{$tableName}` ({$columnsList}) VALUES ({$placeholders})";
            $stmt = $this->db->prepare($sql);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === count($headers)) {
                    $stmt->execute($row);
                    $rowCount++;
                }
            }

            $this->db->commit();
            echo "✓ Successfully imported {$rowCount} rows into '{$tableName}'\n";
        } catch (PDOException $e) {
            $this->db->rollBack();
            echo "✗ Import failed: " . $e->getMessage() . "\n";
        }

        fclose($handle);
    }

    private function createTable(string $tableName, array $columns): void
    {
        // Drop existing table
        $this->db->exec("DROP TABLE IF EXISTS `{$tableName}`");

        // Create new table with auto-increment ID
        $columnDefs = ["id INTEGER PRIMARY KEY AUTOINCREMENT"];
        foreach ($columns as $col) {
            $columnDefs[] = "`{$col}` TEXT";
        }

        $sql = "CREATE TABLE `{$tableName}` (" . implode(', ', $columnDefs) . ")";
        $this->db->exec($sql);
        echo "✓ Created table '{$tableName}'\n";
    }

    private function getTableNameFromFile(string $filepath): string
    {
        $filename = basename($filepath, '.csv');
        // Convert to valid table name
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '_', $filename);
        return strtolower($tableName);
    }

    public function importAllFiles(): void
    {
        $csvFiles = glob($this->dataDir . '/*.csv');

        if (empty($csvFiles)) {
            echo "ℹ️  No CSV files found in {$this->dataDir}\n";
            echo "\n💡 Place your CSV files in the 'database/data/' directory and run this script again.\n";
            return;
        }

        echo "Found " . count($csvFiles) . " CSV file(s) to import\n";
        foreach ($csvFiles as $csvFile) {
            $this->importFile($csvFile);
        }
    }

    public function showTables(): void
    {
        echo "\n📊 Database Tables:\n";
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tables)) {
            echo "   (no tables)\n";
            return;
        }

        foreach ($tables as $table) {
            $countStmt = $this->db->query("SELECT COUNT(*) FROM `{$table}`");
            $count = $countStmt->fetchColumn();
            echo "   • {$table} ({$count} rows)\n";
        }
    }
}

// Main execution
echo "═══════════════════════════════════════════════\n";
echo "  CSV to SQLite Import Tool\n";
echo "═══════════════════════════════════════════════\n";

$importer = new CSVImporter();

if (isset($argv[1])) {
    // Import specific file
    $importer->importFile($argv[1]);
} else {
    // Import all CSV files from database/data/
    $importer->importAllFiles();
}

$importer->showTables();

echo "\n✓ Import complete!\n";
echo "═══════════════════════════════════════════════\n";
