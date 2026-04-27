<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_post();
$body = read_json_body();

$first = trim((string)($body['firstName'] ?? ''));
$last  = trim((string)($body['lastName'] ?? ''));
$email = strtolower(trim((string)($body['email'] ?? '')));
$phone = trim((string)($body['phone'] ?? ''));
$dob   = trim((string)($body['dob'] ?? '')); // currently not stored in schema; kept for future use
$pass  = (string)($body['password'] ?? '');

if (strlen($first) < 2 || strlen($last) < 2) json_response(['ok' => false, 'error' => 'Name is required'], 400);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['ok' => false, 'error' => 'Valid email required'], 400);
if (strlen(preg_replace('/[\\s\\-()]/', '', $phone)) < 7) json_response(['ok' => false, 'error' => 'Valid phone required'], 400);
if (strlen($pass) < 8) json_response(['ok' => false, 'error' => 'Password must be at least 8 characters'], 400);

$name = $first . ' ' . $last;
$hash = password_hash($pass, PASSWORD_DEFAULT);

try {
  $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, phone) VALUES (?,?,?,?)');
  $stmt->bind_param('ssss', $name, $email, $hash, $phone);
  $stmt->execute();
  json_response(['ok' => true, 'userId' => db()->insert_id], 201);
} catch (mysqli_sql_exception $e) {
  // Duplicate email
  if (($e->getCode() ?? 0) === 1062) {
    json_response(['ok' => false, 'error' => 'Email already registered'], 409);
  }
  json_response(['ok' => false, 'error' => 'Server error'], 500);
}

