<?php
require_once 'config.php';

// Add company_logo column if not exists
$conn->query("ALTER TABLE `settings` ADD COLUMN `company_logo` VARCHAR(255) AFTER `company_name`") or true;

// Update settings
$conn->query("UPDATE settings SET company_name = 'Kaizer', company_logo = 'assets/img/logo.png' WHERE id = 1") or 
$conn->query("INSERT INTO settings (id, company_name, company_logo) VALUES (1, 'Kaizer', 'assets/img/logo.png')");

echo "Settings updated successfully.";
unlink(__FILE__);
?>
