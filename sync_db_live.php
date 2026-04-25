<?php
require_once 'config.php';

echo "<h3>Bajot CRM - Master Database Synchronization Utility</h3>";
echo "Checking and updating database schema for Live environment...<br><hr>";

// 1. Create Missing Tables
$new_tables = [
    'expense_categories' => "CREATE TABLE IF NOT EXISTS `expense_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'expenses' => "CREATE TABLE IF NOT EXISTS `expenses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category_id` INT,
        `dept_id` INT DEFAULT 1,
        `date` DATE NOT NULL,
        `amount` DECIMAL(15, 2) DEFAULT 0.00,
        `description` TEXT,
        `payment_mode` VARCHAR(50) DEFAULT 'Cash',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'salaries' => "CREATE TABLE IF NOT EXISTS `salaries` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `employee_id` INT NOT NULL,
        `month` VARCHAR(20),
        `year` INT,
        `monthly_salary` DECIMAL(15, 2),
        `working_days` INT,
        `total_days` INT,
        `week_offs` INT,
        `net_salary` DECIMAL(15, 2),
        `total_advances` DECIMAL(15, 2) DEFAULT 0.00,
        `expense_id` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'salary_advances' => "CREATE TABLE IF NOT EXISTS `salary_advances` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `employee_id` INT NOT NULL,
        `date` DATE NOT NULL,
        `amount` DECIMAL(15, 2) NOT NULL,
        `remarks` TEXT,
        `expense_id` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($new_tables as $table => $sql) {
    if ($conn->query($sql)) {
        echo "- Table '$table' checked/created.<br>";
    } else {
        echo "- <span style='color:red;'>Error creating '$table': " . $conn->error . "</span><br>";
    }
}

echo "<br><hr>";

// 2. Add Missing Columns to Existing Tables
$schema_updates = [
    'salaries' => [
        'total_advances' => "DECIMAL(15,2) DEFAULT 0.00 AFTER net_salary",
        'expense_id' => "INT AFTER total_advances"
    ],
    'salary_advances' => [
        'expense_id' => "INT AFTER remarks"
    ],
    'employees' => [
        'mobile_number' => "VARCHAR(15) AFTER name",
        'week_off' => "VARCHAR(20) DEFAULT 'No Week Off' AFTER salary"
    ],
    'parties' => [
        'ob_alum' => "DECIMAL(15,2) DEFAULT 0.00",
        'ob_pwdr' => "DECIMAL(15,2) DEFAULT 0.00",
        'ob_anod' => "DECIMAL(15,2) DEFAULT 0.00"
    ],
    'products' => [
        'dept_id' => "INT DEFAULT 1 AFTER id",
        'total_pcs' => "DECIMAL(15,2) DEFAULT 0.00",
        'total_kgs' => "DECIMAL(15,2) DEFAULT 0.00"
    ],
    'inwards' => [
        'dept_id' => "INT DEFAULT 1 AFTER id",
        'sub_total' => "DECIMAL(15,2) DEFAULT 0.00",
        'total_amount' => "DECIMAL(15,2) DEFAULT 0.00"
    ],
    'inward_items' => [
        'color' => "VARCHAR(100) AFTER product_id",
        'feet' => "DECIMAL(15,3) DEFAULT 0.000 AFTER color",
        'unit' => "VARCHAR(20) DEFAULT 'Kgs'",
        'qty_pcs' => "DECIMAL(15,2) DEFAULT 0.00",
        'qty_kgs' => "DECIMAL(15,2) DEFAULT 0.00"
    ],
    'outwards' => [
        'dept_id' => "INT DEFAULT 1 AFTER id",
        'narration' => "TEXT AFTER bill_no",
        'entry_format' => "VARCHAR(20) DEFAULT '' AFTER narration",
        'discount' => "DECIMAL(15,2) DEFAULT 0.00",
        'transport_charge' => "DECIMAL(15,2) DEFAULT 0.00",
        'sub_total' => "DECIMAL(15,2) DEFAULT 0.00",
        'total_amount' => "DECIMAL(15,2) DEFAULT 0.00"
    ],
    'outward_items' => [
        'color' => "VARCHAR(100) AFTER product_id",
        'feet' => "DECIMAL(15,3) DEFAULT 0.000 AFTER color",
        'unit' => "VARCHAR(20) DEFAULT 'Kgs'",
        'qty_pcs' => "DECIMAL(15,2) DEFAULT 0.00",
        'qty_kgs' => "DECIMAL(15,2) DEFAULT 0.00"
    ]
];

foreach ($schema_updates as $table => $columns) {
    echo "<b>Verifying table structure: $table</b><br>";
    foreach ($columns as $column => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check->num_rows == 0) {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            if ($conn->query($sql)) {
                echo "- Added column '$column'<br>";
            } else {
                echo "- <span style='color:red;'>Error adding '$column': " . $conn->error . "</span><br>";
            }
        } else {
            echo "- Column '$column' already exists.<br>";
        }
    }
    echo "<br>";
}

echo "<hr><h4>Schema Update Complete.</h4>";

// 3. One-time data fix for Powder Coating formats
echo "<b>Performing data migration:</b><br>";
$fix_sql = "UPDATE outwards o SET entry_format = 'foot' WHERE dept_id = 2 AND entry_format = '' AND EXISTS (SELECT 1 FROM outward_items oi WHERE oi.outward_id = o.id AND oi.feet > 0)";
if ($conn->query($fix_sql)) {
    echo "- Updated " . $conn->affected_rows . " existing records to 'foot' format.<br>";
}

echo "<br><b>Final steps:</b><br>";
echo "1. Powder Coating format issues (Add/Edit) are now resolved.<br>";
echo "2. Bill Number sorting and duplicate prevention is active.<br>";
echo "Please test these modules on the live environment.";
?>
