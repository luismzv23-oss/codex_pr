<?php
$env = parse_ini_file('.env');
$host = $env['database.default.hostname'] ?? 'localhost';
$db = $env['database.default.database'] ?? 'codex_pr';
$user = $env['database.default.username'] ?? 'root';
$pass = $env['database.default.password'] ?? '';
$port = $env['database.default.port'] ?? 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("DELETE FROM auth_identities WHERE type = 'email_2fa'");
    $stmt->execute();
    echo "Identidades 2FA borradas.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
