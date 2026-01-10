<?php
/**
 * CSV Import Script for Load Data
 * Imports load_data.csv into SQLite database atr.db
 */

// Database and CSV file paths
$csvFile = __DIR__ . '/csv/load_data.csv';
$dbFile = __DIR__ . '/Models/atr.db';

echo "🚀 Starting CSV import process...\n";
echo "CSV File: {$csvFile}\n";
echo "Database: {$dbFile}\n\n";

// Check if files exist
if (!file_exists($csvFile)) {
    die("❌ Error: CSV file not found at {$csvFile}\n");
}

if (!file_exists($dbFile)) {
    die("❌ Error: Database file not found at {$dbFile}\n");
}

try {
    // Connect to SQLite database
    $pdo = new PDO("sqlite:{$dbFile}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to database successfully\n";

    // Open CSV file
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        throw new Exception("Failed to open CSV file");
    }

    // Read the header row
    $headers = fgetcsv($handle);
    echo "📋 CSV Headers: " . implode(', ', $headers) . "\n";

    // Prepare the insert statement
    $placeholders = str_repeat('?,', count($headers) - 1) . '?';
    $sql = "INSERT INTO load_data (" . implode(',', $headers) . ") VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    echo "📝 Prepared SQL: {$sql}\n\n";

    // Begin transaction for better performance
    $pdo->beginTransaction();

    $rowCount = 0;
    $errorCount = 0;
    $skippedCount = 0;

    echo "📥 Starting data import...\n";

    // Process each row
    while (($data = fgetcsv($handle)) !== FALSE) {
        $rowCount++;
        
        // Skip empty rows
        if (empty(array_filter($data))) {
            $skippedCount++;
            echo "⏭️  Skipping empty row {$rowCount}\n";
            continue;
        }

        // Clean up data
        $cleanData = [];
        foreach ($data as $index => $value) {
            // Convert empty strings to NULL for better database handling
            $cleanValue = trim($value);
            if ($cleanValue === '' || $cleanValue === 'NA') {
                $cleanValue = null;
            }
            
            // Handle specific data type conversions
            if (in_array($headers[$index], ['invoice', 'miles', 'comp_id'])) {
                $cleanValue = $cleanValue ? (int)$cleanValue : null;
            } elseif (in_array($headers[$index], ['access', 'rate_base', 'amount'])) {
                $cleanValue = $cleanValue ? (float)$cleanValue : null;
            }
            
            $cleanData[] = $cleanValue;
        }

        try {
            $stmt->execute($cleanData);
            
            if ($rowCount % 100 == 0) {
                echo "✅ Processed {$rowCount} rows...\n";
            }
        } catch (PDOException $e) {
            $errorCount++;
            echo "❌ Error on row {$rowCount}: " . $e->getMessage() . "\n";
            echo "   Data: " . implode(', ', $data) . "\n";
            
            // Continue processing other rows
            continue;
        }
    }

    // Commit the transaction
    $pdo->commit();
    fclose($handle);

    echo "\n🎉 Import completed!\n";
    echo "📊 Statistics:\n";
    echo "   Total rows processed: {$rowCount}\n";
    echo "   Successfully imported: " . ($rowCount - $errorCount - $skippedCount) . "\n";
    echo "   Errors: {$errorCount}\n";
    echo "   Skipped (empty): {$skippedCount}\n";

    // Verify the import
    $countStmt = $pdo->query("SELECT COUNT(*) FROM load_data");
    $totalRecords = $countStmt->fetchColumn();
    echo "   Total records in database: {$totalRecords}\n";

    // Show sample of imported data
    echo "\n📋 Sample of imported data:\n";
    $sampleStmt = $pdo->query("SELECT invoice, company, truck, driver, amount FROM load_data WHERE invoice IS NOT NULL ORDER BY invoice DESC LIMIT 5");
    $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($samples as $sample) {
        echo "   Invoice: {$sample['invoice']} | Company: {$sample['company']} | Truck: {$sample['truck']} | Amount: $" . number_format($sample['amount'], 2) . "\n";
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✨ Import process completed successfully!\n";
?>