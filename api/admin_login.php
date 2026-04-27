<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_post();
$body = read_json_body();

$email = strtolower(trim((string)($body['email'] ?? '')));
$pass  = (string)($body['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['ok' => false, 'error' => 'Valid email required'], 400);
if ($pass === '') json_response(['ok' => false, 'error' => 'Password required'], 400);

$stmt = db()->prepare('SELECT admin_id, name, email, password_hash, is_active FROM admins WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) json_response(['ok' => false, 'error' => 'Invalid email or password'], 401);
if ((int)$row['is_active'] !== 1) json_response(['ok' => false, 'error' => 'Account is disabled'], 403);
if (!password_verify($pass, (string)$row['password_hash'])) json_response(['ok' => false, 'error' => 'Invalid email or password'], 401);

session_start();
$_SESSION['admin_id'] = (int)$row['admin_id'];

json_response([
  'ok' => true,
  'admin' => [
    'adminId' => (int)$row['admin_id'],
    'name' => (string)$row['name'],
    'email' => (string)$row['email'],
  ],
]);

