-- Quick Reference SQL Queries for atransport.sqlite
-- Run these queries using: sqlite3 database/atransport.sqlite < database/QUERIES.sql
-- Or interactively: sqlite3 database/atransport.sqlite

-- ============================================================================
-- DATABASE OVERVIEW
-- ============================================================================

-- List all tables
.tables

-- Show table schemas
.schema customers
.schema load_data

-- Count records in each table
SELECT 'customers' as table_name, COUNT(*) as row_count FROM customers
UNION ALL
SELECT 'load_data' as table_name, COUNT(*) as row_count FROM load_data
UNION ALL
SELECT 'example_customers' as table_name, COUNT(*) as row_count FROM example_customers;

-- ============================================================================
-- CUSTOMER QUERIES
-- ============================================================================

-- View all customers
SELECT * FROM customers;

-- Find specific customer by company name
SELECT * FROM customers WHERE company LIKE '%Mills%';

-- List customers by commodity type
SELECT company, commodity, city_st_zip
FROM customers
WHERE commodity LIKE '%Clothing%';

-- Customers sorted by rate
SELECT company, miles, rate_base, (rate_base / CAST(miles AS REAL)) as rate_per_mile
FROM customers
ORDER BY rate_base DESC;

-- Count customers by state (assuming city_st_zip contains state)
SELECT
    SUBSTR(city_st_zip, INSTR(city_st_zip, ' ') + 1, 2) as state,
    COUNT(*) as customer_count
FROM customers
GROUP BY state
ORDER BY customer_count DESC;

-- ============================================================================
-- LOAD DATA QUERIES
-- ============================================================================

-- View recent loads (latest 20)
SELECT invoice, load_dt, driver, company, status, amount
FROM load_data
ORDER BY load_dt DESC
LIMIT 20;

-- Filter by status
SELECT invoice, load_dt, driver, company, status, amount
FROM load_data
WHERE status = 'completed'
ORDER BY load_dt DESC;

-- Loads for specific driver
SELECT invoice, load_dt, company, date_arv, date_dlv, amount, status
FROM load_data
WHERE driver LIKE '%Adams%'
ORDER BY load_dt DESC;

-- Loads for specific company
SELECT invoice, load_dt, driver, date_arv, date_dlv, amount, status
FROM load_data
WHERE company LIKE '%Fiber%'
ORDER BY load_dt DESC;

-- Loads by truck
SELECT truck, COUNT(*) as load_count, SUM(amount) as total_revenue
FROM load_data
GROUP BY truck
ORDER BY load_count DESC;

-- Revenue by driver
SELECT
    driver,
    COUNT(*) as loads_completed,
    SUM(amount) as total_revenue,
    ROUND(AVG(amount), 2) as avg_revenue_per_load
FROM load_data
WHERE status = 'completed'
GROUP BY driver
ORDER BY total_revenue DESC;

-- Monthly revenue summary (2024)
SELECT
    SUBSTR(load_dt, 1, 7) as month,
    COUNT(*) as loads,
    SUM(amount) as total_revenue,
    ROUND(AVG(amount), 2) as avg_revenue
FROM load_data
WHERE status = 'completed' AND load_dt LIKE '2024%'
GROUP BY month
ORDER BY month;

-- Container types summary
SELECT
    cont as container_type,
    COUNT(*) as count,
    SUM(amount) as total_revenue
FROM load_data
WHERE cont IS NOT NULL AND cont != ''
GROUP BY cont
ORDER BY count DESC;

-- Import vs Export loads
SELECT
    descrip as type,
    COUNT(*) as load_count,
    SUM(amount) as total_revenue,
    ROUND(AVG(amount), 2) as avg_revenue
FROM load_data
WHERE descrip IN ('import', 'export')
GROUP BY descrip;

-- Status breakdown
SELECT
    status,
    COUNT(*) as count,
    ROUND(SUM(amount), 2) as total_amount
FROM load_data
GROUP BY status
ORDER BY count DESC;

-- Loads with notes containing specific keywords
SELECT invoice, load_dt, company, notes, amount
FROM load_data
WHERE notes LIKE '%import%'
ORDER BY load_dt DESC;

-- ============================================================================
-- COMBINED QUERIES (CUSTOMERS + LOAD_DATA)
-- ============================================================================

-- Revenue by customer (matching on company name - approximate)
-- Note: This requires exact company name matching
SELECT
    l.company,
    COUNT(*) as loads,
    SUM(l.amount) as total_revenue,
    ROUND(AVG(l.amount), 2) as avg_revenue,
    c.commodity,
    c.city_st_zip
FROM load_data l
LEFT JOIN customers c ON LOWER(TRIM(l.company)) = LOWER(TRIM(c.company))
WHERE l.status = 'completed'
GROUP BY l.company
ORDER BY total_revenue DESC;

-- ============================================================================
-- DATE RANGE QUERIES
-- ============================================================================

-- Loads in specific date range
SELECT invoice, load_dt, driver, company, amount, status
FROM load_data
WHERE load_dt BETWEEN '2024-02-01' AND '2024-02-28'
ORDER BY load_dt;

-- Revenue for specific month
SELECT
    COUNT(*) as total_loads,
    SUM(amount) as total_revenue,
    ROUND(AVG(amount), 2) as avg_revenue
FROM load_data
WHERE load_dt LIKE '2024-02%' AND status = 'completed';

-- ============================================================================
-- ADVANCED ANALYTICS
-- ============================================================================

-- Top 10 most profitable routes/companies
SELECT
    company,
    COUNT(*) as trips,
    SUM(amount) as total_revenue,
    ROUND(AVG(amount), 2) as avg_per_load
FROM load_data
WHERE status = 'completed'
GROUP BY company
HAVING COUNT(*) >= 2
ORDER BY total_revenue DESC
LIMIT 10;

-- Driver performance comparison
SELECT
    driver,
    COUNT(*) as loads,
    SUM(amount) as revenue,
    ROUND(AVG(amount), 2) as avg_revenue,
    MIN(load_dt) as first_load,
    MAX(load_dt) as last_load
FROM load_data
WHERE status = 'completed' AND driver != 'NA'
GROUP BY driver
ORDER BY revenue DESC;

-- Daily load distribution
SELECT
    DATE(load_dt) as load_date,
    COUNT(*) as loads,
    SUM(amount) as revenue
FROM load_data
WHERE status = 'completed'
GROUP BY load_date
ORDER BY load_date DESC
LIMIT 30;

-- ============================================================================
-- EXPORT QUERIES
-- ============================================================================

-- Export completed loads to CSV format
.mode csv
.headers on
.output loads_export.csv
SELECT * FROM load_data WHERE status = 'completed';
.output stdout
.mode box

-- ============================================================================
-- MAINTENANCE QUERIES
-- ============================================================================

-- Check for NULL or empty values
SELECT 'customers_missing_email' as check_name, COUNT(*) as count
FROM customers WHERE email IS NULL OR email = ''
UNION ALL
SELECT 'load_data_missing_amount', COUNT(*)
FROM load_data WHERE amount IS NULL OR amount = 0
UNION ALL
SELECT 'load_data_cancelled', COUNT(*)
FROM load_data WHERE status = 'cancelled';

-- Database size and statistics
SELECT
    (SELECT COUNT(*) FROM customers) as total_customers,
    (SELECT COUNT(*) FROM load_data) as total_loads,
    (SELECT COUNT(*) FROM load_data WHERE status = 'completed') as completed_loads,
    (SELECT SUM(amount) FROM load_data WHERE status = 'completed') as total_revenue;
