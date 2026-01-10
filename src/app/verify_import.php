<?php
/**
 * Verification Script for Imported CSV Data
 * Checks the integrity and completeness of imported load data
 */

$dbFile = __DIR__ . '/Models/atr.db';

if (!file_exists($dbFile)) {
    die("❌ Database file not found: {$dbFile}\n");
}

try {
    $pdo = new PDO("sqlite:{$dbFile}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Verifying imported data in atr.db...\n\n";

    // Basic counts
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM load_data");
    $totalRecords = $totalStmt->fetchColumn();
    echo "📊 Total Records: " . number_format($totalRecords) . "\n";

    // Records with valid invoices
    $validInvoicesStmt = $pdo->query("SELECT COUNT(*) FROM load_data WHERE invoice IS NOT NULL AND invoice != ''");
    $validInvoices = $validInvoicesStmt->fetchColumn();
    echo "📋 Valid Invoices: " . number_format($validInvoices) . "\n";

    // Date range
    $dateRangeStmt = $pdo->query("
        SELECT 
            MIN(load_dt) as earliest_date,
            MAX(load_dt) as latest_date
        FROM load_data 
        WHERE load_dt IS NOT NULL AND load_dt != ''
    ");
    $dateRange = $dateRangeStmt->fetch(PDO::FETCH_ASSOC);
    echo "📅 Date Range: {$dateRange['earliest_date']} to {$dateRange['latest_date']}\n";

    // Financial summary
    $financialStmt = $pdo->query("
        SELECT 
            COUNT(*) as loads_with_amount,
            SUM(amount) as total_revenue,
            AVG(amount) as avg_load_value,
            MIN(amount) as min_amount,
            MAX(amount) as max_amount
        FROM load_data 
        WHERE amount IS NOT NULL AND amount > 0
    ");
    $financial = $financialStmt->fetch(PDO::FETCH_ASSOC);
    echo "\n💰 Financial Summary:\n";
    echo "   Loads with Amount: " . number_format($financial['loads_with_amount']) . "\n";
    echo "   Total Revenue: $" . number_format($financial['total_revenue'], 2) . "\n";
    echo "   Average Load Value: $" . number_format($financial['avg_load_value'], 2) . "\n";
    echo "   Min Amount: $" . number_format($financial['min_amount'], 2) . "\n";
    echo "   Max Amount: $" . number_format($financial['max_amount'], 2) . "\n";

    // Company analysis
    $companiesStmt = $pdo->query("
        SELECT 
            company,
            COUNT(*) as load_count,
            SUM(amount) as company_revenue
        FROM load_data 
        WHERE company IS NOT NULL AND company != '' AND amount > 0
        GROUP BY company 
        ORDER BY company_revenue DESC 
        LIMIT 10
    ");
    $companies = $companiesStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n🏢 Top 10 Companies by Revenue:\n";
    foreach ($companies as $company) {
        echo sprintf(
            "   %-25s | %3d loads | $%s\n",
            substr($company['company'], 0, 25),
            $company['load_count'],
            number_format($company['company_revenue'], 2)
        );
    }

    // Truck analysis
    $trucksStmt = $pdo->query("
        SELECT 
            truck,
            COUNT(*) as load_count,
            SUM(amount) as truck_revenue
        FROM load_data 
        WHERE truck IS NOT NULL AND truck != '' AND truck != 'NA' AND amount > 0
        GROUP BY truck 
        ORDER BY load_count DESC 
        LIMIT 10
    ");
    $trucks = $trucksStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n🚛 Top 10 Trucks by Load Count:\n";
    foreach ($trucks as $truck) {
        echo sprintf(
            "   %-10s | %3d loads | $%s\n",
            $truck['truck'],
            $truck['load_count'],
            number_format($truck['truck_revenue'], 2)
        );
    }

    // Status distribution
    $statusStmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / {$totalRecords}, 2) as percentage
        FROM load_data 
        WHERE status IS NOT NULL AND status != ''
        GROUP BY status 
        ORDER BY count DESC
    ");
    $statuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n📈 Load Status Distribution:\n";
    foreach ($statuses as $status) {
        echo sprintf(
            "   %-15s | %4d loads | %5.1f%%\n",
            $status['status'],
            $status['count'],
            $status['percentage']
        );
    }

    // Data quality checks
    echo "\n🔍 Data Quality Checks:\n";
    
    $nullInvoicesStmt = $pdo->query("SELECT COUNT(*) FROM load_data WHERE invoice IS NULL OR invoice = ''");
    $nullInvoices = $nullInvoicesStmt->fetchColumn();
    echo "   Missing Invoices: " . number_format($nullInvoices) . " (" . round($nullInvoices/$totalRecords*100, 1) . "%)\n";
    
    $nullCompaniesStmt = $pdo->query("SELECT COUNT(*) FROM load_data WHERE company IS NULL OR company = ''");
    $nullCompanies = $nullCompaniesStmt->fetchColumn();
    echo "   Missing Companies: " . number_format($nullCompanies) . " (" . round($nullCompanies/$totalRecords*100, 1) . "%)\n";
    
    $nullAmountsStmt = $pdo->query("SELECT COUNT(*) FROM load_data WHERE amount IS NULL OR amount = 0");
    $nullAmounts = $nullAmountsStmt->fetchColumn();
    echo "   Missing/Zero Amounts: " . number_format($nullAmounts) . " (" . round($nullAmounts/$totalRecords*100, 1) . "%)\n";

    // Recent loads
    echo "\n🕒 Recent Loads (Last 10):\n";
    $recentStmt = $pdo->query("
        SELECT invoice, load_dt, company, truck, amount, status
        FROM load_data 
        WHERE load_dt IS NOT NULL 
        ORDER BY load_dt DESC, invoice DESC 
        LIMIT 10
    ");
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recent as $load) {
        echo sprintf(
            "   %s | %s | %-15s | %s | $%s | %s\n",
            $load['invoice'],
            substr($load['load_dt'], 0, 10),
            substr($load['company'], 0, 15),
            $load['truck'],
            number_format($load['amount'], 2),
            $load['status']
        );
    }

    echo "\n✅ Data verification completed!\n";
    echo "🗄️  Database appears to be properly imported and ready for use.\n";

} catch (Exception $e) {
    echo "❌ Error during verification: " . $e->getMessage() . "\n";
    exit(1);
}
?>