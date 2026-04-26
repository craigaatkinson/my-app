<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Database;

$pdo = Database::getInstance()->getConnection("mysql");

$name = 'Admin';
$email = 'admin@aems.com';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$role = 'admin';

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);

if ($stmt->fetch()) {
    echo "Admin user already exists.\n";
    exit(0);
}

$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password' => $password,
    'role' => $role,
]);

echo "Default admin user created:\n";
echo "  Email: admin@aems.com\n";
echo "  Password: admin123\n";
echo "  Role: admin\n";
