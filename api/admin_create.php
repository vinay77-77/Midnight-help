<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_post();
$body = read_json_body();

$name  = trim((string)($body['name'] ?? ''));
$email = strtolower(trim((string)($body['email'] ?? '')));
$phone = trim((string)($body['phone'] ?? ''));
$pass  = (string)($body['password'] ?? '');

if (strlen($name) < 2) json_response(['ok' => false, 'error' => 'Name is required'], 400);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['ok' => false, 'error' => 'Valid email required'], 400);
if (strlen($pass) < 8) json_response(['ok' => false, 'error' => 'Password must be at least 8 characters'], 400);

$res = db()->query('SELECT COUNT(*) AS c FROM admins');
$row = $res->fetch_assoc();
$count = (int)($row['c'] ?? 0);
if ($count > 0) {
  json_response(['ok' => false, 'error' => 'Admin already exists. Creation is disabled.'], 403);
}

$hash = password_hash($pass, PASSWORD_DEFAULT);

try {
  $stmt = db()->prepare('INSERT INTO admins (name, email, password_hash, phone) VALUES (?,?,?,?)');
  $stmt->bind_param('ssss', $name, $email, $hash, $phone);
  $stmt->execute();
  json_response(['ok' => true, 'adminId' => db()->insert_id], 201);
} catch (mysqli_sql_exception $e) {
  if (($e->getCode() ?? 0) === 1062) {
    json_response(['ok' => false, 'error' => 'Email already registered'], 409);
  }
  json_response(['ok' => false, 'error' => 'Server error'], 500);
}

